<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Shared JSON envelope with stable metadata and structured errors. */
abstract class ApiController extends Controller
{
    protected function ok(mixed $data = null, ?string $message = null, ?array $meta = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'request_id' => request()->attributes->get('request_id'),
                'api_version' => 'v1',
            ], $meta ?? []),
            'error' => null,
        ];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload, $status);
    }

    protected function fail(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $code = match ($status) {
            401 => 'unauthenticated', 403 => 'forbidden', 404 => 'not_found',
            409 => 'conflict', 422 => 'validation_failed', 429 => 'rate_limited',
            default => $status >= 500 ? 'server_error' : 'bad_request',
        };
        $payload = [
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
            'meta' => ['request_id' => request()->attributes->get('request_id'), 'api_version' => 'v1'],
            'error' => [
                'code' => $code,
                'message' => $message,
                'fields' => $errors,
                'request_id' => request()->attributes->get('request_id'),
            ],
        ];

        return response()->json($payload, $status);
    }
}
