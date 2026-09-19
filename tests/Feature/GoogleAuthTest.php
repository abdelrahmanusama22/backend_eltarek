<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Google\Client as GoogleClient;
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

        $mock = Mockery::mock(GoogleClient::class);
        $mock->shouldReceive("verifyIdToken")
             ->once()
             ->with("dummy_id_token")
             ->andReturn($mockPayload);

        $this->app->bind(GoogleClient::class, function() use ($mock) {
            return $mock;
        });

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
