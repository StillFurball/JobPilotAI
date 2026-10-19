<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Applications are private to the user who approved them.
     */
    public function view(User $user, Application $application): bool
    {
        return $application->user_id === $user->id;
    }

    public function send(User $user, Application $application): bool
    {
        return $application->user_id === $user->id;
    }
}
