<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\UserNotification;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserNotificationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UserNotification');
    }

    public function view(AuthUser $authUser, UserNotification $userNotification): bool
    {
        return $authUser->can('View:UserNotification');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:UserNotification');
    }

    public function update(AuthUser $authUser, UserNotification $userNotification): bool
    {
        return $authUser->can('Update:UserNotification');
    }

    public function delete(AuthUser $authUser, UserNotification $userNotification): bool
    {
        return $authUser->can('Delete:UserNotification');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UserNotification');
    }

    public function restore(AuthUser $authUser, UserNotification $userNotification): bool
    {
        return $authUser->can('Restore:UserNotification');
    }

    public function forceDelete(AuthUser $authUser, UserNotification $userNotification): bool
    {
        return $authUser->can('ForceDelete:UserNotification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:UserNotification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:UserNotification');
    }

    public function replicate(AuthUser $authUser, UserNotification $userNotification): bool
    {
        return $authUser->can('Replicate:UserNotification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:UserNotification');
    }

}