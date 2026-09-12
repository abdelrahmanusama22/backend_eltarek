<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\AppSetting;
use App\Models\OtpCode;
use App\Models\Trim;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Reward;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Carbon\Carbon;

class ApiSecurityAndContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_sync_webhook_rejects_unsigned_requests(): void
    {
        $this->postJson('/api/webhook/trigger-catalog-sync')->assertForbidden();
    }

    public function test_every_api_response_has_a_request_id(): void
    {
        $response = $this->getJson('/api/v1/cities')->assertOk();

        $this->assertNotEmpty($response->headers->get('X-Request-Id'));
    }

    public function test_bootstrap_catalog_sections_are_json_arrays(): void
    {
        $this->catalogRecords();

        $response = $this->getJson('/api/v1/bootstrap')->assertOk();
        $response->assertJsonStructure([
            'data' => ['brands', 'vehicles', 'trims', 'cities', 'branches'],
        ]);
        $this->assertIsArray($response->json('data.brands'));
        $this->assertIsArray($response->json('data.vehicles'));
        $this->assertIsArray($response->json('data.trims'));
        $this->assertArrayHasKey('id', $response->json('data.brands.0'));
    }

    public function test_catalog_endpoints_are_versioned_and_paginated(): void
    {
        $this->catalogRecords();

        $this->getJson('/api/v1/app-config')
            ->assertOk()
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonStructure(['data' => ['catalog_version']]);
        $this->getJson('/api/v1/catalog/vehicles?limit=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['catalog_version', 'next_cursor', 'has_more']]);
    }

    public function test_verify_otp_idempotency_replays_the_original_success(): void
    {
        OtpCode::create([
            'phone' => '+201001234568',
            'code_hash' => Hash::make('1234'),
            'expires_at' => now()->addMinutes(5),
        ]);
        $headers = ['Idempotency-Key' => 'same-otp-operation-0001'];
        $payload = ['phone' => '+201001234568', 'otp' => '1234'];

        $first = $this->postJson('/api/v1/auth/verify-otp', $payload, $headers)->assertOk();
        $second = $this->postJson('/api/v1/auth/verify-otp', $payload, $headers)->assertOk();

        $this->assertSame($first->json('data.access_token'), $second->json('data.access_token'));
        $this->assertSame(1, User::where('phone', '+201001234568')->firstOrFail()->tokens()->count());
    }

    public function test_user_can_register_and_login_with_any_valid_email(): void
    {
        $payload = [
            'name' => 'Email User',
            'email' => 'person@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $this->postJson('/api/v1/auth/email/register', $payload, [
            'Idempotency-Key' => 'email-register-test-0001',
        ])->assertOk()->assertJsonStructure(['data' => ['access_token']]);

        $this->postJson('/api/v1/auth/email/login', [
            'email' => 'person@example.com',
            'password' => 'SecurePass123!',
        ])->assertOk()->assertJsonStructure(['data' => ['access_token']]);

        $this->postJson('/api/v1/auth/email/login', [
            'email' => 'person@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_health_endpoint_reports_dependencies(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.cache', 'ok');
    }

    public function test_authenticated_user_can_upload_a_valid_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
        ])->assertOk();

        $this->assertStringStartsWith('/storage/avatars/', $response->json('data.avatar_url'));
        Storage::disk('public')->assertExists($user->fresh()->avatar_url);
    }

    public function test_customer_can_submit_a_unique_garage_link_request(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);
        $payload = ['identifier' => 'VIN-ABC123456', 'car_name' => 'My Car'];
        $headers = ['Idempotency-Key' => 'garage-link-test-0001'];

        $this->postJson('/api/v1/profile/garage-link-requests', $payload, $headers)
            ->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->getJson('/api/v1/profile/garage-link-requests')
            ->assertOk()->assertJsonCount(1, 'data.items');
    }

    public function test_customer_can_cancel_only_their_pending_garage_request(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $request = \App\Models\GarageLinkRequest::create(['user_id' => $owner->id, 'identifier' => 'VIN-CANCEL-1']);

        Sanctum::actingAs($other);
        $this->deleteJson("/api/v1/profile/garage-link-requests/{$request->id}")->assertNotFound();
        Sanctum::actingAs($owner);
        $this->deleteJson("/api/v1/profile/garage-link-requests/{$request->id}")->assertOk();
        $this->assertDatabaseMissing('garage_link_requests', ['id' => $request->id]);
    }

    public function test_garage_approval_cannot_transfer_a_vehicle_between_customers(): void
    {
        $owner = User::factory()->create();
        $claimant = User::factory()->create();
        \App\Models\GarageCar::create(['user_id' => $owner->id, 'tracking_code' => 'VIN-OWNED-1', 'name' => 'Owned Car']);
        $request = \App\Models\GarageLinkRequest::create(['user_id' => $claimant->id, 'identifier' => 'VIN-OWNED-1']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\GarageLinkService::class)->approve($request, $owner->id, ['name' => 'Wrong Car']);
    }

    public function test_framework_validation_errors_follow_the_api_envelope(): void
    {
        $this->postJson('/api/v1/auth/send-otp', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonStructure(['error' => ['fields', 'request_id']]);
    }

    public function test_all_personal_routes_reject_guests_with_structured_401(): void
    {
        foreach ([
            '/api/v1/profile',
            '/api/v1/profile/favorites',
            '/api/v1/profile/garage',
            '/api/v1/profile/points-history',
            '/api/v1/profile/rewards',
            '/api/v1/profile/vip/benefits',
            '/api/v1/notifications',
            '/api/v1/test-drives',
        ] as $uri) {
            $this->getJson($uri)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'unauthenticated');
        }
    }

    public function test_otp_can_only_be_consumed_once_and_token_expires(): void
    {
        OtpCode::create([
            'phone' => '+201001234567',
            'code_hash' => Hash::make('1234'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $first = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+201001234567',
            'otp' => '1234',
        ], ['Idempotency-Key' => 'otp-first-attempt-0001'])->assertOk();

        $this->assertNotEmpty($first->json('data.access_token'));
        $this->assertNotNull(User::where('phone', '+201001234567')->firstOrFail()->tokens()->first()->expires_at);

        $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+201001234567',
            'otp' => '1234',
        ], ['Idempotency-Key' => 'otp-replay-attempt-002'])->assertStatus(400);
    }

    public function test_booking_rejects_a_time_not_published_by_slot_service(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);
        [$trim, $branch] = $this->catalogRecords();

        $this->postJson('/api/v1/test-drives', [
            'trim_id' => $trim->id,
            'branch_id' => $branch->id,
            'date' => now()->toDateString(),
            'day_label' => 'Today',
            'time' => '03:17 AM',
        ], ['Idempotency-Key' => 'invalid-slot-test-0001'])->assertStatus(422);
    }

    public function test_test_drive_slots_follow_dashboard_schedule_and_notice_period(): void
    {
        Carbon::setTestNow('2026-09-13 09:30:00'); // Sunday
        AppSetting::put('test_drive_times', [
            'days_ahead' => 2,
            'min_notice_minutes' => 60,
            'sun' => ['10:00 AM', '11:00 AM'],
            'mon' => [],
        ]);

        $this->getJson('/api/v1/test-drives/slots')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-09-13')
            ->assertJsonPath('data.0.times', ['11:00 AM']);

        Carbon::setTestNow();
    }

    public function test_inactive_vehicle_detail_is_not_public(): void
    {
        [$trim] = $this->catalogRecords();
        $trim->vehicle->update(['active' => false]);

        $this->getJson('/api/v1/vehicles/'.$trim->vehicle_id)->assertNotFound();
    }

    public function test_inactive_branch_detail_is_not_public_and_location_sorting_works(): void
    {
        [, $near] = $this->catalogRecords();
        Branch::create([
            'name'=>'Far','name_ar'=>'بعيد','address'=>'Far address','address_ar'=>'عنوان بعيد',
            'phone'=>'19023','hours'=>'9-5','hours_ar'=>'9-5','lat'=>31.2,'lng'=>32.2,'active'=>true,
        ]);

        $this->getJson('/api/v1/branches?lat=30.01&lng=31.01')
            ->assertOk()->assertJsonPath('data.0.id', $near->id)
            ->assertJsonStructure(['data' => [['distance_km']]]);

        $near->update(['active' => false]);
        $this->getJson('/api/v1/branches/'.$near->id)->assertNotFound();
    }

    public function test_branch_open_status_follows_weekly_schedule(): void
    {
        Carbon::setTestNow('2026-09-14 11:00:00'); // Monday, Cairo
        [, $branch] = $this->catalogRecords();
        $branch->update([
            'timezone' => 'Africa/Cairo',
            'opening_hours' => [
                ['day'=>'mon','open'=>'10:00','close'=>'12:00','closed'=>false],
                ['day'=>'tue','open'=>'10:00','close'=>'12:00','closed'=>true],
            ],
            'is_open' => false,
        ]);
        $this->getJson('/api/v1/branches/'.$branch->id)->assertOk()->assertJsonPath('data.is_open', true);
        Carbon::setTestNow();
    }

    public function test_home_uses_dashboard_curated_smart_matches_and_budget_picks(): void
    {
        [$trim] = $this->catalogRecords();
        AppSetting::put('home_hero_vehicle_ids', [$trim->vehicle_id]);
        AppSetting::put('smart_matches', [[
            'trim_id' => $trim->id,
            'match_percentage' => 97,
        ]]);
        AppSetting::put('budget_pick_trim_ids', [$trim->id]);

        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('data.heroes.0.id', $trim->vehicle_id)
            ->assertJsonPath('data.smart_matches.0.trim.id', $trim->id)
            ->assertJsonPath('data.smart_matches.0.match_percentage', 97)
            ->assertJsonPath('data.budget_picks.0.trim.id', $trim->id);
    }

    public function test_compare_uses_dashboard_limit_and_returns_server_comparison(): void
    {
        [$first] = $this->catalogRecords();
        $secondVehicle = Vehicle::create([
            'brand_id' => $first->vehicle->brand_id,
            'model' => 'Model B', 'model_ar' => 'موديل ب',
            'year' => now()->year, 'category' => 'SUV',
            'starting_price_egp' => 1000000, 'active' => true,
        ]);
        $second = Trim::create([
            'vehicle_id' => $secondVehicle->id,
            'name' => 'Second', 'name_ar' => 'ثانية', 'price_egp' => 1000000,
            'subtitle' => '', 'active' => true,
            'highlights' => [], 'specs' => [], 'gallery' => [],
            'metrics' => ['hp' => ['display' => '200 hp', 'score' => 200]],
        ]);
        AppSetting::put('compare_max', 2);

        $this->getJson("/api/v1/compare?trim_ids={$first->id},{$second->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.vehicles');
    }

    public function test_points_debit_is_atomic_and_cannot_overdraw(): void
    {
        $user = User::factory()->create(['points' => 100]);
        $user->addPoints(40, 'Redeem', 'debit');

        $this->assertSame(60, $user->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'points' => -40,
            'type' => 'debit',
        ]);

        $this->expectException(\RuntimeException::class);
        $user->fresh()->addPoints(61, 'Too much', 'debit');
    }

    public function test_favorites_toggle_and_hide_inactive_catalog_items(): void
    {
        [$trim] = $this->catalogRecords();
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);
        $this->postJson("/api/v1/trims/{$trim->id}/favorite")->assertOk()->assertJsonPath('data.is_favorited', true);
        $this->getJson('/api/v1/profile/favorites')->assertOk()->assertJsonCount(1, 'data');
        $trim->update(['active' => false]);
        $this->getJson('/api/v1/profile/favorites')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_reward_redemption_creates_recoverable_code_and_enforces_limits(): void
    {
        $user = User::factory()->create(['is_active' => true, 'points' => 500]);
        $reward = Reward::create(['name'=>'Gift','name_ar'=>'هدية','description'=>'Test','description_ar'=>'اختبار','points_cost'=>100,'stock'=>1,'per_user_limit'=>1,'validity_days'=>30,'active'=>true]);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/profile/rewards/redeem', ['reward_id'=>$reward->id], ['Idempotency-Key'=>'reward-redemption-0001'])
            ->assertOk()->assertJsonStructure(['data'=>['redemption_code','valid_until']]);
        $this->assertSame(400, $user->fresh()->points);
        $this->assertSame(0, $reward->fresh()->stock);
        $this->assertDatabaseHas('user_notifications', ['user_id'=>$user->id,'type'=>'reward']);
        $this->getJson('/api/v1/profile/redemptions')->assertOk()->assertJsonCount(1, 'data.items');
        $this->postJson('/api/v1/profile/rewards/redeem', ['reward_id'=>$reward->id], ['Idempotency-Key'=>'reward-redemption-0002'])->assertConflict();
    }

    private function catalogRecords(): array
    {
        $brandId = \DB::table('brands')->insertGetId([
            'name' => 'Test Brand', 'name_ar' => 'اختبار', 'active' => true,
        ]);
        $vehicle = Vehicle::create([
            'brand_id' => $brandId, 'model' => 'Model A', 'model_ar' => 'موديل',
            'year' => now()->year, 'category' => 'Sedan', 'starting_price_egp' => 100,
            'active' => true,
        ]);
        $trim = Trim::create([
            'vehicle_id' => $vehicle->id, 'name' => 'Base', 'name_ar' => 'أساسي',
            'price_egp' => 100, 'highlights' => [], 'specs' => [], 'metrics' => [],
            'gallery' => [], 'active' => true, 'in_test_drive_fleet' => true,
        ]);
        $branch = Branch::create([
            'name' => 'Branch', 'name_ar' => 'فرع', 'address' => 'Address',
            'address_ar' => 'عنوان', 'phone' => '19022', 'hours' => '9-5',
            'hours_ar' => '9-5', 'is_open' => true, 'lat' => 30, 'lng' => 31,
            'active' => true,
        ]);

        return [$trim->load('vehicle'), $branch];
    }
}
