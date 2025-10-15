<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "updated" event to log role changes.
     */
    public function updated(User $user): void
    {
        // Check if roles were changed
        if ($user->isDirty('roles')) {
            $oldRoles = $user->getOriginal('roles') ?? [];
            $newRoles = $user->roles ?? [];

            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_roles' => $oldRoles,
                    'new_roles' => $newRoles,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Roles actualizados para usuario: ' . $user->name);
        }
    }

    /**
     * Log when roles are attached to a user.
     */
    public static function roleAttached(User $user, $role): void
    {
        $roleName = is_object($role) ? $role->name : $role;

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'role' => $roleName,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Rol '{$roleName}' asignado a usuario: " . $user->name);
    }

    /**
     * Log when roles are detached from a user.
     */
    public static function roleDetached(User $user, $role): void
    {
        $roleName = is_object($role) ? $role->name : $role;

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'role' => $roleName,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Rol '{$roleName}' removido de usuario: " . $user->name);
    }
}
