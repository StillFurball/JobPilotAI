<?php

namespace App\Services\JobPilot;

use App\Models\CandidateProfile;
use App\Models\JobListing;
use App\Models\JobMatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Agent 2 — scores and ranks jobs for a candidate profile.
 *
 * Hybrid approach: deterministic scoring (authoritative) plus a supplemental
 * LLM explanation. Never claims a requirement is met without profile evidence.
 */
class JobMatchingAgent
{
    /** @var array<string, float> */
    public const WEIGHTS = [
        'skill' => 0.35,
        'keyword' => 0.20,
        'employment_type' => 0.15,
        'location' => 0.15,
        'education' => 0.10,
        'experience' => 0.05,
    ];

    public function __construct(protected LlmClient $llm) {}

    /**
     * @return array{mentions_degree: bool, mentions_intern: bool}
     */
    public function extractRequirements(JobListing $job): array
    {
        $text = Str::lower(($job->description ?? '').' '.($job->requirements ?? ''));

        return [
            'mentions_degree' => Str::contains($text, ['bachelor', 'degree', 's1', 'sarjana', 'diploma']),
            'mentions_intern' => Str::contains($text, ['intern', 'internship', 'magang', 'student']),
        ];
    }

    /**
     * Deterministic scoring. Returns component scores and the weighted overall score.
     *
     * @return array{score: float, skill_score: float, keyword_score: float, employment_type_score: float, location_score: float, education_score: float, experience_score: float, matched_skills: array<int, string>, missing_requirements: array<int, string>}
     */
    public function calculateMatchScore(CandidateProfile $profile, JobListing $job): array
    {
        $jobText = Str::lower(($job->title ?? '').' '.($job->description ?? '').' '.($job->requirements ?? ''));

        // Skills
        $skills = array_map(fn (string $s): string => Str::lower($s), $profile->skillNames());
        $matched = array_values(array_filter($skills, fn (string $s): bool => $s !== '' && str_contains($jobText, $s)));
        $skillScore = $skills !== [] ? min(count($matched) / count($skills), 1.0) : 0.0;

        // Keywords
        $keywords = array_map(fn ($k): string => Str::lower((string) $k), $profile->preferred_keywords ?? []);
        $keywordHits = count(array_filter($keywords, fn (string $kw): bool => $kw !== '' && str_contains($jobText, $kw)));
        $keywordScore = $keywords !== [] ? min($keywordHits / count($keywords), 1.0) : 0.5;

        // Employment type
        $preferredTypes = array_map(fn ($t): string => Str::lower((string) $t), $profile->preferred_employment_types ?? []);
        $jobType = Str::lower($job->employment_type ?? '');

        if ($preferredTypes === []) {
            $employmentScore = 0.5;
        } elseif ($jobType !== '' && collect($preferredTypes)->contains(fn (string $pt): bool => str_contains($jobType, $pt) || str_contains($pt, $jobType))) {
            $employmentScore = 1.0;
        } else {
            $employmentScore = 0.2;
        }

        // Location
        $preferredLocations = array_map(fn ($l): string => Str::lower((string) $l), $profile->preferred_locations ?? []);
        $preferredCountries = array_map(fn ($c): string => Str::lower((string) $c), $profile->preferred_countries ?? []);
        $jobLocation = Str::lower($job->location ?? '');
        $jobCountry = Str::lower($job->country ?? '');

        if ($preferredLocations === [] && $preferredCountries === []) {
            $locationScore = 0.5;
        } elseif (str_contains($jobLocation, 'remote')) {
            $locationScore = 1.0;
        } elseif (collect($preferredLocations)->contains(fn (string $pl): bool => $pl !== '' && str_contains($jobLocation, $pl))) {
            $locationScore = 1.0;
        } elseif (collect($preferredCountries)->contains(fn (string $pc): bool => $pc !== '' && str_contains($jobCountry, $pc))) {
            $locationScore = 0.7;
        } else {
            $locationScore = 0.1;
        }

        // Education & experience (evidence volume)
        $education = $profile->education ?? [];
        $educationScore = min(count($education) * 0.5, 1.0);
        $experienceScore = min(count($profile->experience ?? []) * 0.3, 1.0);

        $w = self::WEIGHTS;
        $overall = $skillScore * $w['skill']
            + $keywordScore * $w['keyword']
            + $employmentScore * $w['employment_type']
            + $locationScore * $w['location']
            + $educationScore * $w['education']
            + $experienceScore * $w['experience'];

        $missing = [];
        $requirements = $this->extractRequirements($job);

        if ($requirements['mentions_degree'] && $education === []) {
            $missing[] = 'Degree/education information not provided in profile';
        }

        if ($matched === [] && $skills !== []) {
            $missing[] = 'No matching skills found in job description';
        }

        return [
            'score' => round($overall, 3),
            'skill_score' => round($skillScore, 3),
            'keyword_score' => round($keywordScore, 3),
            'employment_type_score' => round($employmentScore, 3),
            'location_score' => round($locationScore, 3),
            'education_score' => round($educationScore, 3),
            'experience_score' => round($experienceScore, 3),
            'matched_skills' => $matched,
            'missing_requirements' => $missing,
        ];
    }

