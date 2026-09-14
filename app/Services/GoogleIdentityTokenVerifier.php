<?php

namespace App\Services;

use Google\Client as GoogleClient;

class GoogleIdentityTokenVerifier
{
    /** @return array<string, mixed>|false */
    public function verify(string $idToken, string $clientId): array|false
    {
        return (new GoogleClient(['client_id' => $clientId]))->verifyIdToken($idToken);
    }
}
