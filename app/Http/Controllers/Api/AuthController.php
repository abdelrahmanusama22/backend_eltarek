<?php

namespace App\Http\Controllers\Api;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\SmsMisrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        if ($user && !$user->is_active) {
            return $this->fail('Your account has been deactivated. Please contact support.', 403);
        }

        // Rate limit: one OTP per phone per resend window.
        $resendSeconds = (int) config('services.otp.resend_seconds', 60);
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

        $length = max(4, (int) config('services.otp.length', 4));
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds((int) config('services.otp.ttl_seconds', 300)),
        ]);

        if (! SmsMisrService::make()->sendOtp($phone, $code)) {
            return $this->fail('Could not send the verification SMS. Please try again.', 502);
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

        $otp = OtpCode::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            return $this->fail('Invalid or expired OTP.', 400);
        }
        if ($otp->attempts >= (int) config('services.otp.max_attempts', 5)) {
            return $this->fail('Too many attempts. Please request a new code.', 429);
        }

        $otp->increment('attempts');
        if (! Hash::check($request->string('otp'), $otp->code_hash)) {
            return $this->fail('Invalid or expired OTP.', 400);
        }

        $otp->update(['verified_at' => now()]);

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['member_since' => now()->toDateString()],
        );

        if (! $user->is_active) {
            return $this->fail('Your account has been deactivated. Please contact support.', 403);
        }

        $isNew = $user->wasRecentlyCreated;

        // One active token per device family keeps things simple for now.
        $token = $user->createToken('mobile')->plainTextToken;

        return $this->ok([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
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

    /** Social login placeholder until Google/Apple keys exist. */
    public function social(): JsonResponse
    {
        return $this->fail('Social login is coming soon.', 501);
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
