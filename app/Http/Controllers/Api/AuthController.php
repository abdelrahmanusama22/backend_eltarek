<?php

namespace App\Http\Controllers\Api;

use Google\Client as GoogleClient;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\SmsMisrService;
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

        $user->update([
            'name' => $validated['full_name'],
            'age' => $validated['age'],
            'city_id' => $validated['city_id'],
            'email' => $validated['email'] ?? $user->email,
            'profile_complete' => true,
        ]);

        return $this->ok($user->fresh()->load('city')->toApi(), 'Profile completed successfully.');
    }

    // -------------------------------------------------- email verification

    /** Verify email address with the 6-digit code sent after registration. */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        if ($user->email_verified_at) {
            return $this->ok($user->toApi(), 'Email already verified.');
        }

        $cacheKey = 'email_verify:'.hash('sha256', $user->email);
        $stored = Cache::get($cacheKey);

        if (! $stored || ! Hash::check($request->string('code')->toString(), $stored)) {
            return $this->fail('Invalid or expired verification code.', 400);
        }

        Cache::forget($cacheKey);
        $user->update(['email_verified_at' => now()]);

        return $this->ok($user->fresh()->load('city')->toApi(), 'Email verified successfully.');
    }

    /** Resend the email verification code (auth required). */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return $this->fail('Email is already verified.', 422);
        }
        if (! $user->email) {
            return $this->fail('No email address on file.', 422);
        }

        $this->sendVerificationCode($user->email);

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

    public function google(Request $request): JsonResponse
    {
        $validated = $request->validate(['id_token' => ['required', 'string', 'max:10000']]);
        
        $clientIds = array_filter([
            config('services.google.client_id'),
            config('services.google.web_client_id'),
            config('services.google.android_client_id'),
        ]);

        if (empty($clientIds)) {
            return $this->fail('Google sign-in is not configured.', 503);
        }

        $payload = false;
        foreach ($clientIds as $clientId) {
            try {
                $client = new GoogleClient(['client_id' => (string) $clientId]);
                $payload = $client->verifyIdToken($validated['id_token']);
                if ($payload) {
                    break;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        if (! $payload || empty($payload['sub']) || empty($payload['email']) || empty($payload['email_verified'])) {
            return $this->fail('Invalid Google identity token.', 401);
        }
        $email = mb_strtolower($payload['email']);
        $user = DB::transaction(function () use ($payload, $email) {
            $user = User::where('google_id', $payload['sub'])->orWhere('email', $email)->lockForUpdate()->first();
            if (! $user) {
                return User::create([
                    'google_id' => $payload['sub'], 'email' => $email,
                    'name' => $payload['name'] ?? $email, 'avatar_url' => $payload['picture'] ?? null,
                    'email_verified_at' => now(), 'member_since' => now()->toDateString(),
                    'profile_complete' => false, 'is_active' => true,
                ]);
            }
            $user->update(['google_id' => $payload['sub'], 'email_verified_at' => $user->email_verified_at ?? now()]);
            return $user;
        }, 3);
        if (! $user->is_active) {
            return $this->fail('Your account has been deactivated.', 403);
        }
        return $this->issueMobileToken($user, $user->wasRecentlyCreated, 'Signed in with Google.');
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


