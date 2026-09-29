<?php

namespace Tests\Feature;

use App\Services\GoogleIdentityTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_auth_endpoint_works()
    {
        $mockPayload = [
            "sub" => "1234567890",
            "email" => "test_google@example.com",
            "email_verified" => true,
            "name" => "Test Google User",
            "picture" => "https://example.com/photo.jpg",
        ];

        config(['services.google.web_client_id' => 'test-google-client']);

        $mock = Mockery::mock(GoogleIdentityTokenVerifier::class);
        $mock->shouldReceive('verify')
             ->once()
             ->with('dummy_id_token', 'test-google-client')
             ->andReturn($mockPayload);

        $this->app->instance(GoogleIdentityTokenVerifier::class, $mock);

        $response = $this->postJson("/api/v1/auth/google", [
            "id_token" => "dummy_id_token",
        ], [
            "Idempotency-Key" => "test-idempotency-key-12345678"
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath("success", true)
                 ->assertJsonStructure([
                     "success",
                     "message",
                     "data" => [
                         "access_token",
                         "token_type",
                         "expires_in",
                         "is_new_user",
                         "profile_complete"
                     ],
                 ]);
    }
}
