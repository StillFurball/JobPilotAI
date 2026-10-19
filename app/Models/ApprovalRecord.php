<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit log of approve / reject decisions.
 *
 * @property int $id
 * @property int $application_draft_id
 * @property int $user_id
 * @property string $action approved|rejected
 * @property string $approved_content_hash
 * @property string|null $notes
 */
#[Fillable(['application_draft_id', 'user_id', 'action', 'approved_content_hash', 'notes'])]
class ApprovalRecord extends Model
{
    /**
     * @return BelongsTo<ApplicationDraft, $this>
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(ApplicationDraft::class, 'application_draft_id');
    }
}
