<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payment');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->can('view_payment');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_payment');
    }

    /**
     * Determine whether the user can update the model.
     * Cajero can only edit payments created today
     */
    public function update(User $user, Payment $payment): bool
    {
        // Admin and Gerente can edit any payment
        if ($user->hasAnyRole(['Admin', 'Gerente', 'super_admin'])) {
            return $user->can('update_payment');
        }

        // Cajero can only edit payments created today
        if ($user->hasRole('Cajero')) {
            $isCreatedToday = $payment->created_at->isToday();
            return $user->can('update_payment') && $isCreatedToday;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('delete_payment');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payment');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->can('force_delete_payment');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_payment');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Payment $payment): bool
    {
        return $user->can('restore_payment');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_payment');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Payment $payment): bool
    {
        return $user->can('replicate_payment');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_payment');
    }
}
