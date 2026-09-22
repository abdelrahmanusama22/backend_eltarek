<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AppleIdentityTokenVerifier
{
    /** @return array<string, mixed> */
    public function verify(string $token, string $nonce): array
    {
        $bundleId = (string) config('services.apple.bundle_id');
        $serviceId = (string) config('services.apple.service_id');
        $audiences = array_values(array_filter([$bundleId, $serviceId]));
        if ($audiences === []) {
            throw new RuntimeException('Apple sign-in is not configured.');
        }

        $keys = Cache::remember('auth:apple:public_keys', now()->addHour(), function (): array {
            $response = Http::timeout(8)->get('https://appleid.apple.com/auth/keys')->throw();
            $data = $response->json();
            if (! is_array($data) || ! is_array($data['keys'] ?? null)) {
                throw new RuntimeException('Apple public keys are unavailable.');
            }

            return $data;
        });

        $payload = (array) JWT::decode($token, JWK::parseKeySet($keys, 'RS256'));
        if (($payload['iss'] ?? null) !== 'https://appleid.apple.com'
            || ! in_array($payload['aud'] ?? null, $audiences, true)
            || ! is_string($payload['sub'] ?? null)
            || $payload['sub'] === ''
            || ! hash_equals($nonce, (string) ($payload['nonce'] ?? ''))) {
            throw new RuntimeException('Invalid Apple identity token.');
        }

        return $payload;
    }
}
