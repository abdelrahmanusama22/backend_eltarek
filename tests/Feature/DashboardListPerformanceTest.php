<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardListPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_list_pages_render_with_live_pagination_controls(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($admin);

        Livewire::test(ListBrands::class)->assertSuccessful()->assertSee('brands');
        Livewire::test(ListBookings::class)->assertSuccessful()->assertSee('bookings');
    }

    public function test_brand_page_has_bounded_queries_and_real_pagination_at_1_50_200_records(): void
    {
        foreach ([1, 50, 200] as $count) {
            $already = DB::table('brands')->count();
            DB::table('brands')->insert(array_map(fn (int $id): array => [
                'id' => $id, 'name' => 'Brand '.$id, 'name_ar' => 'Brand '.$id,
            ], range($already + 1, $count)));

            DB::enableQueryLog();
            DB::flushQueryLog();
            $page = (new ListBrands)->brandsQuery()->paginate(20);
            foreach ($page as $brand) {
                $this->assertIsInt($brand->vehicles_count);
            }
            $this->assertLessThanOrEqual(3, count(DB::getQueryLog()));
            DB::disableQueryLog();
            $this->assertSame($count, $page->total());
            $this->assertCount(min($count, 20), $page->items());
        }
    }

    public function test_booking_page_eager_loads_relations_with_bounded_queries_at_1_50_200_records(): void
    {
        $user = User::factory()->create();
        $brandId = DB::table('brands')->insertGetId(['name' => 'Test Brand', 'name_ar' => 'ماركة']);
        $vehicleId = DB::table('vehicles')->insertGetId([
            'brand_id' => $brandId, 'model' => 'Test Car', 'model_ar' => 'سيارة',
            'year' => 2026, 'category' => 'Sedan', 'starting_price_egp' => 100,
        ]);
        $trimId = DB::table('trims')->insertGetId([
            'vehicle_id' => $vehicleId, 'name' => 'Trim', 'name_ar' => 'فئة',
            'price_egp' => 100, 'highlights' => '[]', 'specs' => '{}',
            'metrics' => '{}', 'gallery' => '[]',
        ]);
        $branchId = DB::table('branches')->insertGetId([
            'name' => 'Branch', 'name_ar' => 'فرع', 'address' => 'Address',
            'address_ar' => 'عنوان', 'phone' => '19022', 'hours' => '9-5',
            'hours_ar' => '9-5', 'lat' => 30, 'lng' => 31,
        ]);

        foreach ([1, 50, 200] as $count) {
            $already = DB::table('bookings')->count();
            DB::table('bookings')->insert(array_map(fn (int $id): array => [
                'user_id' => $user->id, 'trim_id' => $trimId, 'branch_id' => $branchId,
                'time' => '10:00 AM', 'reference' => 'BK-'.$id,
                'created_at' => now(), 'updated_at' => now(),
            ], range($already + 1, $count)));

            DB::enableQueryLog();
            DB::flushQueryLog();
            $page = (new ListBookings)->bookingsQuery()->paginate(20);
            foreach ($page as $booking) {
                $this->assertNotNull($booking->user?->name);
                $this->assertNotNull($booking->trim?->name);
                $this->assertNotNull($booking->branch?->name);
            }
            $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
            DB::disableQueryLog();
            $this->assertSame($count, $page->total());
            $this->assertCount(min($count, 20), $page->items());
        }
    }
}
