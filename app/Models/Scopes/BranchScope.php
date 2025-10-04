<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip scoping if no authenticated user
        if (!auth()->check()) {
            return;
        }

        $user = auth()->user();

        // Skip scoping for super admins
        if ($user->isSuperAdmin()) {
            return;
        }

        // Skip scoping if user has permission to view all branches
        if ($user->can('view_all_branches')) {
            return;
        }

        // Apply branch filter
        if ($user->branch_id) {
            $builder->where($model->getTable() . '.branch_id', $user->branch_id);
        }
    }
}
