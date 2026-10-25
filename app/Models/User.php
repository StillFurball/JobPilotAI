<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CandidateProfile|null $candidateProfile
 * @property-read MailAccount|null $mailAccount
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The user's JobPilot candidate profile (one per user).
     *
     * @return HasOne<CandidateProfile, $this>
     */
    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    /**
     * The mailbox applications are sent from (one per user).
     *
     * @return HasOne<MailAccount, $this>
     */
    public function mailAccount(): HasOne
    {
        return $this->hasOne(MailAccount::class);
    }

    /**
     * Get the candidate profile, creating a blank one seeded from the account on first use.
     */
    public function profileOrCreate(): CandidateProfile
    {
        return $this->candidateProfile()->firstOrCreate([], [
            'full_name' => $this->name,
            'email' => $this->email,
            'skills' => [],
            'education' => [],
            'experience' => [],
            'projects' => [],
            'certifications' => [],
            'preferred_locations' => [],
            'preferred_countries' => ['id'],
            'preferred_employment_types' => ['full_time'],
            'preferred_keywords' => [],
        ]);
    }

    /**
     * @return HasMany<JobMatch, $this>
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatch::class);
    }

    /**
     * @return HasMany<ApplicationDraft, $this>
     */
    public function applicationDrafts(): HasMany
    {
        return $this->hasMany(ApplicationDraft::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return HasMany<WorkflowRun, $this>
     */
    public function workflowRuns(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }
}
