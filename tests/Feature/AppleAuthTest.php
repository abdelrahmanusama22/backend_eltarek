<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AppleIdentityTokenVerifier;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AppleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.apple.bundle_id', 'com.example.app');
        config()->set('services.apple.service_id', 'com.example.service');
    }

    public function test_challenge_is_required_and_can_only_be_used_once(): void
    {
        $nonce = $this->postJson('/api/v1/auth/apple/challenge')->assertOk()->json('data.nonce');
        $this->assertSame(64, strlen($nonce));

        $verifier = Mockery::mock(AppleIdentityTokenVerifier::class);
        $verifier->shouldReceive('verify')->twice()->with('signed-token', $nonce)->andReturn([
            'sub' => 'apple-123', 'email' => 'apple@example.com', 'email_verified' => 'true',
        ]);
        $this->app->instance(AppleIdentityTokenVerifier::class, $verifier);

        $payload = ['id_token' => 'signed-token', 'nonce' => $nonce, 'name' => 'Test Person'];
        $this->postJson('/api/v1/auth/apple', $payload, ['Idempotency-Key' => 'apple-login-attempt-0001'])->assertOk()
            ->assertJsonPath('data.is_new_user', true)
            ->assertJsonPath('data.profile_complete', false);
        $this->postJson('/api/v1/auth/apple', $payload, ['Idempotency-Key' => 'apple-login-attempt-0002'])->assertUnauthorized();
        $this->assertSame(1, User::where('apple_id', 'apple-123')->count());
        $this->assertSame('Test Person', User::where('apple_id', 'apple-123')->firstOrFail()->name);
    }

    public function test_invalid_signature_does_not_create_a_user(): void
    {
        $nonce = $this->postJson('/api/v1/auth/apple/challenge')->assertOk()->json('data.nonce');
        $verifier = Mockery::mock(AppleIdentityTokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->andThrow(new \RuntimeException('invalid'));
        $this->app->instance(AppleIdentityTokenVerifier::class, $verifier);

        $this->postJson('/api/v1/auth/apple', ['id_token' => 'forged', 'nonce' => $nonce], ['Idempotency-Key' => 'apple-login-forged-0001'])
            ->assertUnauthorized();
        $this->assertDatabaseCount('users', 0);
        $this->assertTrue((bool) Cache::get('auth:apple:nonce:'.hash('sha256', $nonce)));
    }

    public function test_callback_only_redirects_to_the_configured_android_package(): void
    {
        $response = $this->post('/api/v1/auth/apple/callback', [
            'code' => 'abc', 'state' => 'xyz', 'redirect' => 'https://evil.example',
        ]);
        $response->assertStatus(302);
        $this->assertSame(
            'intent://callback?code=abc&state=xyz#Intent;package=com.eltarek.eltarek_mobile;scheme=signinwithapple;end',
            $response->headers->get('Location'),
        );
    }

    public function test_identity_token_requires_a_valid_apple_signature_audience_and_nonce(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($key === false) {
            if (getenv('CI') === 'true') {
                $this->fail('CI must support RSA key generation to verify Apple token signatures.');
            }
            $this->markTestSkipped('RSA key generation is unavailable in this PHP/OpenSSL environment.');
        }
        openssl_pkey_export($key, $private);
        $details = openssl_pkey_get_details($key);
        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        Cache::forget('auth:apple:public_keys');
        Http::fake(['https://appleid.apple.com/auth/keys' => Http::response([
            'keys' => [[
                'kty' => 'RSA', 'kid' => 'test-key', 'alg' => 'RS256', 'use' => 'sig',
                'n' => $encode($details['rsa']['n']), 'e' => $encode($details['rsa']['e']),
            ]],
        ])]);

        $claims = [
            'iss' => 'https://appleid.apple.com', 'aud' => 'com.example.app',
            'sub' => 'apple-123', 'nonce' => 'expected-nonce',
            'iat' => time(), 'exp' => time() + 300,
        ];
        $verifier = app(AppleIdentityTokenVerifier::class);
        $token = JWT::encode($claims, $private, 'RS256', 'test-key');
        $this->assertSame('apple-123', $verifier->verify($token, 'expected-nonce')['sub']);

        $this->expectException(\RuntimeException::class);
        $verifier->verify($token, 'different-nonce');
    }
}
