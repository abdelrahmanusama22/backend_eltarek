<?php

return [
    'require_scheduler' => (bool) env('HEALTH_REQUIRE_SCHEDULER', false),
    'scheduler_max_age_seconds' => (int) env('HEALTH_SCHEDULER_MAX_AGE_SECONDS', 180),
    'slow_request_ms' => (int) env('SLOW_REQUEST_MS', 1500),
];
