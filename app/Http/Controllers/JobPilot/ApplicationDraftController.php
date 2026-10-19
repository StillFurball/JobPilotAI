<?php

namespace App\Http\Controllers\JobPilot;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobPilot\ReviewDraftRequest;
use App\Http\Requests\JobPilot\UpdateDraftRequest;
use App\Models\Application;
use App\Models\ApplicationDraft;
use App\Models\ApprovalRecord;
use App\Models\JobListing;
use App\Services\JobPilot\CvTailoringAgent;
use App\Services\JobPilot\EmailApplicationAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Review & approval gate: nothing is ever sent without an explicit,
 * hash-bound approval of the exact recipient, subject, body and CV.
 */
class ApplicationDraftController extends Controller
{
    public function index(Request $request): Response
    {
        $drafts = $request->user()->applicationDrafts()
            ->with('jobListing:id,title,company,location,country,application_url')
            ->latest('updated_at')
            ->get();

        $selectedId = $request->integer('draft') ?: $drafts->first()?->id;
        $selected = $drafts->firstWhere('id', $selectedId);

        return Inertia::render('review/index', [
            'drafts' => $drafts->map(fn (ApplicationDraft $draft): array => $this->present($draft)),
            'selected' => $selected ? [
                ...$this->present($selected),
                'approvals' => $selected->approvalRecords()->latest()->get(['id', 'action', 'approved_content_hash', 'notes', 'created_at']),
            ] : null,
        ]);
    }

    /**
     * Tailor the CV and draft the email for a vacancy.
     */
    public function store(Request $request, JobListing $jobListing, CvTailoringAgent $cvAgent, EmailApplicationAgent $emailAgent): RedirectResponse
    {
        $profile = $request->user()->profileOrCreate();
        $draft = $cvAgent->generateDraft($profile, $jobListing);

        if (! $draft->exists) {
            $emailAgent->draftEmail($draft, $profile, $jobListing)->save();
            Inertia::flash('toast', ['type' => 'success', 'message' => __('CV tailored and email drafted. Please review before approving.')]);
        }

        return to_route('drafts.index', ['draft' => $draft->id]);
    }

    /**
     * Save edits: bump version and re-hash, invalidating any prior approval.
     */
    public function update(UpdateDraftRequest $request, ApplicationDraft $draft): RedirectResponse
    {
        if (in_array($draft->status, ['approved', 'sent'], true)) {
            throw ValidationException::withMessages([
                'draft' => __("Cannot edit a draft in status ':status'.", ['status' => $draft->status]),
            ]);
        }

        $draft->fill($request->validated());
        $draft->version++;
        $draft->status = 'draft';
        $draft->refreshContentHash();
        $draft->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Draft saved as v:version. Content hash re-anchored.', ['version' => $draft->version])]);

        return to_route('drafts.index', ['draft' => $draft->id]);
    }

    public function approve(ReviewDraftRequest $request, ApplicationDraft $draft): RedirectResponse
    {
        $currentHash = $draft->computeContentHash();

        if (! hash_equals($currentHash, (string) $request->validated('content_hash'))) {
            throw ValidationException::withMessages([
                'content_hash' => __('Content hash mismatch — the draft has changed since you loaded it. Please reload and re-review before approving.'),
            ]);
        }

        if ($draft->status === 'sent') {
            throw ValidationException::withMessages(['draft' => __('This draft has already been sent.')]);
        }

        if (! $draft->recipient_email) {
            throw ValidationException::withMessages(['recipient_email' => __('Add and save a recipient email before approving.')]);
        }

        DB::transaction(function () use ($request, $draft, $currentHash): void {
            ApprovalRecord::create([
                'application_draft_id' => $draft->id,
                'user_id' => $request->user()->id,
                'action' => 'approved',
                'approved_content_hash' => $currentHash,
                'notes' => $request->validated('notes'),
            ]);

            $draft->update(['status' => 'approved', 'content_hash' => $currentHash]);

            Application::updateOrCreate(
                ['application_draft_id' => $draft->id],
                [
                    'user_id' => $draft->user_id,
                    'job_listing_id' => $draft->job_listing_id,
                    'sent_content_hash' => $currentHash,
                    'status' => 'approved',
                    'approved_at' => now(),
                    'error_message' => null,
                ],
            );
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application approved and bound to hash :hash…', ['hash' => substr($currentHash, 0, 16)])]);

        return to_route('drafts.index', ['draft' => $draft->id]);
    }

    public function reject(ReviewDraftRequest $request, ApplicationDraft $draft): RedirectResponse
    {
        if ($draft->status === 'sent') {
            throw ValidationException::withMessages(['draft' => __('This draft has already been sent.')]);
        }

        DB::transaction(function () use ($request, $draft): void {
            ApprovalRecord::create([
                'application_draft_id' => $draft->id,
                'user_id' => $request->user()->id,
                'action' => 'rejected',
                'approved_content_hash' => $draft->content_hash ?? '',
                'notes' => $request->validated('notes'),
            ]);

            $draft->update(['status' => 'rejected']);
            $draft->application?->update(['status' => 'rejected']);
        });

        Inertia::flash('toast', ['type' => 'warning', 'message' => __('Draft rejected.')]);

        return to_route('drafts.index', ['draft' => $draft->id]);
    }

    /**
     * Stream the tailored CV (inline, so it can be previewed in an iframe).
     */
    public function cv(Request $request, ApplicationDraft $draft): StreamedResponse
    {
        Gate::authorize('view', $draft);

        $disk = Storage::disk((string) config('jobpilot.cv_disk', 'local'));
        abort_unless($draft->cv_file_path && $disk->exists($draft->cv_file_path), 404, 'CV file not found');

        $isPdf = str_ends_with($draft->cv_file_path, '.pdf');

        return $disk->response(
            $draft->cv_file_path,
            "cv_draft_{$draft->id}.".($isPdf ? 'pdf' : 'html'),
            ['Content-Type' => $isPdf ? 'application/pdf' : 'text/html; charset=UTF-8'],
            $request->boolean('download') ? 'attachment' : 'inline',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(ApplicationDraft $draft): array
    {
        return [
            ...$draft->only([
                'id', 'job_listing_id', 'version', 'recipient_email', 'recipient_verified', 'subject', 'body',
                'cv_file_hash', 'content_hash', 'status', 'created_at', 'updated_at',
            ]),
            'has_cv' => (bool) $draft->cv_file_path,
            'cv_format' => $draft->cv_file_path ? pathinfo($draft->cv_file_path, PATHINFO_EXTENSION) : null,
            'job' => $draft->jobListing->only(['id', 'title', 'company', 'location', 'country', 'application_url']),
        ];
    }
}
