<?php

namespace App\Models;

use Database\Factories\CandidateProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $location
 * @property string|null $linkedin_url
 * @property string|null $github_url
 * @property string|null $portfolio_url
 * @property string|null $professional_summary
 * @property array<int, mixed>|null $skills
 * @property array<int, array<string, mixed>>|null $education
 * @property array<int, array<string, mixed>>|null $experience
 * @property array<int, array<string, mixed>>|null $projects
 * @property array<int, array<string, mixed>>|null $certifications
 * @property array<int, string>|null $preferred_locations
 * @property array<int, string>|null $preferred_countries
 * @property array<int, string>|null $preferred_employment_types
 * @property array<int, string>|null $preferred_keywords
 * @property int|null $min_salary
 * @property int|null $max_salary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'full_name', 'email', 'phone', 'location', 'linkedin_url', 'github_url', 'portfolio_url',
    'professional_summary', 'skills', 'education', 'experience', 'projects', 'certifications',
    'preferred_locations', 'preferred_countries', 'preferred_employment_types', 'preferred_keywords',
    'min_salary', 'max_salary',
])]
class CandidateProfile extends Model
{
    /** @use HasFactory<CandidateProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'education' => 'array',
            'experience' => 'array',
            'projects' => 'array',
            'certifications' => 'array',
            'preferred_locations' => 'array',
            'preferred_countries' => 'array',
            'preferred_employment_types' => 'array',
            'preferred_keywords' => 'array',
            'min_salary' => 'integer',
            'max_salary' => 'integer',
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
     * Skills normalised to a flat list of names (skills may be strings or {name: ...} objects).
     *
     * @return array<int, string>
     */
    public function skillNames(): array
    {
        return collect($this->skills ?? [])
            ->map(fn (mixed $skill): string => is_array($skill) ? (string) ($skill['name'] ?? '') : (string) $skill)
            ->filter()
            ->values()
            ->all();
    }
}
