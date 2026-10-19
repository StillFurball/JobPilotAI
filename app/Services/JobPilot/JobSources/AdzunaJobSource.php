<?php

namespace App\Services\JobPilot\JobSources;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Adzuna job search API adapter. Requires ADZUNA_APP_ID and ADZUNA_APP_KEY.
 */
class AdzunaJobSource implements JobSource
{
    protected const API_BASE = 'https://api.adzuna.com/v1/api/jobs';

    /** @var array<string, string> */
    protected const EMPLOYMENT_MAP = [
        'permanent' => 'full_time',
        'contract' => 'contract',
        'part_time' => 'part_time',
        'temporary' => 'contract',
    ];

    /**
     * Country endpoints Adzuna serves; any other code returns HTTP 404.
     *
     * @var array<int, string>
     */
    public const SUPPORTED_COUNTRIES = [
        'at', 'au', 'be', 'br', 'ca', 'ch', 'de', 'es', 'fr', 'gb',
        'in', 'it', 'mx', 'nl', 'nz', 'pl', 'sg', 'us', 'za',
    ];

    public function __construct(
        protected string $appId = '',
        protected string $appKey = '',
        protected string $defaultCountry = 'id',
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            appId: (string) config('jobpilot.adzuna.app_id'),
            appKey: (string) config('jobpilot.adzuna.app_key'),
            defaultCountry: (string) config('jobpilot.adzuna.country', 'id'),
        );
    }

    public function name(): string
    {
        return 'adzuna';
    }

    public function isAvailable(): bool
    {
        return $this->appId !== '' && $this->appKey !== '';
    }

    public function supportsCountry(string $country): bool
    {
        return in_array(Str::lower($country), self::SUPPORTED_COUNTRIES, true);
    }

    public function search(
        array $keywords,
        string $country = 'id',
        ?string $location = null,
        ?string $employmentType = null,
        int $maxResults = 20,
    ): array {
        if (! $this->isAvailable()) {
            Log::warning('Adzuna credentials not configured — returning empty list');

            return [];
        }

        $results = [];
        $page = 1;
        $perPage = min($maxResults, 50);
        $query = $keywords !== [] ? implode(' ', $keywords) : 'software engineer';
        $countryCode = Str::lower($country ?: $this->defaultCountry);

        if (! $this->supportsCountry($countryCode)) {
            Log::warning('Adzuna does not cover country={country} — returning empty list', ['country' => $countryCode]);

            return [];
        }

        while (count($results) < $maxResults) {
            $params = array_filter([
                'app_id' => $this->appId,
                'app_key' => $this->appKey,
                'results_per_page' => $perPage,
                'what' => $query,
                'where' => $location,
            ]);

            try {
                $response = Http::timeout(30)
                    ->get(self::API_BASE."/{$countryCode}/search/{$page}", $params)
                    ->throw();
            } catch (Throwable $e) {
                Log::error('Adzuna request error: {error}', ['error' => $e->getMessage()]);
                break;
            }

            /** @var array<int, array<string, mixed>> $rawJobs */
            $rawJobs = $response->json('results', []);

            if ($rawJobs === []) {
                break;
            }

            foreach ($rawJobs as $raw) {
                if ($job = $this->normalize($raw, $countryCode)) {
                    $results[] = $job;
                }
            }

            if (count($rawJobs) < $perPage) {
                break;
            }

            $page++;
        }

        return array_slice($results, 0, $maxResults);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    protected function normalize(array $raw, string $country): ?NormalizedJob
    {
        $sourceId = (string) ($raw['id'] ?? '');

        if ($sourceId === '') {
            return null;
        }

        $contract = (string) ($raw['contract_type'] ?? '');
        $postedAt = null;

        if (! empty($raw['created'])) {
            try {
                $postedAt = Carbon::parse((string) $raw['created']);
            } catch (Throwable) {
                $postedAt = null;
            }
        }

        return new NormalizedJob(
            source: 'adzuna',
            sourceId: $sourceId,
            title: (string) ($raw['title'] ?? 'Untitled'),
            company: data_get($raw, 'company.display_name'),
            location: data_get($raw, 'location.display_name'),
            country: Str::upper($country),
            employmentType: self::EMPLOYMENT_MAP[$contract] ?? ($contract !== '' ? $contract : null),
            description: isset($raw['description']) ? (string) $raw['description'] : null,
            salaryMin: isset($raw['salary_min']) ? (int) $raw['salary_min'] : null,
            salaryMax: isset($raw['salary_max']) ? (int) $raw['salary_max'] : null,
            salaryCurrency: null,
            applicationUrl: isset($raw['redirect_url']) ? (string) $raw['redirect_url'] : null,
            postedAt: $postedAt,
            rawData: $raw,
        );
    }
}
