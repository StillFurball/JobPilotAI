<?php

namespace App\Services\JobPilot;

use App\Models\ApplicationDraft;
use App\Models\CandidateProfile;
use App\Models\JobListing;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Agent 3 — generates a tailored CV (PDF) for a specific job.
 *
 * Never fabricates: only reorders and adapts language over profile evidence.
 * Uses dompdf when installed (composer require dompdf/dompdf); otherwise the
 * CV is stored as print-ready HTML.
 */
class CvTailoringAgent
{
    public function __construct(protected LlmClient $llm) {}

    /**
     * Return only factual data from the profile — no fabrication allowed.
     *
     * @return array<string, mixed>
     */
    public function candidateEvidence(CandidateProfile $profile): array
    {
        return [
            'full_name' => $profile->full_name,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'location' => $profile->location,
            'linkedin_url' => $profile->linkedin_url,
            'github_url' => $profile->github_url,
            'portfolio_url' => $profile->portfolio_url,
            'professional_summary' => $profile->professional_summary,
            'skills' => $profile->skillNames(),
            'education' => $profile->education ?? [],
            'experience' => $profile->experience ?? [],
            'projects' => $profile->projects ?? [],
            'certifications' => $profile->certifications ?? [],
        ];
    }

    /**
     * Ask the LLM to reorder and adapt — not fabricate — CV content for the job.
     *
     * @param  array<string, mixed>  $evidence
     * @return array<string, mixed>
     */
    public function tailorContent(array $evidence, JobListing $job): array
    {
        $company = $job->company ?: 'Unknown Company';
        $excerpt = Str::limit($job->description ?? '', 1000, '');
        $data = json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
        You are an expert CV writer. Tailor the following candidate data for a specific job.

        RULES (mandatory):
        1. Do NOT add any skills, experience, certifications, or achievements not present in the data.
        2. Do NOT change names, dates, company names, or qualifications.
        3. Only reorder bullet points, adapt wording, and write a focused professional summary.
        4. Return a valid JSON object matching the schema below.
        5. If a section is empty in the input, keep it empty in the output.

        Job: {$job->title} at {$company}
        Job description excerpt: {$excerpt}

        Candidate data:
        {$data}

        Return JSON with this exact structure:
        {
          "professional_summary": "2-3 sentence summary relevant to this job",
          "skills_ordered": ["skill1", "skill2"],
          "education": [],
          "experience": [],
          "projects": [],
          "certifications": []
        }

        Return ONLY the JSON object, no other text.
        PROMPT;

        $fallback = [
            'professional_summary' => $evidence['professional_summary'] ?? '',
            'skills_ordered' => $evidence['skills'] ?? [],
            'education' => $evidence['education'] ?? [],
            'experience' => $evidence['experience'] ?? [],
            'projects' => $evidence['projects'] ?? [],
            'certifications' => $evidence['certifications'] ?? [],
        ];

        try {
            $raw = $this->llm->generate($prompt, temperature: 0.2, maxTokens: 2000);
            $start = strpos($raw, '{');
            $end = strrpos($raw, '}');

            if ($start === false || $end === false || $end <= $start) {
                throw new \UnexpectedValueException('No JSON found in LLM response');
            }

            $tailored = json_decode(substr($raw, $start, $end - $start + 1), true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($tailored)) {
                throw new \UnexpectedValueException('LLM response is not a JSON object');
            }

            return array_replace($fallback, array_intersect_key($tailored, $fallback));
        } catch (Throwable $e) {
            Log::info('CV tailoring LLM unavailable ({error}); using raw profile data.', ['error' => $e->getMessage()]);

            return $fallback;
        }
    }

