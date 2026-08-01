<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;
    public string $type;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otpCode, string $type = 'email_verification')
    {
        $this->otpCode = $otpCode;
        $this->type = $type;
    }

    /**
     * Get the message envelope with explicit From address & name alignment.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name', 'JSS Marketplace');
        $fromAddress = config('mail.from.address', 'no-reply@jsssolutions.in');
        $fromName = config('mail.from.name', 'JSS Marketplace');

        $subject = $this->type === 'email_verification'
            ? "Verify Your Email Address - {$appName}"
            : "Password Reset Verification Code - {$appName}";

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     * Combines HTML view ('emails.otp') with Plain Text fallback ('emails.otp_plain')
     * into a standard multipart/alternative MIME message for maximum email deliverability.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            text: 'emails.otp_plain',
            with: [
                'otpCode' => $this->otpCode,
                'type' => $this->type,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
