# Production operations

Required long-running processes:

```text
php artisan queue:work --sleep=1 --tries=3 --timeout=600
php artisan schedule:work
```

Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, explicit
`CORS_ALLOWED_ORIGINS`, `QUEUE_CONNECTION=database`, token expiration, and
`HEALTH_REQUIRE_SCHEDULER=true`. Point the load balancer health check to
`GET /health`; `/up` remains the process liveness endpoint.

Before each release run Composer validation/audit, migrations on staging,
backend and mobile tests, a signed release build, and smoke tests for OTP,
profile, catalog, booking, cancellation, favorites, comparison and rewards.

Back up the database and `storage/app/public` before migrations. Restore both
into staging at least monthly and verify catalog media, users, bookings and
point ledgers. Alert on HTTP 5xx, slow-request logs, failed jobs, stale
scheduler heartbeat, queue depth and disk usage. Never log OTP values, tokens,
credentials or complete request bodies.
