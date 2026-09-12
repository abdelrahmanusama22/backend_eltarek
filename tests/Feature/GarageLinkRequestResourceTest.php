<?php

namespace Tests\Feature;

use App\Filament\Resources\GarageLinkRequests\Pages\ListGarageLinkRequests;
use App\Filament\Resources\GarageLinkRequests\Pages\ViewGarageLinkRequest;
use App\Models\Brand;
use App\Models\GarageCar;
use App\Models\GarageLinkRequest;
use App\Models\Trim;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GarageLinkRequestResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_view_garage_link_request_details(): void
    {
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'admin@eltarek.com',
            'is_admin' => true,
        ]);
        $admin->assignRole($superAdminRole);
        $this->actingAs($admin);

        $customer = User::factory()->create([
            'name' => 'Ahmed Mahmoud',
            'phone' => '+201099887766',
            'email' => 'ahmed.m@example.com',
        ]);

        $request = GarageLinkRequest::create([
            'user_id' => $customer->id,
            'identifier' => 'VIN-EGY-2025-9988',
            'car_name' => 'Tucson Turbo 2025',
            'status' => 'pending',
        ]);

        Livewire::test(ViewGarageLinkRequest::class, ['record' => $request->id])
            ->assertSuccessful()
            ->assertSee('Ahmed Mahmoud')
            ->assertSee('+201099887766')
            ->assertSee('VIN-EGY-2025-9988')
            ->assertSee('Tucson Turbo 2025');
    }

    public function test_admin_can_approve_request_and_link_vehicle_to_customer(): void
    {
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'admin@eltarek.com',
            'is_admin' => true,
        ]);
        $admin->assignRole($superAdminRole);
        $this->actingAs($admin);

        $customer = User::factory()->create([
            'name' => 'Youssef Nabil',
            'phone' => '+201011223344',
            'email' => 'youssef@example.com',
        ]);

        $brand = Brand::create(['name' => 'Hyundai', 'name_ar' => 'هيونداي', 'tier' => 'standard']);
        $vehicle = Vehicle::create([
            'brand_id' => $brand->id,
            'model' => 'Tucson',
            'model_ar' => 'توسان',
            'year' => 2025,
            'category' => 'SUV',
            'starting_price_egp' => 1750000,
            'image_url' => 'https://example.com/tucson.jpg',
            'engine_summary' => '1.6L Turbo',
            'active' => true,
        ]);
        $trim = Trim::create([
            'vehicle_id' => $vehicle->id,
            'name' => 'Highline',
            'name_ar' => 'الفئة العليا',
            'price_egp' => 1850000,
            'subtitle' => '1.6L Turbo • DCT',
            'highlights' => [],
            'specs' => [],
            'metrics' => [],
            'gallery' => [],
            'active' => true,
        ]);

        $request = GarageLinkRequest::create([
            'user_id' => $customer->id,
            'identifier' => 'VIN-TUC-7788',
            'car_name' => 'Tucson Highline',
            'status' => 'pending',
        ]);

        Livewire::test(ViewGarageLinkRequest::class, ['record' => $request->id])
            ->assertSuccessful()
            ->callAction('approve', [
                'vehicle_id' => $vehicle->id,
                'trim_id' => $trim->id,
                'name' => 'My Tucson 2025',
                'warranty_expires_at' => '2028-12-31',
                'next_service_at' => '2026-06-30',
            ])
            ->assertHasNoActionErrors();

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertSame($admin->id, $request->reviewed_by);
        $this->assertNotNull($request->reviewed_at);

        // Verify GarageCar was linked to customer
        $car = GarageCar::where('tracking_code', 'VIN-TUC-7788')->first();
        $this->assertNotNull($car);
        $this->assertSame($customer->id, $car->user_id);
        $this->assertSame('My Tucson 2025', $car->name);

        // Verify customer received notification
        $notification = UserNotification::where('user_id', $customer->id)->first();
        $this->assertNotNull($notification);
        $this->assertSame('garage', $notification->type);
    }

    public function test_admin_can_reject_request_with_notes(): void
    {
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'admin@eltarek.com',
            'is_admin' => true,
        ]);
        $admin->assignRole($superAdminRole);
        $this->actingAs($admin);

        $customer = User::factory()->create([
            'name' => 'Hassan Tamer',
            'email' => 'hassan@example.com',
        ]);

        $request = GarageLinkRequest::create([
            'user_id' => $customer->id,
            'identifier' => 'INVALID-VIN-000',
            'car_name' => 'Unknown Car',
            'status' => 'pending',
        ]);

        Livewire::test(ViewGarageLinkRequest::class, ['record' => $request->id])
            ->assertSuccessful()
            ->callAction('reject', [
                'admin_notes' => 'Chassis number does not match our dealership sales records.',
            ])
            ->assertHasNoActionErrors();

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame('Chassis number does not match our dealership sales records.', $request->admin_notes);
        $this->assertSame($admin->id, $request->reviewed_by);

        // Verify rejection notification was sent
        $notification = UserNotification::where('user_id', $customer->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('dealership sales records', $notification->body);
    }
}
