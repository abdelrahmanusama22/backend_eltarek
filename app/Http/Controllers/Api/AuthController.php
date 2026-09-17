<?php

namespace App\Http\Controllers\Api;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\SmsMisrService;
use App\Services\AppleIdentityTokenVerifier;
use App\Services\GoogleIdentityTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends ApiController
{
    /** Step 1 — send a 4-digit OTP via SMS Misr. */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $this->normalizePhone($request->string('phone'));
        if ($phone === null) {
            return $this->fail('Validation failed.', 422, [
                'phone' => ['The phone must be a valid Egyptian mobile number.'],
            ]);
        }

        $user = User::where('phone', $phone)->first();
        if ($user && ! $user->is_active) {
            return $this->fail('Your account has been deactivated. Please contact support.', 403);
        }

        $resendSeconds = (int) config('services.otp.resend_seconds', 60);
        $length = max(4, (int) config('services.otp.length', 4));
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $otp = null;

        $lock = Cache::lock('otp:send:'.hash('sha256', $phone), 15);
        if (! $lock->get()) {
            return $this->fail('An OTP request is already in progress. Please try again shortly.', 429);
        }

        try {
            $recent = OtpCode::where('phone', $phone)
                ->where('created_at', '>', now()->subSeconds($resendSeconds))
                ->latest()
                ->first();
            if ($recent) {
                $retryAfter = $resendSeconds - (int) $recent->created_at->diffInSeconds(now());

                return response()->json([
                    'success' => false,
                    'message' => 'Too many OTP requests. Please wait before trying again.',
                    'retry_after' => max(1, $retryAfter),
                ], 429);
            }

            $otp = OtpCode::create([
                'phone' => $phone,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addSeconds((int) config('services.otp.ttl_seconds', 300)),
            ]);

            if (! SmsMisrService::make()->sendOtp($phone, $code)) {
                $otp->delete();

                return $this->fail('Could not send the verification SMS. Please try again.', 502);
            }
        } finally {
            $lock->release();
        }

        $data = [
            'phone' => $phone,
            'expires_in' => (int) config('services.otp.ttl_seconds', 300),
            'resend_available_in' => $resendSeconds,
        ];
        // Surface the code during local development (log driver only).
        if (config('app.debug') && config('services.smsmisr.driver') === 'log') {
            $data['debug_otp'] = $code;
        }

        return $this->ok($data, 'OTP sent successfully.');
    }

    public function resendOtp(Request $request): JsonResponse
    {
        return $this->sendOtp($request);
    }

    public function registerEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = mb_strtolower($validated['email']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => $validated['password'],
            // email_verified_at intentionally left null — user must verify via code.
            'member_since' => now()->toDateString(),
            'profile_complete' => false,
            'is_active' => true,
        ]);

        // Generate & send a 6-digit verification code.
        $this->sendVerificationCode($email);

        // Issue token so the user can call /auth/email/verify while authenticated.
        return $this->issueMobileToken($user, true, 'Account created. Please verify your email.');
    }

    public function loginEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', mb_strtolower($validated['email']))->first();

        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            return $this->fail('Invalid email or password.', 401);
        }
        if (! $user->is_active) {
            return $this->fail('Your account has been deactivated. Please contact support.', 403);
        }

        return $this->issueMobileToken($user, false, 'Signed in successfully.');
    }

    /** Step 2 — verify the OTP and issue a Sanctum token. */
    public function verifyOtp(Request $request): JsonResponse
    {
        $otpLength = max(4, (int) config('services.otp.length', 4));
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'digits:'.$otpLength],
        ]);

        $phone = $this->normalizePhone($request->string('phone'));
        if ($phone === null) {
            return $this->fail('Validation failed.', 422, [
                'phone' => ['The phone must be a valid Egyptian mobile number.'],
            ]);
        }

        $result = DB::transaction(function () use ($phone, $request) {
            $otp = OtpCode::where('phone', $phone)
                ->whereNull('verified_at')
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $otp || $otp->expires_at->isPast()) {
                return ['error' => 'invalid'];
            }
            if ($otp->attempts >= (int) config('services.otp.max_attempts', 5)) {
                return ['error' => 'attempts'];
            }

            $otp->increment('attempts');
            if (! Hash::check($request->string('otp')->toString(), $otp->code_hash)) {
                return ['error' => 'invalid'];
            }

            $otp->update(['verified_at' => now()]);
            $user = User::firstOrCreate(
                ['phone' => $phone],
                ['member_since' => now()->toDateString(), 'is_active' => true],
            );

            return ['user' => $user];
        }, 3);

        if (($result['error'] ?? null) === 'attempts') {
            return $this->fail('Too many attempts. Please request a new code.', 429);
        }
        if (isset($result['error'])) {
            return $this->fail('Invalid or expired OTP.', 400);
        }

        /** @var User $user */
        $user = $result['user'];

        if (! $user->is_active) {
            return $this->fail('Your account has been deactivated. Please contact support.', 403);
        }

        $isNew = $user->wasRecentlyCreated;

        $user->tokens()->where('name', 'mobile')->delete();
        $expirationMinutes = (int) config('sanctum.expiration', 1440);
        $token = $user->createToken(
            'mobile',
            ['*'],
            now()->addMinutes($expirationMinutes),
        )->plainTextToken;

        return $this->ok([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expirationMinutes * 60,
            'is_new_user' => $isNew,
            'profile_complete' => (bool) $user->profile_complete,
        ], 'OTP verified.');
    }

    /** Step 3 — complete the profile for new users. */
    public function completeProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'age' => ['required', 'integer', 'min:18', 'max:100'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);

        $user = $request->user();

        if (! empty($validated['email'])) {
            $emailTaken = User::where('email', $validated['email'])
                ->whereKeyNot($user->id)
                ->exists();
            if ($emailTaken) {
                return $this->fail('This email is already associated with another account.', 409);
            }
        }

        $newEmail = ! empty($validated['email']) ? mb_strtolower($validated['email']) : null;
        $emailChanged = $newEmail !== null && $newEmail !== mb_strtolower((string) $user->email);
        $user->update([
            'name' => $validated['full_name'],
            'age' => $validated['age'],
            'city_id' => $validated['city_id'],
            'pending_email' => $emailChanged ? $newEmail : $user->pending_email,
            'profile_complete' => true,
        ]);

        if ($emailChanged) {
            $this->sendVerificationCode($newEmail);
        }

        return $this->ok($user->fresh()->load('city')->toApi(), 'Profile completed successfully.');
    }

    // -------------------------------------------------- email verification

    /** Verify email address with the 6-digit code sent after registration. */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        $targetEmail = $user->pending_email ?: $user->email;
        if (! $user->pending_email && $user->email_verified_at) {
            return $this->ok($user->toApi(), 'Email already verified.');
        }
        if (! $targetEmail) {
            return $this->fail('No email address on file.', 422);
        }

        $cacheKey = 'email_verify:'.hash('sha256', $targetEmail);
        $stored = Cache::get($cacheKey);

        if (! $stored || ! Hash::check($request->string('code')->toString(), $stored)) {
            return $this->fail('Invalid or expired verification code.', 400);
        }

        if ($user->pending_email && User::where('email', $targetEmail)->whereKeyNot($user->id)->exists()) {
            return $this->fail('This email is already associated with another account.', 409);
        }
        Cache::forget($cacheKey);
        $user->update(['email' => $targetEmail, 'pending_email' => null, 'email_verified_at' => now()]);

        return $this->ok($user->fresh()->load('city')->toApi(), 'Email verified successfully.');
    }

    /** Resend the email verification code (auth required). */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->pending_email && $user->email_verified_at) {
            return $this->fail('Email is already verified.', 422);
        }
        $targetEmail = $user->pending_email ?: $user->email;
        if (! $targetEmail) {
            return $this->fail('No email address on file.', 422);
        }

        $this->sendVerificationCode($targetEmail);

        return $this->ok(null, 'Verification code resent.');
    }

    // -------------------------------------------------- password reset

    /** Step 1 — request a password-reset code (public). */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:190']]);

        $email = mb_strtolower($request->string('email')->toString());
        $user  = User::where('email', $email)->first();

        // Always respond OK to avoid email enumeration.
        if (! $user || ! $user->is_active) {
            return $this->ok(null, 'If an account exists for that email, a reset code has been sent.');
        }

        $ttl     = 10 * 60; // 10 minutes
        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = 'pwd_reset:'.hash('sha256', $email);

        Cache::put($cacheKey, Hash::make($code), $ttl);

        $this->sendMail(
            $email,
            'Password Reset Code — El Tarek',
            "Your password reset code is: {$code}\n\nThis code expires in 10 minutes."
        );

        return $this->ok(null, 'If an account exists for that email, a reset code has been sent.');
    }

    /** Step 2 — verify code and set new password (public). */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email', 'max:190'],
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email    = mb_strtolower($request->string('email')->toString());
        $cacheKey = 'pwd_reset:'.hash('sha256', $email);
        $stored   = Cache::get($cacheKey);

        if (! $stored || ! Hash::check($request->string('code')->toString(), $stored)) {
            return $this->fail('Invalid or expired reset code.', 400);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return $this->fail('Account not found.', 404);
        }

        Cache::forget($cacheKey);
        $user->update(['password' => $request->string('password')->toString()]);
        // Revoke all existing tokens so old sessions are invalidated.
        $user->tokens()->delete();

        return $this->ok(null, 'Password reset successfully. Please sign in with your new password.');
    }

    // -------------------------------------------------- helpers

    public function sendPendingEmailVerification(string $email): void
    {
        $this->sendVerificationCode($email);
    }

    private function sendVerificationCode(string $email): string
    {
        $ttl      = 15 * 60; // 15 minutes
        $code     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = 'email_verify:'.hash('sha256', $email);

        Cache::put($cacheKey, Hash::make($code), $ttl);

        $this->sendMail(
            $email,
            'Verify Your Email — El Tarek',
            "Your email verification code is: {$code}\n\nThis code expires in 15 minutes."
        );

        return $code;
    }


    private function sendMail(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            // Log but do not bubble up — mail failure shouldn't block the API.
            logger()->error('Mail send failed', ['to' => $to, 'error' => $e->getMessage()]);
        }
    }

    public function google(Request $request, GoogleIdentityTokenVerifier $verifier): JsonResponse
    {
        $validated = $request->validate(['id_token' => ['required', 'string', 'max:10000']]);
        $clientId = (string) config('services.google.web_client_id');
        if ($clientId === '') {
            return $this->fail('Google sign-in is not configured.', 503);
        }
        try {
            $payload = $verifier->verify($validated['id_token'], $clientId);
        } catch (\Throwable) {
            $payload = false;
        }
        if (! $payload || empty($payload['sub']) || empty($payload['email']) || empty($payload['email_verified'])) {
            return $this->fail('Invalid Google identity token.', 401);
        }
        $email = mb_strtolower($payload['email']);
        $user = DB::transaction(function () use ($payload, $email) {
            $user = User::where('google_id', $payload['sub'])->lockForUpdate()->first();
            if (! $user) {
                if (User::where('email', $email)->exists()) {
                    return null;
                }
            }
            if (! $user) {
                return User::create([
                    'google_id' => $payload['sub'], 'email' => $email,
                    'name' => $payload['name'] ?? $email, 'avatar_url' => $payload['picture'] ?? null,
                    'email_verified_at' => now(), 'member_since' => now()->toDateString(),
                    'profile_complete' => false, 'is_active' => true,
                ]);
            }
            return $user;
        }, 3);
        if (! $user) {
            return $this->fail('This email belongs to an existing account. Sign in to that account and link Google explicitly.', 409);
        }
        if (! $user->is_active) {
            return $this->fail('Your account has been deactivated.', 403);
        }
        return $this->issueMobileToken($user, $user->wasRecentlyCreated, 'Signed in with Google.');
    }

    public function linkGoogle(Request $request, GoogleIdentityTokenVerifier $verifier): JsonResponse
    {
        $validated = $request->validate(['id_token' => ['required', 'string', 'max:10000']]);
        $clientId = (string) config('services.google.web_client_id');
        if ($clientId === '') {
            return $this->fail('Google sign-in is not configured.', 503);
        }
        try {
            $identity = $verifier->verify($validated['id_token'], $clientId);
        } catch (\Throwable) {
            return $this->fail('Invalid Google identity token.', 401);
        }
        if (! $identity || empty($identity['sub']) || empty($identity['email_verified'])) {
            return $this->fail('Invalid Google identity token.', 401);
        }

        $linked = DB::transaction(function () use ($request, $identity): bool {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (($user->google_id && $user->google_id !== $identity['sub']) ||
                User::where('google_id', $identity['sub'])->whereKeyNot($user->id)->exists()) {
                return false;
            }
            $user->update(['google_id' => $identity['sub']]);
            return true;
        }, 3);

        return $linked ? $this->ok(['linked' => true]) : $this->fail('Google identity is already linked to another account.', 409);
    }

    public function appleChallenge(): JsonResponse
    {
        if (! config('services.apple.bundle_id') && ! config('services.apple.service_id')) {
            return $this->fail('Apple sign-in is not configured.', 503);
        }

        $nonce = bin2hex(random_bytes(32));
        Cache::put('auth:apple:nonce:'.hash('sha256', $nonce), true, now()->addMinutes(5));

        return $this->ok(['nonce' => $nonce, 'expires_in' => 300]);
    }

    /** Apple posts its web/Android authorization result here; only the fixed app deep link is allowed. */
    public function appleCallback(Request $request)
    {
        $values = $request->only(['code', 'id_token', 'state', 'user', 'error']);
        $package = (string) config('services.apple.android_package');
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_.]*$/', $package)) {
            abort(503, 'Apple Android package is not configured.');
        }

        $query = http_build_query($values, '', '&', PHP_QUERY_RFC3986);
        $url = 'intent://callback?'.$query.'#Intent;package='.$package.';scheme=signinwithapple;end';

        return response('', 302)->header('Location', $url);
    }

    public function apple(Request $request, AppleIdentityTokenVerifier $verifier): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string', 'max:10000'],
            'nonce' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        if (! config('services.apple.bundle_id') && ! config('services.apple.service_id')) {
            return $this->fail('Apple sign-in is not configured.', 503);
        }

        try {
            $identity = $verifier->verify($validated['id_token'], $validated['nonce']);
        } catch (\Throwable) {
            return $this->fail('Invalid Apple identity token.', 401);
        }

        $key = 'auth:apple:nonce:'.hash('sha256', $validated['nonce']);
        if (! Cache::pull($key)) {
            return $this->fail('Apple sign-in challenge expired or was already used.', 401);
        }

        $email = isset($identity['email']) && is_string($identity['email'])
            ? mb_strtolower($identity['email']) : null;
        $emailVerified = in_array($identity['email_verified'] ?? false, [true, 'true'], true);
        if ($email && (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $emailVerified)) {
            return $this->fail('Apple email is not verified.', 401);
        }

        $user = DB::transaction(function () use ($identity, $email, $validated) {
            $user = User::where('apple_id', $identity['sub'])->lockForUpdate()->first();
            if ($user) {
                return $user;
            }
            if ($email) {
                if (User::where('email', $email)->exists()) {
                    return null;
                }
            }

            return User::create([
                'apple_id' => $identity['sub'],
                'email' => $email,
                'email_verified_at' => $email ? now() : null,
                'name' => trim($validated['name'] ?? '') ?: 'Apple User',
                'member_since' => now()->toDateString(),
                'profile_complete' => false,
                'is_active' => true,
            ]);
        }, 3);

        if (! $user) {
            return $this->fail('This email belongs to an existing account. Sign in to that account and link Apple explicitly.', 409);
        }
        if (! $user->is_active) {
            return $this->fail('Your account has been deactivated.', 403);
        }

        return $this->issueMobileToken($user, $user->wasRecentlyCreated, 'Signed in with Apple.');
    }

    public function linkApple(Request $request, AppleIdentityTokenVerifier $verifier): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string', 'max:10000'],
            'nonce' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
        ]);
        if (! config('services.apple.bundle_id') && ! config('services.apple.service_id')) {
            return $this->fail('Apple sign-in is not configured.', 503);
        }
        try {
            $identity = $verifier->verify($validated['id_token'], $validated['nonce']);
        } catch (\Throwable) {
            return $this->fail('Invalid Apple identity token.', 401);
        }
        if (! Cache::pull('auth:apple:nonce:'.hash('sha256', $validated['nonce']))) {
            return $this->fail('Apple sign-in challenge expired or was already used.', 401);
        }
        if (empty($identity['sub'])) {
            return $this->fail('Invalid Apple identity token.', 401);
        }

        $linked = DB::transaction(function () use ($request, $identity): bool {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (($user->apple_id && $user->apple_id !== $identity['sub']) ||
                User::where('apple_id', $identity['sub'])->whereKeyNot($user->id)->exists()) {
                return false;
            }
            $user->update(['apple_id' => $identity['sub']]);
            return true;
        }, 3);

        return $linked ? $this->ok(['linked' => true]) : $this->fail('Apple identity is already linked to another account.', 409);
    }

    private function issueMobileToken(User $user, bool $isNew, string $message): JsonResponse
    {
        $user->tokens()->where('name', 'mobile')->delete();
        $expirationMinutes = (int) config('sanctum.expiration', 1440);
        $token = $user->createToken('mobile', ['*'], now()->addMinutes($expirationMinutes))->plainTextToken;

        return $this->ok([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expirationMinutes * 60,
            'is_new_user' => $isNew,
            'profile_complete' => (bool) $user->profile_complete,
        ], $message);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logged out successfully.');
    }

    /** Accepts +20 1x…, 20 1x…, 01x…, 1x… → +201xxxxxxxxx or null. */
    private function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (str_starts_with($digits, '20')) {
            $digits = substr($digits, 2);
        }
        $digits = ltrim($digits, '0');

        return preg_match('/^1[0125]\d{8}$/', $digits) ? '+20'.$digits : null;
    }
}