    /**
     * LLM semantic analysis — supplemental, not authoritative.
     *
     * @param  array{score: float, matched_skills: array<int, string>, missing_requirements: array<int, string>}  $scoreData
     */
    public function llmAnalysis(CandidateProfile $profile, JobListing $job, array $scoreData): string
    {
        $skills = implode(', ', $profile->skillNames());
        $matched = implode(', ', $scoreData['matched_skills']);
        $missing = implode('; ', $scoreData['missing_requirements']);
        $excerpt = Str::limit($job->description ?? '', 800, '');
        $summary = $profile->professional_summary ?: 'Not provided';
        $company = $job->company ?: 'Unknown';

        $prompt = <<<PROMPT
        You are a career advisor evaluating job-candidate compatibility.

        Candidate Profile:
        - Name: {$profile->full_name}
        - Skills: {$skills}
        - Summary: {$summary}

        Job:
        - Title: {$job->title}
        - Company: {$company}
        - Description (excerpt): {$excerpt}

        Match Score: {$scoreData['score']} / 1.0
        Matched Skills: {$matched}
        Missing: {$missing}

        In 2-3 sentences, explain why this job does or does not fit the candidate.
        Only mention qualifications that are actually present in the profile.
        Be honest about gaps. Do NOT fabricate any information.
        PROMPT;

        try {
            return $this->llm->generate($prompt, temperature: 0.2, maxTokens: 300);
        } catch (Throwable $e) {
            Log::warning('LLM analysis failed: {error}', ['error' => $e->getMessage()]);

            return 'LLM analysis unavailable: '.$e->getMessage();
        }
    }

    /**
     * Score every given job for the profile owner and persist the results.
     *
     * @param  iterable<JobListing>  $jobs
     * @return Collection<int, JobMatch>
     */
    public function run(CandidateProfile $profile, iterable $jobs, bool $withLlm = true): Collection
    {
        $matches = collect();

        foreach ($jobs as $job) {
            try {
                $scoreData = $this->calculateMatchScore($profile, $job);
                $analysis = $withLlm ? $this->llmAnalysis($profile, $job, $scoreData) : '';

                $matches->push(JobMatch::updateOrCreate(
                    ['user_id' => $profile->user_id, 'job_listing_id' => $job->id],
                    [
                        ...$scoreData,
                        'llm_analysis' => $analysis,
                        'explanation' => $this->buildExplanation($scoreData, $analysis),
                    ],
                ));
            } catch (Throwable $e) {
                Log::error('Failed to match job {job}: {error}', ['job' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        return $matches->sortByDesc('score')->values();
    }

    /**
     * @param  array{score: float, skill_score: float, location_score: float, employment_type_score: float, matched_skills: array<int, string>, missing_requirements: array<int, string>}  $scoreData
     */
    protected function buildExplanation(array $scoreData, string $analysis): string
    {
        $pct = fn (float $v): string => round($v * 100).'%';

        $lines = [
            'Overall match: '.$pct($scoreData['score']),
            'Skills: '.$pct($scoreData['skill_score']),
            'Location: '.$pct($scoreData['location_score']),
            'Employment type: '.$pct($scoreData['employment_type_score']),
        ];

        if ($scoreData['matched_skills'] !== []) {
            $lines[] = 'Matched skills: '.implode(', ', $scoreData['matched_skills']);
        }

        if ($scoreData['missing_requirements'] !== []) {
            $lines[] = 'Gaps: '.implode('; ', $scoreData['missing_requirements']);
        }

        if ($analysis !== '') {
            $lines[] = "\n".$analysis;
        }

        return implode("\n", $lines);
    }
}
