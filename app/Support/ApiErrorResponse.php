<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApiErrorResponse
{
    public static function make(Request $request, string $code, string $message, int $status, ?array $fields = null): JsonResponse
    {
        $requestId = $request->attributes->get('request_id');

        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $fields,
            'meta' => ['request_id' => $requestId, 'api_version' => 'v1'],
            'error' => compact('code', 'message') + ['fields' => $fields, 'request_id' => $requestId],
        ], $status);
    }
}
