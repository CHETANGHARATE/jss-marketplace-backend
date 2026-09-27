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

        $emailSubject = $subject ?: 'JSS Solutions Marketplace Update';

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
                        ->from(config('mail.from.address', 'no-reply@jsssolutions.in'), config('mail.from.name', 'JSS Marketplace'))
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
                Mail::raw("{$emailSubject}\n\n{$body}\n\nThank you,\nJSS Solutions Marketplace Team\nhttps://jsssolutions.in", function ($message) use ($email, $emailSubject) {
                    $message->to($email)
                        ->from(config('mail.from.address', 'no-reply@jsssolutions.in'), config('mail.from.name', 'JSS Marketplace'))
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
