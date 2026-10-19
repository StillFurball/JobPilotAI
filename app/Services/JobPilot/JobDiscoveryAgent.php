<?php

namespace App\Services\JobPilot;

use App\Models\JobListing;
use App\Services\JobPilot\JobSources\AdzunaJobSource;
use App\Services\JobPilot\JobSources\JobSource;
use App\Services\JobPilot\JobSources\MockJobSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Agent 1 — discovers jobs, normalizes, deduplicates and persists them.
 *
 * Vacancies are shared between all users: a job already stored by another
 * user's search is reused (and refreshed) rather than duplicated.
 */
class JobDiscoveryAgent
{
    public function __construct(
        protected AdzunaJobSource $adzuna,
        protected MockJobSource $mock,
    ) {}

    /**
     * Resolve which sources to query: "auto" prefers Adzuna when configured.
     *
     * @return array<int, JobSource>
     */
    public function sources(string $preference = 'auto'): array
    {
        return match ($preference) {
            'mock' => [$this->mock],
            default => $this->adzuna->isAvailable() ? [$this->adzuna] : [$this->mock],
        };
    }

    /**
     * Countries a source cannot cover fall back to sample listings, and each
     * fallback is reported in `warnings` so the run does not look like a
     * clean empty search.
     *
     * @param  array<int, string>  $keywords
     * @param  array<int, string>  $countries
     * @param  array<int, string>  $employmentTypes
     * @return array{jobs: Collection<int, JobListing>, new: int, warnings: array<int, string>}
     */
    public function run(
        array $keywords,
        array $countries,
        array $employmentTypes,
        int $maxJobs,
        string $sourcePreference = 'auto',
    ): array {
        $countries = $countries !== [] ? $countries : ['id'];
        $perCountry = max(1, intdiv($maxJobs, max(count($countries), 1)));
        $found = [];
        $warnings = [];

        foreach ($this->sources($sourcePreference) as $preferredSource) {
            foreach ($countries as $country) {
                $source = $preferredSource;

                if (! $source->supportsCountry($country)) {
                    $warnings[] = sprintf(
                        '%s does not cover %s; showing sample listings instead.',
                        ucfirst($source->name()),
                        strtoupper($country),
                    );
                    $source = $this->mock;
                }

                foreach ($employmentTypes !== [] ? $employmentTypes : [null] as $type) {
                    try {
                        array_push($found, ...$source->search(
                            keywords: $keywords,
                            country: $country,
                            employmentType: $type,
                            maxResults: $perCountry,
                        ));
                    } catch (Throwable $e) {
                        Log::error('Source {source} failed for country={country}: {error}', [
                            'source' => $source->name(),
                            'country' => $country,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        $unique = collect($found)
            ->unique(fn ($job): string => $job->source.'|'.$job->sourceId)
            ->take($maxJobs);

        $new = 0;

        $jobs = $unique->map(function ($normalized) use (&$new): JobListing {
            $job = JobListing::updateOrCreate(
                ['source' => $normalized->source, 'source_id' => $normalized->sourceId],
                $normalized->toAttributes(),
            );

            if ($job->wasRecentlyCreated) {
                $new++;
            }

            return $job;
        })->values();

        return ['jobs' => $jobs, 'new' => $new, 'warnings' => $warnings];
    }
}
