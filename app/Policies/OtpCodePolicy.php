<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OtpCode;
use Illuminate\Auth\Access\HandlesAuthorization;

class OtpCodePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OtpCode');
    }

    public function view(AuthUser $authUser, OtpCode $otpCode): bool
    {
        return $authUser->can('View:OtpCode');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OtpCode');
    }

    public function update(AuthUser $authUser, OtpCode $otpCode): bool
    {
        return $authUser->can('Update:OtpCode');
    }

    public function delete(AuthUser $authUser, OtpCode $otpCode): bool
    {
        return $authUser->can('Delete:OtpCode');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OtpCode');
    }

    public function restore(AuthUser $authUser, OtpCode $otpCode): bool
    {
        return $authUser->can('Restore:OtpCode');
    }

    public function forceDelete(AuthUser $authUser, OtpCode $otpCode): bool
    {
        return $authUser->can('ForceDelete:OtpCode');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OtpCode');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OtpCode');
    }

    public function replicate(AuthUser $authUser, OtpCode $otpCode): bool
    {
        return $authUser->can('Replicate:OtpCode');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OtpCode');
    }

}