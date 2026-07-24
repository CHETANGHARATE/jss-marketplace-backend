<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create gateway order payload for frontend checkout popup.
     */
    public function createPaymentOrder(Order $order): array;

    /**
     * Verify payment signature after checkout completes on frontend.
     */
    public function verifyPaymentSignature(array $payload): bool;

    /**
     * Verify webhook signature from gateway server headers.
     */
    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader): bool;

    /**
     * Process refund through gateway API.
     */
    public function processRefund(Payment $payment, float $amount, string $reason): array;
}
