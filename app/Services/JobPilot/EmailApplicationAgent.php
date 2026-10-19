<?php

namespace App\Services\JobPilot;

use App\Models\ApplicationDraft;
use App\Models\CandidateProfile;
use App\Models\JobListing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Agent 4 — drafts professional application emails.
 *
 * Never guesses recruiter addresses: only an application email published in
 * the vacancy is used (and marked verified). Requires explicit approval.
 */
class EmailApplicationAgent
{
    public function __construct(protected LlmClient $llm) {}

    /**
     * @return array{0: string|null, 1: bool}
     */
    public function applicationAddress(JobListing $job): array
    {
        return $job->application_email ? [$job->application_email, true] : [null, false];
    }

    public function draftEmail(ApplicationDraft $draft, CandidateProfile $profile, JobListing $job): ApplicationDraft
    {
        [$recipient, $verified] = $this->applicationAddress($job);
        [$subject, $body] = $this->generateEmail($profile, $job);

        $draft->fill([
            'recipient_email' => $recipient,
            'recipient_verified' => $verified,
            'subject' => $subject,
            'body' => $body,
            'status' => 'draft',
        ]);
        $draft->refreshContentHash();

        return $draft;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function generateEmail(CandidateProfile $profile, JobListing $job): array
    {
        $skills = implode(', ', array_slice($profile->skillNames(), 0, 8));
        $experience = $profile->experience ?? [];
        $experienceSummary = $experience !== []
            ? count($experience).' work experience(s) including '.collect($experience)->take(2)
                ->map(fn (array $e): string => ($e['title'] ?? 'role').' at '.($e['company'] ?? 'company'))
                ->implode(', ')
            : 'no prior work experience (student/fresh graduate)';
        $summary = $profile->professional_summary ?: 'Not provided';
        $company = $job->company ?: 'the company';
        $location = $job->location ?: 'Unknown';
        $excerpt = Str::limit($job->description ?? '', 600, '');

        $prompt = <<<PROMPT
        Write a professional job application email.

        Candidate: {$profile->full_name}
        Email: {$profile->email}
        Key Skills: {$skills}
        Experience: {$experienceSummary}
        Summary: {$summary}

        Job: {$job->title} at {$company}
        Location: {$location}
        Job Description: {$excerpt}

        Instructions:
        - Write a formal, professional application email in English
        - Do NOT fabricate any achievements or qualifications not listed above
        - Keep it concise (3-4 paragraphs)
        - Include a subject line as the first line (format: "Subject: <subject text>")
        - Then a blank line, then the email body
        - Sign with the candidate's full name
        - Be genuine and specific to this role
        PROMPT;

        try {
            $lines = explode("\n", trim($this->llm->generate($prompt, temperature: 0.3, maxTokens: 800)));
            $subject = 'Application for '.$job->title;
            $bodyStart = 0;

            foreach ($lines as $i => $line) {
                if (Str::startsWith(Str::lower($line), 'subject:')) {
                    $subject = trim(Str::after($line, ':'));
                    $bodyStart = $i + 1;
                    break;
                }
            }

            $body = trim(implode("\n", array_slice($lines, $bodyStart)));

            // The offline engine signs as "Candidate"; personalise it.
            $body = preg_replace('/\nCandidate$/', "\n".$profile->full_name, $body) ?? $body;

            if ($body === '') {
                throw new \UnexpectedValueException('Empty email body');
            }

            return [$subject, $body];
        } catch (Throwable $e) {
            Log::warning('Email generation LLM failed: {error}', ['error' => $e->getMessage()]);

            return ["Application for {$job->title} — {$profile->full_name}", $this->fallbackEmail($profile, $job)];
        }
    }

    protected function fallbackEmail(CandidateProfile $profile, JobListing $job): string
    {
        $skills = implode(', ', array_slice($profile->skillNames(), 0, 6)) ?: 'relevant technologies';
        $company = $job->company ?: 'your company';

        return trim(<<<EMAIL
        Dear Hiring Manager,

        I am writing to express my interest in the {$job->title} position at {$company}.

        My name is {$profile->full_name} and I have experience with {$skills}. {$profile->professional_summary}

        I am excited about the opportunity to contribute to your team and believe my background aligns well with the requirements described in the job posting.

        I have attached my CV for your review. I look forward to hearing from you.

        Best regards,
        {$profile->full_name}
        {$profile->email}
        {$profile->phone}
        EMAIL);
    }
}
