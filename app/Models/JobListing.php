<?php

namespace App\Models;

use Database\Factories\JobListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A discovered job vacancy. Shared across all users (deduplicated by source + source_id).
 *
 * @property int $id
 * @property string $source
 * @property string $source_id
 * @property string $title
 * @property string|null $company
 * @property string|null $location
 * @property string|null $country
 * @property string|null $employment_type
 * @property string|null $description
 * @property string|null $requirements
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property string|null $salary_currency
 * @property string|null $application_url
 * @property string|null $application_email
 * @property Carbon|null $posted_at
 * @property Carbon|null $expires_at
 * @property array<string, mixed>|null $raw_data
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'source', 'source_id', 'title', 'company', 'location', 'country', 'employment_type',
    'description', 'requirements', 'salary_min', 'salary_max', 'salary_currency',
    'application_url', 'application_email', 'posted_at', 'expires_at', 'raw_data', 'is_active',
])]
class JobListing extends Model
{
    /** @use HasFactory<JobListingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'expires_at' => 'datetime',
            'raw_data' => 'array',
            'is_active' => 'boolean',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
        ];
    }

    /**
     * @return HasMany<JobMatch, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(JobMatch::class);
    }

    /**
     * The match record for a single user (eager-load with a constrained closure).
     *
     * @return HasOne<JobMatch, $this>
     */
    public function userMatch(): HasOne
    {
        return $this->hasOne(JobMatch::class);
    }

    /**
     * @return HasMany<ApplicationDraft, $this>
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(ApplicationDraft::class);
    }
}
