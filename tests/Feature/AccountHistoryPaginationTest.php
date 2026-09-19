<?php

namespace Tests\Feature;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountHistoryPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_points_history_uses_bounded_cursor_pages_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (range(1, 5) as $index) {
            PointTransaction::create(['user_id' => $user->id, 'points' => $index, 'type' => 'earned', 'description' => 'Test']);
        }
        PointTransaction::create(['user_id' => $other->id, 'points' => 999, 'type' => 'earned', 'description' => 'Private']);
        Sanctum::actingAs($user);

        $first = $this->getJson('/api/v1/profile/points-history?limit=2')->assertOk();
        $first->assertJsonCount(2, 'data.items');
        $cursor = $first->json('data.next_cursor');
        $this->assertNotEmpty($cursor);

        $second = $this->getJson('/api/v1/profile/points-history?limit=2&cursor='.urlencode($cursor))->assertOk();
        $second->assertJsonCount(2, 'data.items');
        $third = $this->getJson('/api/v1/profile/points-history?limit=2&cursor='.urlencode($second->json('data.next_cursor')))->assertOk();
        $third->assertJsonCount(1, 'data.items')->assertJsonPath('data.next_cursor', null);

        $ids = array_merge(
            array_column($first->json('data.items'), 'id'),
            array_column($second->json('data.items'), 'id'),
            array_column($third->json('data.items'), 'id'),
        );
        $this->assertCount(5, array_unique($ids));
    }
}
