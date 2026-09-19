<?php

namespace Tests\Feature;

use App\Models\GarageCar;
use App\Models\GarageServiceRecord;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountCollectionsPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_garage_preview_is_bounded_and_service_history_has_own_cursor(): void
    {
        $user = User::factory()->create();
        $car = GarageCar::create(['user_id' => $user->id, 'tracking_code' => 'TEST-CAR-123', 'name' => 'Test']);
        foreach (range(1, 6) as $index) {
            GarageServiceRecord::create(['garage_car_id' => $car->id, 'type' => "Service {$index}", 'serviced_at' => now()->toDateString()]);
        }
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile/garage')->assertOk()
            ->assertJsonCount(3, 'data.items.0.service_history')
            ->assertJsonPath('data.items.0.service_history_total', 6);
        $first = $this->getJson("/api/v1/profile/garage/{$car->id}/service-records?limit=2")->assertOk()
            ->assertJsonCount(2, 'data.items');
        $this->getJson("/api/v1/profile/garage/{$car->id}/service-records?limit=2&cursor=".urlencode($first->json('data.next_cursor')))
            ->assertOk()->assertJsonCount(2, 'data.items');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/v1/profile/garage/{$car->id}/service-records")->assertNotFound();
    }

    public function test_rewards_are_bounded_and_later_pages_remain_available(): void
    {
        Sanctum::actingAs(User::factory()->create());
        foreach (range(1, 5) as $index) {
            Reward::create([
                'name' => "Reward {$index}", 'name_ar' => 'مكافأة',
                'description' => 'Test', 'description_ar' => 'اختبار',
                'points_cost' => 100, 'active' => true,
            ]);
        }

        $first = $this->getJson('/api/v1/profile/rewards?limit=2')->assertOk()->assertJsonCount(2, 'data.items');
        $second = $this->getJson('/api/v1/profile/rewards?limit=2&cursor='.urlencode($first->json('data.next_cursor')))
            ->assertOk()->assertJsonCount(2, 'data.items');
        $third = $this->getJson('/api/v1/profile/rewards?limit=2&cursor='.urlencode($second->json('data.next_cursor')))
            ->assertOk()->assertJsonCount(1, 'data.items');
        $this->assertNull($third->json('data.next_cursor'));
    }
}
