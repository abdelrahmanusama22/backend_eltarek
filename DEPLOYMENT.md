# Production operations

Required long-running processes:

```text
php artisan queue:work --sleep=1 --tries=3 --timeout=600
php artisan schedule:work
php artisan reverb:start
```

Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, explicit
`CORS_ALLOWED_ORIGINS`, `QUEUE_CONNECTION=database`, token expiration, and
`HEALTH_REQUIRE_SCHEDULER=true`. Point the load balancer health check to
`GET /health`; `/up` remains the process liveness endpoint.

Run the three processes under systemd, Supervisor, a container orchestrator,
or an equivalent process manager with automatic restart. Alert if `/health`
reports a stale scheduler, if failed jobs increase, or if Reverb is unavailable.
Do not use `php artisan serve` in production; use PHP-FPM with enough workers
for the mobile catalog's parallel requests.

Google OAuth secrets belong only in the deployment secret store. If a client
secret has ever appeared in chat, logs, tickets, or source control, revoke it
in Google Cloud, create a replacement, and update the secret store before the
next deployment. Android receives only the public client ID; it must never
contain the web client secret.

Before each release run Composer validation/audit, migrations on staging,
backend and mobile tests, a signed release build, and smoke tests for OTP,
profile, catalog, booking, cancellation, favorites, comparison and rewards.

Back up the database and `storage/app/public` before migrations. Restore both
into staging at least monthly and verify catalog media, users, bookings and
point ledgers. Alert on HTTP 5xx, slow-request logs, failed jobs, stale
scheduler heartbeat, queue depth and disk usage. Never log OTP values, tokens,
credentials or complete request bodies.
