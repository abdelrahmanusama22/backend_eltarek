# Production operations

Use PHP 8.4 or newer; the locked `spatie/laravel-activitylog` version requires it.
Before installing on staging or production, run `php -v` and confirm the CLI
and PHP-FPM are both PHP 8.4+. Then run `composer install --no-interaction
--prefer-dist` and `composer check-platform-reqs` using that PHP version.
Never use `--ignore-platform-reqs` to force this lockfile onto PHP 8.2/8.3.
The local `.tools/php83` runner is only a development convenience and does not
prove compatibility with the declared PHP 8.4 platform; the CI workflow runs
these installation and platform checks on PHP 8.4.

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

## Apple Sign In

Register the iOS Bundle ID with the Sign in with Apple capability and set
`APPLE_BUNDLE_ID` to that exact identifier. For Android (not web login),
register a Services ID and configure its domain plus the HTTPS Return URL
`https://YOUR_API_HOST/api/v1/auth/apple/callback`; set `APPLE_SERVICE_ID` and
`APPLE_ANDROID_PACKAGE`. Apple does not accept localhost or an IP address for
the web return URL. The backend fetches Apple's public signing keys and checks
the identity token's signature, issuer, audience, expiry and one-time nonce
before issuing a mobile token. Do not accept unverified client profile fields
as identity claims. The current ID-token flow does not exchange authorization
codes or store Apple refresh tokens; add that separately if account revocation
monitoring is required. No Apple private `.p8` key is required by this flow.
Configure the Apple private-email-relay sending domain if users can hide email.
The current callback returns an Android intent. A separate web callback and
session flow must be implemented before offering Apple login on the website.
An end-to-end staging test requires a public HTTPS API domain, configured
Apple Developer IDs, and physical iOS/Android verification; local configuration
alone does not establish that the Apple flow works.

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
