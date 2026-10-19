<?php

namespace App\Policies;

use App\Models\ApplicationDraft;
use App\Models\User;

class ApplicationDraftPolicy
{
    /**
     * Drafts are private to the user who generated them.
     */
    public function view(User $user, ApplicationDraft $draft): bool
    {
        return $draft->user_id === $user->id;
    }

    public function update(User $user, ApplicationDraft $draft): bool
    {
        return $draft->user_id === $user->id;
    }
}