    /**
     * Verify tailored CV against source evidence, removing anything fabricated.
     *
     * Skills must exist in the profile; experience, education, projects and
     * certifications must be items that exist in the profile (only reordering).
     *
     * @param  array<string, mixed>  $tailored
     * @param  array<string, mixed>  $evidence
     * @return array{tailored: array<string, mixed>, warnings: array<int, string>}
     */
    public function validateClaims(array $tailored, array $evidence): array
    {
        $warnings = [];
        $originalSkills = array_map(fn (string $s): string => Str::lower($s), $evidence['skills'] ?? []);

        $tailored['skills_ordered'] = array_values(array_filter(
            array_map(fn ($s): string => is_array($s) ? (string) ($s['name'] ?? '') : (string) $s, (array) ($tailored['skills_ordered'] ?? [])),
            function (string $skill) use ($originalSkills, &$warnings): bool {
                if (in_array(Str::lower($skill), $originalSkills, true)) {
                    return true;
                }
                $warnings[] = "Skill '{$skill}' not found in original profile — removed";

                return false;
            },
        ));

        foreach (['education', 'experience', 'projects', 'certifications'] as $section) {
            $original = $evidence[$section] ?? [];
            $fingerprints = array_map(fn ($item): string => $this->fingerprint($item), $original);

            $kept = array_values(array_filter(
                (array) ($tailored[$section] ?? []),
                fn ($item): bool => in_array($this->fingerprint($item), $fingerprints, true),
            ));

            if (count($kept) !== count((array) ($tailored[$section] ?? []))) {
                $warnings[] = "Unverified {$section} entries were dropped";
            }

            // Fall back to the original evidence when the LLM output can't be verified.
            $tailored[$section] = count($kept) === count($original) ? $kept : $original;
        }

        return ['tailored' => $tailored, 'warnings' => $warnings];
    }

    /**
     * @param  array<string, mixed>  $evidence
     * @param  array<string, mixed>  $tailored
     */
    public function renderHtml(array $evidence, array $tailored, JobListing $job): string
    {
        return view('cv.template', [
            'candidate' => $evidence,
            'tailored' => $tailored,
            'jobTitle' => $job->title,
            'jobCompany' => $job->company ?? '',
        ])->render();
    }

    /**
     * Render and store the CV. Returns [relative path, sha256 hash].
     *
     * @return array{path: string, hash: string}
     */
    public function storeCv(string $html, int $userId, int $jobId): array
    {
        $disk = Storage::disk((string) config('jobpilot.cv_disk', 'local'));
        $base = trim((string) config('jobpilot.cv_directory', 'cvs'), '/')."/{$userId}/cv_{$jobId}_".Str::lower(Str::random(8));

        $contents = $this->renderPdf($html);
        $path = $contents !== null ? "{$base}.pdf" : "{$base}.html";
        $contents ??= $html;

        $disk->put($path, $contents);

        return ['path' => $path, 'hash' => hash('sha256', $contents)];
    }

    /**
     * Convert HTML to PDF bytes, or null when no PDF engine is installed.
     */
    protected function renderPdf(string $html): ?string
    {
        if (! class_exists(Dompdf::class)) {
            return null;
        }

        try {
            $dompdf = new Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'Helvetica']);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4');
            $dompdf->render();

            return $dompdf->output() ?: null;
        } catch (Throwable $e) {
            Log::error('PDF rendering failed: {error}', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Main entry point: build a new draft (reusing a still-pending one for the same job).
     */
    public function generateDraft(CandidateProfile $profile, JobListing $job): ApplicationDraft
    {
        $existing = ApplicationDraft::query()
            ->where('user_id', $profile->user_id)
            ->where('job_listing_id', $job->id)
            ->whereIn('status', ApplicationDraft::PENDING_STATUSES)
            ->latest('version')
            ->first();

        if ($existing) {
            return $existing;
        }

        $evidence = $this->candidateEvidence($profile);
        ['tailored' => $tailored, 'warnings' => $warnings] = $this->validateClaims($this->tailorContent($evidence, $job), $evidence);

        if ($warnings !== []) {
            Log::warning('CV validation warnings', $warnings);
        }

        $cv = $this->storeCv($this->renderHtml($evidence, $tailored, $job), $profile->user_id, $job->id);

        return new ApplicationDraft([
            'user_id' => $profile->user_id,
            'job_listing_id' => $job->id,
            'version' => 1,
            'cv_file_path' => $cv['path'],
            'cv_file_hash' => $cv['hash'],
            'status' => 'draft',
        ]);
    }

    protected function fingerprint(mixed $item): string
    {
        if (is_array($item)) {
            ksort($item);
        }

        return md5((string) json_encode($item));
    }
}
