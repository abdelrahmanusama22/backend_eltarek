<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = hrtime(true);
        $response = $next($request);
        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
        $context = [
            'request_id' => $request->attributes->get('request_id'),
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'user_id' => $request->user()?->getAuthIdentifier(),
        ];

        if ($response->getStatusCode() >= 500) {
            Log::error('api.request', $context);
        } elseif ($durationMs >= (int) config('health.slow_request_ms', 1500)) {
            Log::warning('api.slow_request', $context);
        } else {
            Log::info('api.request', $context);
        }

        return $response;
    }
}
