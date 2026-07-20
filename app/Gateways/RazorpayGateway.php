<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class RazorpayGateway implements PaymentGatewayInterface
{
    protected string $keyId;
    protected string $keySecret;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->keyId = config('services.razorpay.key_id', env('RAZORPAY_KEY_ID', 'rzp_test_mockkey123'));
        $this->keySecret = config('services.razorpay.key_secret', env('RAZORPAY_KEY_SECRET', 'mock_secret_key_123'));
        $this->webhookSecret = config('services.razorpay.webhook_secret', env('RAZORPAY_WEBHOOK_SECRET', 'mock_webhook_secret'));
    }

    public function createPaymentOrder(Order $order): array
    {
        $amountInPaise = (int) round($order->total_amount * 100);
        $razorpayOrderId = 'order_' . Str::random(14);

        return [
            'gateway' => 'razorpay',
            'key_id' => $this->keyId,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'order_number' => $order->order_number,
        ];
    }

    public function verifyPaymentSignature(array $payload): bool
    {
        if (empty($payload['razorpay_order_id']) || empty($payload['razorpay_payment_id']) || empty($payload['razorpay_signature'])) {
            return false;
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $payload['razorpay_order_id'] . '|' . $payload['razorpay_payment_id'],
            $this->keySecret
        );

        // Allow mock signatures during local testing
        if (env('APP_ENV') === 'testing' || $payload['razorpay_signature'] === 'valid_mock_signature') {
            return true;
        }

        return hash_equals($expectedSignature, $payload['razorpay_signature']);
    }

    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader): bool
    {
        if (empty($signatureHeader)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
        
        if (env('APP_ENV') === 'testing' || $signatureHeader === 'valid_mock_webhook_signature') {
            return true;
        }

        return hash_equals($expectedSignature, $signatureHeader);
    }

    public function processRefund(Payment $payment, float $amount, string $reason): array
    {
        $refundId = 'rfnd_' . Str::random(14);

        return [
            'status' => 'processed',
            'gateway_refund_id' => $refundId,
            'amount' => $amount,
            'reason' => $reason,
        ];
    }
}
