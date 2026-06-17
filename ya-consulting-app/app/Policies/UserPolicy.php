<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can manage other users.
     */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('manage users');
    }
}
