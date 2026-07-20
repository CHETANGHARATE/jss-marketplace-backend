<?php

namespace App\Listeners;

use App\Events\PaymentSuccessEvent;
use App\Services\VendorCommissionService;

class UpdateOrderOnPaymentSuccessListener
{
    protected VendorCommissionService $commissionService;

    public function __construct(VendorCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
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
        }
    }
}
