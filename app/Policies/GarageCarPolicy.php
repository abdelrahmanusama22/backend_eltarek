<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GarageCar;
use Illuminate\Auth\Access\HandlesAuthorization;

class GarageCarPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GarageCar');
    }

    public function view(AuthUser $authUser, GarageCar $garageCar): bool
    {
        return $authUser->can('View:GarageCar');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GarageCar');
    }

    public function update(AuthUser $authUser, GarageCar $garageCar): bool
    {
        return $authUser->can('Update:GarageCar');
    }

    public function delete(AuthUser $authUser, GarageCar $garageCar): bool
    {
        return $authUser->can('Delete:GarageCar');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GarageCar');
    }

    public function restore(AuthUser $authUser, GarageCar $garageCar): bool
    {
        return $authUser->can('Restore:GarageCar');
    }

    public function forceDelete(AuthUser $authUser, GarageCar $garageCar): bool
    {
        return $authUser->can('ForceDelete:GarageCar');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GarageCar');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GarageCar');
    }

    public function replicate(AuthUser $authUser, GarageCar $garageCar): bool
    {
        return $authUser->can('Replicate:GarageCar');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:GarageCar');
    }

}