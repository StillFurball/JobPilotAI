<?php

namespace App\Services\JobPilot\JobSources;

/**
 * Pluggable interface for all job sources.
 */
interface JobSource
{
    public function name(): string;

    /**
     * Return False if required credentials are missing.
     */
    public function isAvailable(): bool;

    /**
     * Whether the source can search the given ISO 3166-1 alpha-2 country.
     */
    public function supportsCountry(string $country): bool;

    /**
     * @param  array<int, string>  $keywords
     * @return array<int, NormalizedJob>
     */
    public function search(
        array $keywords,
        string $country = 'id',
        ?string $location = null,
        ?string $employmentType = null,
        int $maxResults = 20,
    ): array;
}
