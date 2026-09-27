<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;
use App\Services\OrderNotificationService;
use App\Services\VendorCommissionService;

class UpdateOrderOnPaymentSuccessListener
{
    protected VendorCommissionService $commissionService;
    protected OrderNotificationService $orderNotificationService;

    public function __construct(
        VendorCommissionService $commissionService,
        OrderNotificationService $orderNotificationService
    ) {
        $this->commissionService = $commissionService;
        $this->orderNotificationService = $orderNotificationService;
    }

    public function handle(PaymentSuccessEvent $event): void
    {
        $payment = $event->payment;
        $order = $payment->order;

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
            ]);

            // Credit vendor wallet balances for this order
            $this->commissionService->processOrderCommission($order);

            // Server-Authoritative & Idempotent Order Confirmed Notification
            $this->orderNotificationService->notifyPaymentConfirmed($order, $payment);
        }
    }
}
