<?php

namespace App\Services\JobPilot\JobSources;

use Carbon\CarbonInterface;

/**
 * Canonical job representation regardless of source.
 */
final readonly class NormalizedJob
{
    /**
     * @param  array<string, mixed>  $rawData
     */
    public function __construct(
        public string $source,
        public string $sourceId,
        public string $title,
        public ?string $company = null,
        public ?string $location = null,
        public ?string $country = null,
        public ?string $employmentType = null,
        public ?string $description = null,
        public ?string $requirements = null,
        public ?int $salaryMin = null,
        public ?int $salaryMax = null,
        public ?string $salaryCurrency = null,
        public ?string $applicationUrl = null,
        public ?string $applicationEmail = null,
        public ?CarbonInterface $postedAt = null,
        public ?CarbonInterface $expiresAt = null,
        public array $rawData = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'company' => $this->company,
            'location' => $this->location,
            'country' => $this->country,
            'employment_type' => $this->employmentType,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'salary_min' => $this->salaryMin,
            'salary_max' => $this->salaryMax,
            'salary_currency' => $this->salaryCurrency,
            'application_url' => $this->applicationUrl,
            'application_email' => $this->applicationEmail,
            'posted_at' => $this->postedAt,
            'expires_at' => $this->expiresAt,
            'raw_data' => $this->rawData,
            'is_active' => true,
        ];
    }
}
