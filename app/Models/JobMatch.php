<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $job_listing_id
 * @property float $score
 * @property float|null $skill_score
 * @property float|null $experience_score
 * @property float|null $education_score
 * @property float|null $location_score
 * @property float|null $keyword_score
 * @property float|null $employment_type_score
 * @property string|null $explanation
 * @property array<int, string>|null $missing_requirements
 * @property array<int, string>|null $matched_skills
 * @property string|null $llm_analysis
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JobListing $jobListing
 */
#[Fillable([
    'user_id', 'job_listing_id', 'score', 'skill_score', 'experience_score', 'education_score',
    'location_score', 'keyword_score', 'employment_type_score', 'explanation',
    'missing_requirements', 'matched_skills', 'llm_analysis',
])]
class JobMatch extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
            'skill_score' => 'float',
            'experience_score' => 'float',
            'education_score' => 'float',
            'location_score' => 'float',
            'keyword_score' => 'float',
            'employment_type_score' => 'float',
            'missing_requirements' => 'array',
            'matched_skills' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<JobListing, $this>
     */
    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }
}
