<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDraft;
use App\Models\JobMatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->profileOrCreate();

        return Inertia::render('dashboard', [
            'profile' => $profile->only(['full_name', 'preferred_keywords', 'preferred_countries', 'preferred_employment_types']),
            'stats' => [
                'matchedJobs' => $user->jobMatches()->count(),
                'highMatches' => $user->jobMatches()->where('score', '>=', 0.7)->count(),
                'pendingDrafts' => $user->applicationDrafts()->whereIn('status', ApplicationDraft::PENDING_STATUSES)->count(),
                'sentApplications' => $user->applications()->where('status', 'sent')->count(),
            ],
            'topMatches' => $user->jobMatches()
                ->with('jobListing:id,title,company,location,country,employment_type')
                ->orderByDesc('score')
                ->limit(5)
                ->get()
                ->map(fn (JobMatch $match): array => [
                    'id' => $match->id,
                    'score' => $match->score,
                    'job' => $match->jobListing->only(['id', 'title', 'company', 'location', 'country', 'employment_type']),
                ]),
            'latestWorkflow' => $user->workflowRuns()->latest('id')->first(),
            'adzunaConfigured' => config('jobpilot.adzuna.app_id') !== '' && config('jobpilot.adzuna.app_key') !== '',
        ]);
    }
}
