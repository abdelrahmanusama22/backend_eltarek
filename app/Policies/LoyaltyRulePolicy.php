<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LoyaltyRule;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LoyaltyRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $user): bool
    {
        return $user->can('ViewAny:LoyaltyRule');
    }

    public function view(AuthUser $user, LoyaltyRule $rule): bool
    {
        return $user->can('View:LoyaltyRule');
    }

    public function create(AuthUser $user): bool
    {
        return $user->can('Create:LoyaltyRule');
    }

    public function update(AuthUser $user, LoyaltyRule $rule): bool
    {
        return $user->can('Update:LoyaltyRule');
    }

    public function delete(AuthUser $user, LoyaltyRule $rule): bool
    {
        return $user->can('Delete:LoyaltyRule');
    }

    public function deleteAny(AuthUser $user): bool
    {
        return $user->can('DeleteAny:LoyaltyRule');
    }

    public function restore(AuthUser $user, LoyaltyRule $rule): bool
    {
        return $user->can('Restore:LoyaltyRule');
    }

    public function restoreAny(AuthUser $user): bool
    {
        return $user->can('RestoreAny:LoyaltyRule');
    }

    public function forceDelete(AuthUser $user, LoyaltyRule $rule): bool
    {
        return $user->can('ForceDelete:LoyaltyRule');
    }

    public function forceDeleteAny(AuthUser $user): bool
    {
        return $user->can('ForceDeleteAny:LoyaltyRule');
    }

    public function replicate(AuthUser $user, LoyaltyRule $rule): bool
    {
        return $user->can('Replicate:LoyaltyRule');
    }

    public function reorder(AuthUser $user): bool
    {
        return $user->can('Reorder:LoyaltyRule');
    }
}
