<?php

namespace App\Policies;

use App\Models\GarageLinkRequest;
use Illuminate\Foundation\Auth\User as AuthUser;

class GarageLinkRequestPolicy
{
    public function viewAny(AuthUser $user): bool { return $user->can('ViewAny:GarageLinkRequest') || $user->can('ViewAny:GarageCar'); }
    public function view(AuthUser $user, GarageLinkRequest $request): bool { return $user->can('View:GarageLinkRequest') || $user->can('View:GarageCar'); }
    public function update(AuthUser $user, GarageLinkRequest $request): bool { return $user->can('Update:GarageLinkRequest') || $user->can('Update:GarageCar'); }
}
