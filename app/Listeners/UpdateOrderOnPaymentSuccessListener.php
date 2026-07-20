<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;

class UpdateOrderOnPaymentSuccessListener
{
    public function handle(PaymentSuccessEvent $event): void
    {
        $payment = $event->payment;
        $order = $payment->order;

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
            ]);
        }
    }
}
