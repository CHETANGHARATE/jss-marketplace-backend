<?php

namespace App\Console\Commands;

use App\Models\AbandonedCartReminder;
use App\Models\Cart;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAbandonedCartsCommand extends Command
{
    protected $signature = 'cart:process-abandoned';
    protected $description = 'Scan and send recovery reminders for carts inactive past the threshold';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Starting abandoned cart recovery scan...');

        // Find carts updated between 2 hours and 48 hours ago
        $cutoffTime = now()->subHours(2);
        $maxTime = now()->subHours(48);

        $abandonedCarts = Cart::where('updated_at', '<=', $cutoffTime)
            ->where('updated_at', '>=', $maxTime)
            ->whereNotNull('user_id')
            ->with(['items.product', 'user'])
            ->get();

        $processed = 0;

        foreach ($abandonedCarts as $cart) {
            $user = $cart->user;
            if (!$user || $cart->items->isEmpty()) {
                continue;
            }

            // Check total cart value
            $cartTotal = $cart->items->sum(function ($item) {
                $price = $item->product?->sale_price ?? $item->product?->price ?? 0;
                return $price * $item->quantity;
            });

            if ($cartTotal <= 0) continue;

            // Check if reminder was already sent recently (24 hour cooldown)
            $existingReminder = AbandonedCartReminder::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($existingReminder && $existingReminder->last_reminded_at && $existingReminder->last_reminded_at->gt(now()->subHours(24))) {
                continue;
            }

            $reminder = AbandonedCartReminder::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cart_total' => $cartTotal,
                    'item_count' => $cart->items->count(),
                    'cart_snapshot' => $cart->items->map(fn($i) => [
                        'product_id' => $i->product_id,
                        'name' => $i->product?->name,
                        'quantity' => $i->quantity,
                    ])->toArray(),
                    'status' => 'pending',
                    'last_reminded_at' => now(),
                ]
            );

            $idempotencyKey = "abandoned_cart_{$user->id}_" . now()->format('Ymd');

            $notificationService->send(
                'abandoned_cart',
                $user,
                [
                    'name' => $user->name,
                    'amount' => $cartTotal,
                    'item_count' => $cart->items->count(),
                ],
                null,
                $idempotencyKey
            );

            $reminder->update(['status' => 'sent']);
            $processed++;
        }

        $this->info("Processed {$processed} abandoned cart reminders.");
        return Command::SUCCESS;
    }
}
