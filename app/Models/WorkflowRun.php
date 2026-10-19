<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status pending|running|completed|failed
 * @property string|null $stage
 * @property array<string, mixed>|null $search_params
 * @property int $jobs_discovered
 * @property int $jobs_new
 * @property int $jobs_matched
 * @property string|null $error_message failure reason, or source warnings on a completed run
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read User $user
 */
#[Fillable([
    'user_id', 'status', 'stage', 'search_params', 'jobs_discovered', 'jobs_new', 'jobs_matched',
    'error_message', 'started_at', 'completed_at',
])]
class WorkflowRun extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'search_params' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }
}
