<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class Idempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key'));
        if (strlen($key) < 16 || strlen($key) > 100) {
            return $this->error('invalid_idempotency_key', 'A 16–100 character Idempotency-Key header is required.', 422, $request);
        }

        $principal = $request->user()?->getAuthIdentifier()
            ?? hash('sha256', $request->ip().'|'.(string) $request->input('phone'));
        $scope = $request->method().'|'.$request->route()?->uri().'|'.$principal.'|'.$key;
        $cacheKey = 'idempotency:'.hash('sha256', $scope);
        $fingerprint = hash('sha256', json_encode($request->all(), JSON_THROW_ON_ERROR));
        $lock = Cache::lock($cacheKey.':lock', 30);

        if (! $lock->block(5)) {
            return $this->error('request_in_progress', 'An identical request is still being processed.', 409, $request);
        }

        try {
            $stored = Cache::get($cacheKey);
            if (is_array($stored)) {
                if (! hash_equals($stored['fingerprint'], $fingerprint)) {
                    return $this->error('idempotency_conflict', 'This Idempotency-Key was already used with a different payload.', 409, $request);
                }

                return response($stored['body'], $stored['status'], $stored['headers']);
            }

            /** @var Response $response */
            $response = $next($request);
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                Cache::put($cacheKey, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                    'headers' => ['Content-Type' => $response->headers->get('Content-Type', 'application/json')],
                ], now()->addDay());
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function error(string $code, string $message, int $status, Request $request): JsonResponse
    {
        $requestId = $request->attributes->get('request_id');

        return response()->json([
            'success' => false,
            'data' => null,
            'meta' => ['request_id' => $requestId, 'api_version' => 'v1'],
            'error' => ['code' => $code, 'message' => $message, 'fields' => null, 'request_id' => $requestId],
            'message' => $message,
            'errors' => null,
        ], $status);
    }
}
