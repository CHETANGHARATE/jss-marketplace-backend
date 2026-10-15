<?php

namespace App\Services\Notification\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannel
{
    /**
     * Dispatch Transactional Email.
     */
    public function send(User $user, string $eventKey, ?string $subject, string $body, array $data = []): array
    {
        $email = $user->email;
        if (empty($email)) {
            return [
                'success' => false,
                'provider' => 'smtp',
                'error' => 'Recipient has no registered email address.',
            ];
        }

        $emailSubject = $subject ?: (config('brand.name', 'JDS Mart') . ' Update');

        $viewData = [
            'subject' => $emailSubject,
            'body' => $body,
            'data' => $data,
            'user' => $user,
        ];

        try {
            Mail::send(
                ['html' => 'emails.marketplace_layout', 'text' => 'emails.marketplace_layout_plain'],
                $viewData,
                function ($message) use ($email, $emailSubject) {
                    $message->to($email)
                        ->from(config('mail.from.address', 'no-reply@jsssolutions.in'), config('brand.name', config('mail.from.name', 'JDS Mart')))
                        ->subject($emailSubject);
                }
            );

            Log::info("EMAIL_CHANNEL_SENT: To [{$email}], Subject [{$emailSubject}]");

            return [
                'success' => true,
                'provider' => 'smtp',
                'provider_message_id' => 'mail_' . uniqid(),
                'response' => ['recipient' => $email],
            ];
        } catch (\Throwable $e) {
            Log::warning("EMAIL_CHANNEL_HTML_FAILED to [{$email}], trying raw fallback: " . $e->getMessage());

            try {
                $brandName = config('brand.name', 'JDS Mart');
                $brandUrl = config('brand.website_url', 'https://jdsmart.in');
                Mail::raw("{$emailSubject}\n\n{$body}\n\nThank you,\n{$brandName} Team\n{$brandUrl}", function ($message) use ($email, $emailSubject) {
                    $message->to($email)
                        ->from(config('mail.from.address', 'no-reply@jsssolutions.in'), config('brand.name', config('mail.from.name', 'JDS Mart')))
                        ->subject($emailSubject);
                });

                return [
                    'success' => true,
                    'provider' => 'smtp_raw_fallback',
                    'provider_message_id' => 'mail_' . uniqid(),
                    'response' => ['recipient' => $email],
                ];
            } catch (\Throwable $rawError) {
                Log::error("EMAIL_CHANNEL_EXCEPTION to [{$email}]: " . $rawError->getMessage());
                return [
                    'success' => false,
                    'provider' => 'smtp',
                    'error' => $rawError->getMessage(),
                ];
            }
        }
    }
}
