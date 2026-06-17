<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view expenses');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Expense $expense): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            return $expense->project?->created_by === $user->id;
        }

        return $user->hasPermissionTo('view expenses');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create expenses');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Expense $expense): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            return $expense->project?->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Expense $expense): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('chef_projet')) {
            return $expense->project?->created_by === $user->id;
        }

        return false;
    }
}
