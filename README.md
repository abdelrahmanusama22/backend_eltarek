# El Tarek Automotive — Backend

Laravel 13 REST API for the El Tarek mobile app (`../eltarek-mobile`).
SQLite in dev, Sanctum bearer-token auth, OTP login via **SMS Misr (sms.com.eg)**.

## Run locally

```bash
composer install
cp .env.example .env && php artisan key:generate   # first time only
php artisan migrate --seed
php artisan serve --port=8010
```

Port **8010** is what the app expects in dev (8000 is occupied by Laragon on
this machine). The Flutter app hydrates its catalog from `GET /api/v1/bootstrap`
on launch and silently falls back to its bundled data when the server is down.

## OTP / SMS Misr

`SMS_DRIVER=log` (default) writes OTP codes to `storage/logs/laravel.log`
instead of sending SMS — and while `APP_DEBUG=true` the code is also returned
as `debug_otp` in the send-otp response for easy testing.

To go live, fill in the credentials from your SMS Misr account:

```env
SMS_DRIVER=smsmisr
SMSMISR_ENVIRONMENT=1        # 1 = live, 2 = integration test
SMSMISR_USERNAME=...
SMSMISR_PASSWORD=...
SMSMISR_SENDER=...           # approved sender token
SMSMISR_OTP_TEMPLATE=...     # approved OTP template token
```

Client: `app/Services/SmsMisrService.php`
(OTP endpoint `https://smsmisr.com/api/OTP/`, success code 4901;
SMS endpoint `https://smsmisr.com/api/SMS/`, success code 1901).

## Dynamic content

Everything the app shows lives in the database — brands, vehicles, trims
(specs/highlights/metrics/gallery as JSON columns), branches, rewards,
cities — plus `app_settings` rows for home sections (smart matches, budget
picks, financing banner), finance calculator rates, VIP tiers/benefits,
test-drive weekly time templates, and the compare limit. Change a row, and
the app reflects it on next launch with no release.

`database/seeders/CatalogSeeder.php` seeds the launch catalog (a port of the
app's bundled mock data). `DatabaseSeeder` adds a demo VIP user
(`+201001234567`).

## API

Routes in `routes/api.php` under `/api/v1` — the Postman collection in the
mobile repo (`ElTarek_Automotive_API.postman_collection.json`) documents every
endpoint. Highlights:

- `POST auth/send-otp | verify-otp | resend-otp`, `POST auth/complete-profile`, `POST auth/logout`
- `GET bootstrap` — full catalog in one call (app hydration)
- `GET home`, `GET vehicles`, `GET vehicles/search`, `GET vehicles/{id}/trims`, `GET trims/{id}`
- `POST finance/calculate`, `POST finance/eligibility`
- `GET/POST compare`, `DELETE compare/{trimId}` (max 3)
- `GET branches`, `GET test-drives/fleet|slots`, `GET/POST/DELETE test-drives`
- `GET/PUT profile`, garage, favorites, rewards (+redeem), VIP benefits
- `GET notifications`, `POST notifications/mark-all-read`
