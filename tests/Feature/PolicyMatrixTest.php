<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PolicyMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_policies_deny_by_default_and_honor_explicit_permission(): void
    {
        $resources = [
            'Activity', 'Booking', 'Branch', 'Brand', 'City', 'GarageCar',
            'LoyaltyRule', 'OtpCode', 'Redemption', 'Reward', 'Role', 'Trim',
            'User', 'UserNotification', 'Vehicle',
        ];
        $user = User::factory()->create();

        foreach ($resources as $resource) {
            $policyClass = "App\\Policies\\{$resource}Policy";
            $policy = app($policyClass);
            $permission = "ViewAny:{$resource}";

            $this->assertFalse($policy->viewAny($user), "{$resource} must deny by default");
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
            $user->unsetRelation('permissions');
            $this->assertTrue($policy->viewAny($user), "{$resource} must honor {$permission}");
        }
    }
}
