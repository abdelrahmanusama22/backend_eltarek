<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use App\Services\AppleIdentityTokenVerifier;
use App\Services\GoogleIdentityTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class EmailIdentitySafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_a_verified_email_keeps_the_old_identity_until_new_address_is_verified(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['email' => 'new@example.com'])->assertOk()
            ->assertJsonPath('data.email', 'old@example.com')
            ->assertJsonPath('data.pending_email', 'new@example.com');
        $this->assertNotNull($user->fresh()->email_verified_at);
        Cache::put('email_verify:'.hash('sha256', 'new@example.com'), Hash::make('123456'), now()->addMinutes(5));

        $this->postJson('/api/v1/auth/email/verify', ['code' => '123456'])->assertOk()
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.pending_email', null);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_phone_profile_email_is_pending_until_code_verification(): void
    {
        Mail::fake();
        $city = City::create(['name' => 'Cairo', 'name_ar' => 'القاهرة', 'sort' => 1]);
        $user = User::factory()->create(['email' => null, 'email_verified_at' => null]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/complete-profile', [
            'full_name' => 'Phone User', 'age' => 25, 'city_id' => $city->id,
            'email' => 'someone@example.com',
        ])->assertOk()->assertJsonPath('data.pending_email', 'someone@example.com');
        $this->assertNull($user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_apple_cannot_claim_an_account_with_unverified_email_or_another_apple_identity(): void
    {
        config()->set('services.apple.bundle_id', 'com.example.app');
        $user = User::factory()->create(['email' => 'person@example.com', 'email_verified_at' => null]);
        $verifier = Mockery::mock(AppleIdentityTokenVerifier::class);
        $verifier->shouldReceive('verify')->twice()->andReturn([
            'sub' => 'apple-attacker', 'email' => 'person@example.com', 'email_verified' => 'true',
        ]);
        $this->app->instance(AppleIdentityTokenVerifier::class, $verifier);

        foreach ([null, 'apple-original'] as $existingAppleId) {
            $user->update(['email_verified_at' => $existingAppleId ? now() : null, 'apple_id' => $existingAppleId]);
            $nonce = $this->postJson('/api/v1/auth/apple/challenge')->assertOk()->json('data.nonce');
            $this->postJson('/api/v1/auth/apple', [
                'id_token' => 'signed-token', 'nonce' => $nonce,
            ], ['Idempotency-Key' => 'apple-conflict-'.($existingAppleId ?: 'unverified')])
                ->assertStatus(409);
        }
        $this->assertSame(1, User::where('email', 'person@example.com')->count());
        $this->assertSame('apple-original', $user->fresh()->apple_id);
    }

    public function test_google_cannot_claim_an_unverified_or_differently_linked_account(): void
    {
        config()->set('services.google.web_client_id', 'test-client-id');
        $user = User::factory()->create(['email' => 'person@example.com', 'email_verified_at' => null]);
        $verifier = Mockery::mock(GoogleIdentityTokenVerifier::class);
        $verifier->shouldReceive('verify')->twice()->andReturn([
            'sub' => 'google-attacker', 'email' => 'person@example.com', 'email_verified' => true,
        ]);
        $this->app->instance(GoogleIdentityTokenVerifier::class, $verifier);

        foreach ([null, 'google-original'] as $existingGoogleId) {
            $user->update(['email_verified_at' => $existingGoogleId ? now() : null, 'google_id' => $existingGoogleId]);
            $this->postJson('/api/v1/auth/google', ['id_token' => 'signed-token'], [
                'Idempotency-Key' => 'google-conflict-'.($existingGoogleId ?: 'unverified'),
            ])->assertStatus(409);
        }
        $this->assertSame('google-original', $user->fresh()->google_id);
    }

    public function test_verified_email_does_not_automatically_link_google_and_owner_can_link_it_explicitly(): void
    {
        config()->set('services.google.web_client_id', 'test-client-id');
        $user = User::factory()->create(['email' => 'owner@example.com', 'email_verified_at' => now()]);
        $verifier = Mockery::mock(GoogleIdentityTokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn([
            'sub' => 'google-owner', 'email' => 'owner@example.com', 'email_verified' => true,
        ]);
        $this->app->instance(GoogleIdentityTokenVerifier::class, $verifier);

        $this->postJson('/api/v1/auth/google', ['id_token' => 'signed-token'],
            ['Idempotency-Key' => 'google-verified-conflict'])->assertStatus(409);
        $this->assertNull($user->fresh()->google_id);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/auth/google/link', ['id_token' => 'signed-token'])
            ->assertOk()->assertJsonPath('data.linked', true);
        $this->assertSame('google-owner', $user->fresh()->google_id);
    }

    public function test_verified_email_does_not_automatically_link_apple_and_owner_can_link_it_explicitly(): void
    {
        config()->set('services.apple.bundle_id', 'com.example.app');
        $user = User::factory()->create(['email' => 'owner@example.com', 'email_verified_at' => now()]);
        $verifier = Mockery::mock(AppleIdentityTokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn([
            'sub' => 'apple-owner', 'email' => 'owner@example.com', 'email_verified' => 'true',
        ]);
        $this->app->instance(AppleIdentityTokenVerifier::class, $verifier);

        $nonce = $this->postJson('/api/v1/auth/apple/challenge')->assertOk()->json('data.nonce');
        $this->postJson('/api/v1/auth/apple', ['id_token' => 'signed-token', 'nonce' => $nonce],
            ['Idempotency-Key' => 'apple-verified-conflict'])->assertStatus(409);
        $this->assertNull($user->fresh()->apple_id);

        Sanctum::actingAs($user);
        $nonce = $this->postJson('/api/v1/auth/apple/challenge')->assertOk()->json('data.nonce');
        $this->postJson('/api/v1/auth/apple/link', ['id_token' => 'signed-token', 'nonce' => $nonce])
            ->assertOk()->assertJsonPath('data.linked', true);
        $this->assertSame('apple-owner', $user->fresh()->apple_id);
    }

    public function test_an_external_identity_cannot_be_relinked_to_a_second_session(): void
    {
        config()->set('services.google.web_client_id', 'test-client-id');
        $original = User::factory()->create(['google_id' => 'google-taken']);
        $other = User::factory()->create();
        $verifier = Mockery::mock(GoogleIdentityTokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn([
            'sub' => 'google-taken', 'email' => 'other@example.com', 'email_verified' => true,
        ]);
        $this->app->instance(GoogleIdentityTokenVerifier::class, $verifier);
        Sanctum::actingAs($other);

        $this->postJson('/api/v1/auth/google/link', ['id_token' => 'signed-token'])->assertStatus(409);
        $this->assertNull($other->fresh()->google_id);
        $this->assertSame('google-taken', $original->fresh()->google_id);
    }
}
