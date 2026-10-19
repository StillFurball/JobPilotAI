<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A tailored CV + application email awaiting human review.
 *
 * @property int $id
 * @property int $user_id
 * @property int $job_listing_id
 * @property int $version
 * @property string|null $recipient_email
 * @property bool $recipient_verified
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $cv_file_path
 * @property string|null $cv_file_hash
 * @property string|null $content_hash
 * @property string $status draft|awaiting_approval|approved|rejected|sent|failed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read JobListing $jobListing
 * @property-read Application|null $application
 */
#[Fillable([
    'user_id', 'job_listing_id', 'version', 'recipient_email', 'recipient_verified', 'subject',
    'body', 'cv_file_path', 'cv_file_hash', 'content_hash', 'status',
])]
#[Hidden(['cv_file_path'])]
class ApplicationDraft extends Model
{
    /** Statuses in which the draft is still waiting for the user's decision. */
    public const PENDING_STATUSES = ['draft', 'awaiting_approval'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipient_verified' => 'boolean',
            'version' => 'integer',
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

    /**
     * @return HasMany<ApprovalRecord, $this>
     */
    public function approvalRecords(): HasMany
    {
        return $this->hasMany(ApprovalRecord::class);
    }

    /**
     * @return HasOne<Application, $this>
     */
    public function application(): HasOne
    {
        return $this->hasOne(Application::class);
    }

    /**
     * Bind approval to the exact content: recipient, subject, body and attached CV.
     */
    public function computeContentHash(): string
    {
        $payload = [
            'body' => $this->body ?? '',
            'cv_file_hash' => $this->cv_file_hash ?? '',
            'recipient_email' => $this->recipient_email ?? '',
            'subject' => $this->subject ?? '',
        ];

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function refreshContentHash(): void
    {
        $this->content_hash = $this->computeContentHash();
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING_STATUSES, true);
    }
}
