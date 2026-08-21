<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;

class ProcessScheduledNotificationsCommand extends Command
{
    protected $signature = 'notifications:process-scheduled';
    protected $description = 'Process and dispatch scheduled promotional and operational notifications';

    public function handle(NotificationService $notificationService): int
    {
        $due = ScheduledNotification::where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->get();

        $count = 0;

        foreach ($due as $scheduled) {
            $scheduled->update(['status' => 'processing']);

            $recipientsQuery = match ($scheduled->target_audience) {
                'verified_buyers' => User::whereHas('orders', fn($q) => $q->where('status', 'delivered')),
                'vendors' => User::where('role', 'vendor'),
                'custom_list' => User::whereIn('id', $scheduled->target_user_ids ?? []),
                default => User::where('role', 'customer'),
            };

            $recipients = $recipientsQuery->get();
            $scheduled->update(['total_recipients' => $recipients->count()]);

            $successCount = 0;
            $failCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    $idempotencyKey = "sched_{$scheduled->id}_user_{$recipient->id}";
                    $notificationService->send(
                        $scheduled->event_key,
                        $recipient,
                        [
                            'subject' => $scheduled->title,
                            'message' => $scheduled->message,
                            'action_url' => $scheduled->action_url,
                        ],
                        $scheduled->channels,
                        $idempotencyKey
                    );
                    $successCount++;
                } catch (\Throwable $e) {
                    $failCount++;
                }
            }

            $scheduled->update([
                'status' => 'completed',
                'successful_deliveries' => $successCount,
                'failed_deliveries' => $failCount,
                'processed_at' => now(),
            ]);

            $count++;
        }

        $this->info("Processed {$count} scheduled notifications.");
        return Command::SUCCESS;
    }
}
