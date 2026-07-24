<?php

namespace App\Services;

use App\Events\PaymentSuccessEvent;
use App\Gateways\PaymentGatewayManager;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class PaymentService
{
    protected PaymentGatewayManager $gatewayManager;

    public function __construct(PaymentGatewayManager $gatewayManager)
    {
        $this->gatewayManager = $gatewayManager;
    }

    /**
     * Initiate payment for an order and create gateway checkout payload.
     */
    public function initiatePayment(Order $order, string $gateway = 'razorpay'): array
    {
        if ($order->payment_status === 'paid') {
            throw new Exception("Order '{$order->order_number}' is already paid.");
        }

        return DB::transaction(function () use ($order, $gateway) {
            $driver = $this->gatewayManager->driver($gateway);
            $gatewayPayload = $driver->createPaymentOrder($order);

            $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $transactionId = $gatewayPayload['razorpay_order_id'] ?? $gatewayPayload['payment_intent_id'] ?? null;

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'gateway' => $gateway,
                'transaction_id' => $transactionId,
                'amount' => $order->total_amount,
                'currency' => $gatewayPayload['currency'] ?? 'INR',
                'status' => 'pending',
            ]);

            PaymentLog::create([
                'payment_id' => $payment->id,
                'gateway' => $gateway,
                'event_type' => 'order.created',
                'payload' => $gatewayPayload,
                'ip_address' => request()->ip(),
            ]);

            return array_merge($gatewayPayload, [
                'payment_number' => $paymentNumber,
                'payment_id' => $payment->id,
            ]);
        });
    }

    /**
     * Verify payment signature and capture payment upon frontend checkout completion.
     */
    public function verifyAndCapturePayment(Order $order, string $gateway, array $payload): Payment
    {
        $driver = $this->gatewayManager->driver($gateway);

        if (!$driver->verifyPaymentSignature($payload)) {
            PaymentLog::create([
                'payment_id' => null,
                'gateway' => $gateway,
                'event_type' => 'payment.failed_signature',
                'payload' => $payload,
                'ip_address' => request()->ip(),
            ]);

            throw new Exception("Invalid payment signature verification failed.");
        }

        return DB::transaction(function () use ($order, $gateway, $payload) {
            $transactionId = $payload['razorpay_payment_id'] ?? $payload['payment_intent_id'] ?? $payload['transaction_id'] ?? null;

            $payment = Payment::where('order_id', $order->id)
                ->where('gateway', $gateway)
                ->latest()
                ->first();

            if (!$payment) {
                $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(5));
                $payment = Payment::create([
                    'payment_number' => $paymentNumber,
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'gateway' => $gateway,
                    'amount' => $order->total_amount,
                    'status' => 'pending',
                ]);
            }

            $payment->update([
                'transaction_id' => $transactionId,
                'status' => 'captured',
                'paid_at' => now(),
                'payment_method_details' => $payload,
            ]);

            PaymentLog::create([
                'payment_id' => $payment->id,
                'gateway' => $gateway,
                'event_type' => 'payment.captured',
                'payload' => $payload,
                'ip_address' => request()->ip(),
            ]);

            // Dispatch PaymentSuccessEvent
            event(new PaymentSuccessEvent($payment));

            return $payment->fresh(['order']);
        });
    }

    /**
     * Idempotent Webhook Handler for payment gateways.
     */
    public function handleWebhook(string $gateway, string $rawBody, string $signatureHeader): array
    {
        $driver = $this->gatewayManager->driver($gateway);

        if (!$driver->verifyWebhookSignature($rawBody, $signatureHeader)) {
            throw new Exception("Webhook signature verification failed for gateway [{$gateway}].");
        }

        $payload = json_decode($rawBody, true) ?? [];
        $eventType = $payload['event'] ?? 'webhook.received';

        // Check Idempotency via Audit Log
        $exists = PaymentLog::where('gateway', $gateway)
            ->where('event_type', $eventType)
            ->where('payload->event_id', $payload['id'] ?? null)
            ->exists();

        if ($exists) {
            return ['status' => 'ignored', 'message' => 'Duplicate webhook event ignored (idempotent).'];
        }

        PaymentLog::create([
            'gateway' => $gateway,
            'event_type' => $eventType,
            'payload' => $payload,
            'ip_address' => request()->ip(),
        ]);

        return ['status' => 'processed', 'event' => $eventType];
    }

    /**
     * Process refund through gateway API.
     */
    public function processRefund(Order $order, float $amount, string $reason): Refund
    {
        $payment = Payment::where('order_id', $order->id)->where('status', 'captured')->first();

        if (!$payment) {
            throw new Exception("No captured payment found for order '{$order->order_number}'.");
        }

        return DB::transaction(function () use ($payment, $order, $amount, $reason) {
            $driver = $this->gatewayManager->driver($payment->gateway);
            $refundResult = $driver->processRefund($payment, $amount, $reason);

            $refundNumber = 'REF-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $refund = Refund::create([
                'refund_number' => $refundNumber,
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'gateway_refund_id' => $refundResult['gateway_refund_id'] ?? null,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            $payment->update(['status' => 'refunded']);

            return $refund;
        });
    }
}
