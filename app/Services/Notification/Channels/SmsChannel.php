<?php

namespace App\Services\Notification\Channels;

use App\Models\User;
use App\Services\Msg91Service;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    protected Msg91Service $msg91Service;

    public function __construct(Msg91Service $msg91Service)
    {
        $this->msg91Service = $msg91Service;
    }

    /**
     * Dispatch Transactional SMS via MSG91.
     */
    public function send(User $user, string $eventKey, ?string $subject, string $body, array $data = []): array
    {
        $mobile = $data['phone'] ?? $data['mobile'] ?? $user->mobile;
        if (empty($mobile)) {
            return [
                'success' => false,
                'provider' => 'msg91',
                'error' => 'Recipient has no valid mobile number.',
            ];
        }

        try {
            $formattedMessage = $subject ? "{$subject}\n{$body}" : $body;
            $res = $this->msg91Service->sendTransactionalMessage($mobile, $formattedMessage);

            if ($res['success']) {
                Log::info("SMS_CHANNEL_SENT: To [{$mobile}]");
                return [
                    'success' => true,
                    'provider' => 'msg91',
                    'provider_message_id' => $res['data']['request_id'] ?? ('sms_' . uniqid()),
                    'response' => $res['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'provider' => 'msg91',
                'error' => $res['message'] ?? 'SMS dispatch failed via provider.',
            ];
        } catch (\Throwable $e) {
            Log::warning("SMS_CHANNEL_EXCEPTION to [{$mobile}]: " . $e->getMessage());
            return [
                'success' => false,
                'provider' => 'msg91',
                'error' => $e->getMessage(),
            ];
        }
    }
}
