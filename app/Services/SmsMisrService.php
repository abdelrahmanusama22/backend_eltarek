<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS Misr (sms.com.eg) gateway client.
 *
 * OTP API:  POST https://smsmisr.com/api/OTP/
 *   environment, username, password, sender, mobile, template, otp
 *   → {"Code":"4901", ...} on success
 * SMS API:  POST https://smsmisr.com/api/SMS/
 *   environment, username, password, sender, mobile, language, message
 *   → {"Code":"1901", ...} on success
 *
 * Driver is configured via SMS_DRIVER:
 *   log     — writes the OTP to the log (local dev, no credentials needed)
 *   smsmisr — hits the live gateway
 */
class SmsMisrService
{
    public function __construct(private readonly array $config)
    {
    }

    public static function make(): self
    {
        return new self(config('services.smsmisr'));
    }

    /** Sends an OTP through the configured driver. Returns true on success. */
    public function sendOtp(string $mobile, string $otp): bool
    {
        if (($this->config['driver'] ?? 'log') === 'log') {
            Log::info("[SMS:log] OTP for {$mobile} is {$otp}");

            return true;
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($this->config['otp_url'], [
                    'environment' => $this->config['environment'],
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                    'sender' => $this->config['sender'],
                    'mobile' => $this->normalize($mobile),
                    'template' => $this->config['otp_template'],
                    'otp' => $otp,
                ]);

            $code = (string) ($response->json('Code') ?? $response->json('code') ?? '');
            $success = in_array($code, ['4901', '1901'], true);
            if (! $success) {
                Log::warning('[SMS:smsmisr] OTP send failed', [
                    'mobile' => $this->normalize($mobile),
                    'code' => $code,
                    'body' => $response->body(),
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('[SMS:smsmisr] OTP send exception: '.$e->getMessage());

            return false;
        }
    }

    /** Plain SMS (notifications, booking confirmations…). */
    public function sendSms(string $mobile, string $message, string $language = 'en'): bool
    {
        if (($this->config['driver'] ?? 'log') === 'log') {
            Log::info("[SMS:log] SMS to {$mobile}: {$message}");

            return true;
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($this->config['sms_url'], [
                    'environment' => $this->config['environment'],
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                    'sender' => $this->config['sender'],
                    'mobile' => $this->normalize($mobile),
                    'language' => $language === 'ar' ? 2 : 1,
                    'message' => $message,
                ]);

            return in_array((string) ($response->json('Code') ?? ''), ['1901'], true);
        } catch (\Throwable $e) {
            Log::error('[SMS:smsmisr] SMS send exception: '.$e->getMessage());

            return false;
        }
    }

    /** SMS Misr expects 2010xxxxxxxx (country code without the plus). */
    private function normalize(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        return str_starts_with($digits, '20') ? $digits : '20'.ltrim($digits, '0');
    }
}
