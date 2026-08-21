<?php

namespace App\Services\Notification\Channels;

use App\Models\User;
use App\Services\Msg91Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    protected string $token;
    protected string $phoneNumberId;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('services.whatsapp.token', env('WHATSAPP_TOKEN', ''));
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID', ''));
        $this->apiUrl = "https://graph.facebook.com/v20.0/{$this->phoneNumberId}/messages";
    }

    /**
     * Dispatch WhatsApp Business Notification.
     */
    public function send(User $user, string $eventKey, ?string $subject, string $body, array $data = []): array
    {
        $rawMobile = $data['phone'] ?? $data['mobile'] ?? $user->mobile;
        if (empty($rawMobile)) {
            return [
                'success' => false,
                'provider' => 'whatsapp_cloud',
                'error' => 'Recipient has no valid phone number.',
            ];
        }

        $normalized = Msg91Service::normalizeMobile($rawMobile);
        $cleanPhone = ltrim($normalized, '+'); // e.g. 919876543210

        if (empty($this->token) || empty($this->phoneNumberId)) {
            Log::info("WHATSAPP_CONFIG_NOTICE: WhatsApp API credentials not set in environment. Simulated dispatch to [{$cleanPhone}].");
            return [
                'success' => true,
                'provider' => 'whatsapp_cloud',
                'provider_message_id' => 'wa_sim_' . uniqid(),
                'response' => ['status' => 'simulated', 'recipient' => $cleanPhone],
            ];
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanPhone,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $subject ? "*{$subject}*\n\n{$body}" : $body,
                ],
            ];

            $response = Http::withToken($this->token)->post($this->apiUrl, $payload);
            $resData = $response->json();

            if ($response->successful() && !empty($resData['messages'][0]['id'])) {
                Log::info("WHATSAPP_CHANNEL_SENT: MessageId [{$resData['messages'][0]['id']}] to [{$cleanPhone}]");
                return [
                    'success' => true,
                    'provider' => 'whatsapp_cloud',
                    'provider_message_id' => $resData['messages'][0]['id'],
                    'response' => $resData,
                ];
            }

            $errMsg = $resData['error']['message'] ?? 'WhatsApp API request failed.';
            Log::warning("WHATSAPP_CHANNEL_FAILED: {$errMsg}");
            return [
                'success' => false,
                'provider' => 'whatsapp_cloud',
                'error' => $errMsg,
            ];
        } catch (\Throwable $e) {
            Log::error("WHATSAPP_CHANNEL_EXCEPTION: " . $e->getMessage());
            return [
                'success' => false,
                'provider' => 'whatsapp_cloud',
                'error' => $e->getMessage(),
            ];
        }
    }
}
