<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $application_draft_id
 * @property int $job_listing_id
 * @property string|null $sent_content_hash
 * @property string $status approved|sending|sent|failed|uncertain|rejected
 * @property string|null $provider_message_id
 * @property string|null $error_message
 * @property Carbon|null $approved_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ApplicationDraft $draft
 * @property-read JobListing $jobListing
 */
#[Fillable([
    'user_id', 'application_draft_id', 'job_listing_id', 'sent_content_hash', 'status',
    'provider_message_id', 'error_message', 'approved_at', 'sent_at',
])]
class Application extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
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
     * @return BelongsTo<ApplicationDraft, $this>
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(ApplicationDraft::class, 'application_draft_id');
    }

    /**
     * @return BelongsTo<JobListing, $this>
     */
    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }
}
