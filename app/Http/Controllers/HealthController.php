<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'failed';
        }
        try {
            Cache::put('health:last_check', now()->toIso8601String(), 60);
            $checks['cache'] = 'ok';
        } catch (\Throwable) {
            $checks['cache'] = 'failed';
        }

        if (config('health.require_scheduler')) {
            $heartbeat = (int) Cache::get('health:scheduler_heartbeat', 0);
            $checks['scheduler'] = $heartbeat > 0
                && now()->timestamp - $heartbeat <= config('health.scheduler_max_age_seconds')
                ? 'ok'
                : 'failed';
        } else {
            $checks['scheduler'] = 'not_required';
        }

        try {
            $checks['failed_jobs'] = (string) DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $checks['failed_jobs'] = 'unknown';
        }

        $healthy = ! in_array('failed', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
