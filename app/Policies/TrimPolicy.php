<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Trim;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrimPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Trim');
    }

    public function view(AuthUser $authUser, Trim $trim): bool
    {
        return $authUser->can('View:Trim');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Trim');
    }

    public function update(AuthUser $authUser, Trim $trim): bool
    {
        return $authUser->can('Update:Trim');
    }

    public function delete(AuthUser $authUser, Trim $trim): bool
    {
        return $authUser->can('Delete:Trim');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Trim');
    }

    public function restore(AuthUser $authUser, Trim $trim): bool
    {
        return $authUser->can('Restore:Trim');
    }

    public function forceDelete(AuthUser $authUser, Trim $trim): bool
    {
        return $authUser->can('ForceDelete:Trim');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Trim');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Trim');
    }

    public function replicate(AuthUser $authUser, Trim $trim): bool
    {
        return $authUser->can('Replicate:Trim');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Trim');
    }

}