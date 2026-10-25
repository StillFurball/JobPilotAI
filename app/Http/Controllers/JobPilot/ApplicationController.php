<?php

namespace App\Http\Controllers\JobPilot;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\JobPilot\ApplicationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ApplicationController extends Controller
{
    /**
     * Application tracker: lifecycle of approved applications.
     */
    public function index(Request $request): Response
    {
        $applications = $request->user()->applications()
            ->with([
                'jobListing:id,title,company,location,country',
                'draft:id,version,recipient_email,subject,content_hash,status',
            ])
            ->latest()
            ->get();

        return Inertia::render('applications/index', [
            'applications' => $applications,
            'sendEnabled' => app(ApplicationDispatcher::class)->enabled(),
            'sender' => [
                'email' => $request->user()->email,
                'emailVerified' => $request->user()->hasVerifiedEmail(),
                'mailboxConnected' => $request->user()->mailAccount()->exists(),
            ],
        ]);
    }

    /**
     * Re-verify approval and content integrity, then dispatch.
     */
    public function send(Request $request, Application $application, ApplicationDispatcher $dispatcher): RedirectResponse
    {
        Gate::authorize('send', $application);

        $application->loadMissing('draft.user');
        $draft = $application->draft;

        if ($application->status === 'sent') {
            throw ValidationException::withMessages(['application' => __('This application has already been sent.')]);
        }

        if ($draft->status !== 'approved') {
            throw ValidationException::withMessages(['application' => __('The draft must be approved before sending.')]);
        }

        $currentHash = $draft->computeContentHash();

        if (! $application->sent_content_hash || ! hash_equals($application->sent_content_hash, $currentHash)) {
            throw ValidationException::withMessages(['application' => __('Draft content changed after approval — re-approval required.')]);
        }

        if (! $request->user()->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['application' => __('Verify your email address before sending applications.')]);
        }

        if (! $dispatcher->enabled()) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('Safe mode: approval for draft #:id verified, but sending is disabled. Set JOBPILOT_SEND_EMAILS=true to send.', ['id' => $draft->id]),
            ]);

            return back();
        }

        if (! $request->user()->mailAccount()->exists()) {
            throw ValidationException::withMessages(['application' => __('Connect your mailbox in Settings → Email before sending applications.')]);
        }

        $application->update(['status' => 'sending']);

        try {
            $messageId = $dispatcher->send($application);
        } catch (Throwable $e) {
            report($e);
            $application->update(['status' => 'uncertain', 'error_message' => $e->getMessage()]);
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Send failed (status = uncertain): :error', ['error' => $e->getMessage()])]);

            return back();
        }

        $application->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $messageId,
            'error_message' => null,
        ]);
        $draft->update(['status' => 'sent']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application #:id sent.', ['id' => $application->id])]);

        return back();
    }
}
