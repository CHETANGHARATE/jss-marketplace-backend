<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service
{
    protected string $authKey;
    protected string $templateId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->authKey = config('services.msg91.auth_key', env('MSG91_AUTH_KEY', ''));
        $this->templateId = config('services.msg91.template_id', env('MSG91_TEMPLATE_ID', ''));
        $this->baseUrl = 'https://control.msg91.com/api/v5/otp';
    }

    /**
     * Normalize Indian mobile number to canonical format +91XXXXXXXXXX
     */
    public static function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/[^\d]/', '', $mobile);
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+' . $digits;
        }
        if (!str_starts_with($mobile, '+') && strlen($digits) > 10) {
            return '+' . $digits;
        }
        return '+' . $digits;
    }

    /**
     * Format mobile for MSG91 API requests (without leading + e.g. 919876543210)
     */
    public static function formatForMsg91(string $normalizedMobile): string
    {
        return ltrim($normalizedMobile, '+');
    }

    /**
     * Send OTP via MSG91 API v5
     */
    public function sendOtp(string $mobile): array
    {
        $normalizedMobile = self::normalizeMobile($mobile);
        $msg91Mobile = self::formatForMsg91($normalizedMobile);

        if (empty($this->authKey)) {
            Log::error('MSG91_CONFIG_ERROR: MSG91_AUTH_KEY is not configured in environment.');
            return [
                'success' => false,
                'message' => 'SMS gateway configuration missing. Please contact platform support.',
            ];
        }

        try {
            $payload = [
                'template_id' => $this->templateId,
                'mobile' => $msg91Mobile,
                'otp_length' => 6,
                'otp_expiry' => 5,
            ];

            Log::info("MSG91_SEND_OTP_REQUEST: Mobile [{$msg91Mobile}], TemplateId [{$this->templateId}]");

            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, $payload);

            $data = $response->json();
            Log::info("MSG91_SEND_OTP_RESPONSE: Status [{$response->status()}], Body: " . json_encode($data));

            if ($response->successful() && isset($data['type']) && $data['type'] === 'success') {
                return [
                    'success' => true,
                    'request_id' => $data['request_id'] ?? $data['message'] ?? uniqid('msg91_'),
                    'message' => 'OTP sent successfully',
                ];
            }

            $errMsg = $data['message'] ?? 'Failed to dispatch OTP via SMS gateway.';
            Log::error("MSG91_SEND_OTP_FAILED: {$errMsg}");
            return [
                'success' => false,
                'message' => 'Unable to send OTP right now. Please try again later.',
            ];
        } catch (\Throwable $e) {
            Log::error("MSG91_SEND_OTP_EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to send OTP right now. Please check your network and try again.',
            ];
        }
    }

    /**
     * Verify OTP via MSG91 API v5
     */
    public function verifyOtp(string $mobile, string $otp, ?string $reqId = null): array
    {
        $normalizedMobile = self::normalizeMobile($mobile);
        $msg91Mobile = self::formatForMsg91($normalizedMobile);

        if (empty($this->authKey)) {
            return [
                'success' => false,
                'message' => 'SMS gateway configuration missing.',
            ];
        }

        try {
            $queryParams = [
                'otp' => $otp,
                'mobile' => $msg91Mobile,
            ];
            if ($reqId) {
                $queryParams['req_id'] = $reqId;
            }

            Log::info("MSG91_VERIFY_OTP_REQUEST: Mobile [{$msg91Mobile}], ReqId [{$reqId}]");

            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/verify", $queryParams);

            $data = $response->json();
            Log::info("MSG91_VERIFY_OTP_RESPONSE: Status [{$response->status()}], Body: " . json_encode($data));

            if ($response->successful() && isset($data['type']) && $data['type'] === 'success') {
                return [
                    'success' => true,
                    'message' => 'OTP verified successfully.',
                ];
            }

            $msg = $data['message'] ?? '';
            if (str_contains(strtolower($msg), 'expire')) {
                return [
                    'success' => false,
                    'message' => 'Your OTP has expired. Please request a new OTP.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Incorrect OTP. Please check the OTP and try again.',
            ];
        } catch (\Throwable $e) {
            Log::error("MSG91_VERIFY_OTP_EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to verify OTP right now. Please try again.',
            ];
        }
    }

    /**
     * Resend / Retry OTP via MSG91 API v5
     */
    public function resendOtp(string $mobile, ?string $reqId = null): array
    {
        $normalizedMobile = self::normalizeMobile($mobile);
        $msg91Mobile = self::formatForMsg91($normalizedMobile);

        if (empty($this->authKey)) {
            return [
                'success' => false,
                'message' => 'SMS gateway configuration missing.',
            ];
        }

        try {
            $params = [
                'authkey' => $this->authKey,
                'retrytype' => 'text',
                'mobile' => $msg91Mobile,
            ];
            if ($reqId) {
                $params['req_id'] = $reqId;
            }

            Log::info("MSG91_RETRY_OTP_REQUEST: Mobile [{$msg91Mobile}], ReqId [{$reqId}]");

            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/retry", $params);

            $data = $response->json();
            Log::info("MSG91_RETRY_OTP_RESPONSE: Status [{$response->status()}], Body: " . json_encode($data));

            if ($response->successful() && isset($data['type']) && $data['type'] === 'success') {
                return [
                    'success' => true,
                    'request_id' => $data['request_id'] ?? $reqId,
                    'message' => 'OTP resent successfully.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Unable to resend OTP right now. Please try again later.',
            ];
        } catch (\Throwable $e) {
            Log::error("MSG91_RETRY_OTP_EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to resend OTP right now. Please try again later.',
            ];
        }
    }

    /**
     * Send Transactional Notification (SMS/Flow) via MSG91
     */
    public function sendTransactionalMessage(string $mobile, string $message): array
    {
        $normalizedMobile = self::normalizeMobile($mobile);
        $msg91Mobile = self::formatForMsg91($normalizedMobile);

        if (empty($this->authKey)) {
            Log::warning('MSG91_CONFIG: MSG91_AUTH_KEY missing, skipping external SMS dispatch.');
            return [
                'success' => false,
                'message' => 'SMS gateway configuration missing.',
            ];
        }

        try {
            Log::info("MSG91_TRANSACTIONAL_SMS_DISPATCH: Mobile [{$msg91Mobile}], Message [{$message}]");

            // Standard MSG91 v5 SMS endpoint / flow fallback
            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post('https://control.msg91.com/api/v5/flow/', [
                'template_id' => $this->templateId,
                'short_url' => '1',
                'recipients' => [
                    [
                        'mobiles' => $msg91Mobile,
                        'message' => $message,
                    ]
                ]
            ]);

            $data = $response->json();
            Log::info("MSG91_TRANSACTIONAL_RESPONSE: Status [{$response->status()}], Body: " . json_encode($data));

            return [
                'success' => $response->successful(),
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error("MSG91_TRANSACTIONAL_SMS_EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

