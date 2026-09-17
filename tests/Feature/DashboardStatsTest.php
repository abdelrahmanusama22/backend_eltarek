<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatsOverview;
use App\Models\GarageLinkRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_only_active_vehicles_and_pending_garage_links(): void
    {
        $brandId = \DB::table('brands')->insertGetId([
            'name' => 'Test Brand', 'name_ar' => 'اختبار', 'active' => true,
        ]);
        foreach ([true, false] as $index => $active) {
            Vehicle::create([
                'brand_id' => $brandId, 'model' => "Car {$index}", 'model_ar' => 'سيارة',
                'year' => 2026, 'category' => 'Sedan', 'starting_price_egp' => 100,
                'active' => $active,
            ]);
        }
        $user = User::factory()->create();
        GarageLinkRequest::create(['user_id' => $user->id, 'identifier' => 'TEST-CAR-1001', 'status' => 'pending']);
        GarageLinkRequest::create(['user_id' => $user->id, 'identifier' => 'TEST-CAR-1002', 'status' => 'approved']);

        $stats = (new \ReflectionMethod(StatsOverview::class, 'getStats'))->invoke(new StatsOverview);
        $this->assertSame('Active Vehicles', $stats[2]->getLabel());
        $this->assertSame(1, $stats[2]->getValue());
        $this->assertSame('Pending Garage Link Requests', $stats[3]->getLabel());
        $this->assertSame(1, $stats[3]->getValue());
    }
}
