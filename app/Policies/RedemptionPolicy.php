<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Redemption;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RedemptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Redemption');
    }

    public function view(AuthUser $authUser, Redemption $redemption): bool
    {
        return $authUser->can('View:Redemption');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Redemption');
    }

    public function update(AuthUser $authUser, Redemption $redemption): bool
    {
        return $authUser->can('Update:Redemption');
    }

    public function delete(AuthUser $authUser, Redemption $redemption): bool
    {
        return $authUser->can('Delete:Redemption');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Redemption');
    }

    public function restore(AuthUser $authUser, Redemption $redemption): bool
    {
        return $authUser->can('Restore:Redemption');
    }

    public function forceDelete(AuthUser $authUser, Redemption $redemption): bool
    {
        return $authUser->can('ForceDelete:Redemption');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Redemption');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Redemption');
    }

    public function replicate(AuthUser $authUser, Redemption $redemption): bool
    {
        return $authUser->can('Replicate:Redemption');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Redemption');
    }
}
