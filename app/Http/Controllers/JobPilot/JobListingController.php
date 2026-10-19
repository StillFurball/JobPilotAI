<?php

namespace App\Http\Controllers\JobPilot;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\JobMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobListingController extends Controller
{
    /**
     * Browse discovered vacancies with the user's own match analysis.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'employment_type' => ['nullable', 'string', 'in:full_time,part_time,internship,contract'],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $userId = $request->user()->id;
        $minScore = (float) ($filters['min_score'] ?? 0);

        $jobs = JobListing::query()
            ->where('is_active', true)
            ->with(['userMatch' => fn (HasOne $query) => $query->where('user_id', $userId)])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"),
            ))
            ->when($filters['country'] ?? null, fn (Builder $query, string $country) => $query->where('country', strtoupper($country)))
            ->when($filters['employment_type'] ?? null, fn (Builder $query, string $type) => $query->where('employment_type', $type))
            ->when($minScore > 0, fn (Builder $query) => $query->whereHas(
                'matches',
                fn (Builder $q) => $q->where('user_id', $userId)->where('score', '>=', $minScore),
            ))
            ->orderByDesc(
                JobMatch::query()
                    ->select('score')
                    ->where('user_id', $userId)
                    ->whereColumn('job_matches.job_listing_id', 'job_listings.id')
                    ->limit(1),
            )
            ->orderByDesc('posted_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('jobs/index', [
            'jobs' => $jobs,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'country' => $filters['country'] ?? '',
                'employment_type' => $filters['employment_type'] ?? '',
                'min_score' => (string) ($filters['min_score'] ?? '0'),
            ],
            'candidateName' => $request->user()->profileOrCreate()->full_name,
        ]);
    }
}
