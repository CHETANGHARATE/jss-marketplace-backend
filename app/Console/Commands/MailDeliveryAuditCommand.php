<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class MailDeliveryAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Usage: php artisan mail:audit --to=test@gmail.com
     */
    protected $signature = 'mail:audit {--to= : Recipient email address to send the test OTP}';

    /**
     * The console command description.
     */
    protected $description = 'Audit SMTP configuration and send a test OTP email to verify end-to-end mail delivery';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = $this->option('to');

        if (!$recipient || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid recipient email. Example: php artisan mail:audit --to=test@gmail.com');
            return Command::FAILURE;
        }

        $this->info('');
        $this->info('========================================');
        $this->info('  JSS Marketplace - Mail Delivery Audit');
        $this->info('========================================');
        $this->info('');

        // 1. Dump loaded SMTP configuration
        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $encryption = config('mail.mailers.smtp.encryption');
        $username = config('mail.mailers.smtp.username');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->info('[1] Loaded SMTP Configuration:');
        $this->line("    Mailer       : {$mailer}");
        $this->line("    Host         : {$host}");
        $this->line("    Port         : {$port}");
        $this->line("    Encryption   : {$encryption}");
        $this->line("    Username     : {$username}");
        $this->line("    From Address : {$fromAddress}");
        $this->line("    From Name    : {$fromName}");
        $this->info('');

        // 2. Verify From Address matches Username (critical for cPanel Exim)
        $this->info('[2] Sender Alignment Check:');
        if ($fromAddress === $username) {
            $this->info("    ✓ PASS — From Address matches SMTP Username ({$fromAddress})");
        } else {
            $this->error("    ✗ FAIL — MISMATCH! From Address [{$fromAddress}] does NOT match SMTP Username [{$username}]");
            $this->warn("    cPanel Exim WILL reject this sender mismatch with 550 or 535 error!");
        }
        $this->info('');

        // 3. Check for rogue config/OtpMail.php file
        $this->info('[3] Rogue File Check:');
        $rogueFile = config_path('OtpMail.php');
        if (file_exists($rogueFile)) {
            $this->error("    ✗ CRITICAL — config/OtpMail.php EXISTS at: {$rogueFile}");
            $this->warn("    This file causes: Cannot declare class App\\Mail\\OtpMail, because the name is already in use");
            $this->warn("    Action: DELETE this file immediately!");
            Log::critical("AUDIT [Mail Audit]: Rogue file detected at config/OtpMail.php — causes OtpMail duplicate class error!");
        } else {
            $this->info("    ✓ PASS — config/OtpMail.php does NOT exist (correct)");
        }
        $this->info('');

        // 4. Check OtpMail class path
        $this->info('[4] OtpMail Class Location Check:');
        $correctPath = app_path('Mail/OtpMail.php');
        if (file_exists($correctPath)) {
            $this->info("    ✓ PASS — app/Mail/OtpMail.php exists at: {$correctPath}");
        } else {
            $this->error("    ✗ FAIL — app/Mail/OtpMail.php MISSING at: {$correctPath}");
        }
        $this->info('');

        // 5. Attempt SMTP connection test
        $this->info('[5] SMTP Dispatch Test:');
        $this->line("    Sending test OTP email to: {$recipient}");
        $testOtp = '999' . rand(100, 999);

        Log::info("AUDIT [MailDeliveryAudit]: Starting SMTP dispatch test to [{$recipient}], Host [{$host}:{$port}], Encryption [{$encryption}], From [{$fromAddress}]");

        try {
            Mail::to($recipient)->send(new OtpMail($testOtp, 'email_verification'));
            $this->info("    ✓ PASS — Mail::to('{$recipient}')->send() completed without exception");
            Log::info("AUDIT [MailDeliveryAudit]: SMTP dispatch SUCCEEDED to [{$recipient}]");
        } catch (\Throwable $e) {
            $this->error("    ✗ FAIL — Exception during SMTP dispatch: " . $e->getMessage());
            Log::error("AUDIT [MailDeliveryAudit]: SMTP dispatch FAILED to [{$recipient}]: " . $e->getMessage());
            Log::error("AUDIT [MailDeliveryAudit Stack Trace]:\n" . $e->getTraceAsString());
            $this->info('');
            $this->error('Audit failed. Check storage/logs/laravel.log for full stack trace.');
            return Command::FAILURE;
        }

        // 6. Final delivery note
        $this->info('');
        $this->info('[6] Post-Dispatch Verification:');
        $this->line("    → SMTP has accepted the message for delivery.");
        $this->line("    → Check Exim logs on hosting: /var/log/exim_mainlog");
        $this->line("      Command: tail -n 50 /var/log/exim_mainlog | grep {$recipient}");
        $this->line("    → If email not received in inbox, check spam folder.");
        $this->line("    → If in spam: DMARC record is likely missing for jsssolutions.in");
        $this->line("    → Required DNS record: _dmarc.jsssolutions.in TXT v=DMARC1; p=none; rua=mailto:dmarc@jsssolutions.in");
        $this->info('');
        $this->info('========================================');
        $this->info('  Audit Complete. Check laravel.log.');
        $this->info('========================================');

        return Command::SUCCESS;
    }
}
