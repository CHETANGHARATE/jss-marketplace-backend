<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
{
    protected string $publishableKey;
    protected string $secretKey;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->publishableKey = config('services.stripe.key', env('STRIPE_KEY', 'pk_test_mockstripe123'));
        $this->secretKey = config('services.stripe.secret', env('STRIPE_SECRET', 'sk_test_mockstripe123'));
        $this->webhookSecret = config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET', 'whsec_mock123'));
    }

    public function createPaymentOrder(Order $order): array
    {
        $intentId = 'pi_' . Str::random(24);
        $clientSecret = $intentId . '_secret_' . Str::random(10);

        return [
            'gateway' => 'stripe',
            'publishable_key' => $this->publishableKey,
            'client_secret' => $clientSecret,
            'payment_intent_id' => $intentId,
            'amount' => (int) round($order->total_amount * 100),
            'currency' => 'usd',
            'order_number' => $order->order_number,
        ];
    }

    public function verifyPaymentSignature(array $payload): bool
    {
        return !empty($payload['payment_intent_id']);
    }

    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader): bool
    {
        return !empty($signatureHeader);
    }

    public function processRefund(Payment $payment, float $amount, string $reason): array
    {
        return [
            'status' => 'processed',
            'gateway_refund_id' => 're_' . Str::random(24),
            'amount' => $amount,
            'reason' => $reason,
        ];
    }
}
