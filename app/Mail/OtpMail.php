<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

if (!class_exists('App\Mail\OtpMail', false)) {

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
     * Build the message using classic Laravel Mailable API.
     */
    public function build()
    {
        $appName = config('app.name', 'JSS Solutions');
        $subject = $this->type === 'email_verification'
            ? "Verify Your Email Address - {$appName}"
            : "Password Reset Verification Code - {$appName}";

        return $this
            ->subject($subject)
            ->text('emails.otp_plain');
    }
}

}
