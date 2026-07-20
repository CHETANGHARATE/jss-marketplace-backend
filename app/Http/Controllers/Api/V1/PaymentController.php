<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initiate payment order for frontend checkout popup.
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $order = Order::where('user_id', $request->user()->id)
                ->where('id', $validated['order_id'])
                ->firstOrFail();

            $payload = $this->paymentService->initiatePayment($order, $validated['gateway'] ?? 'razorpay');

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated.',
                'data' => $payload,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify payment signature after checkout popup completes.
     */
    public function verify(VerifyPaymentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $order = Order::where('user_id', $request->user()->id)
                ->where('id', $validated['order_id'])
                ->firstOrFail();

            $payment = $this->paymentService->verifyAndCapturePayment($order, $validated['gateway'], $validated['payload']);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and captured successfully.',
                'data' => new PaymentResource($payment),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * View single payment details.
     */
    public function show(Request $request, string $paymentNumber): JsonResponse
    {
        $payment = Payment::where('user_id', $request->user()->id)
            ->where('payment_number', $paymentNumber)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ], 200);
    }

    /**
     * Gateway Webhook Handler (Public, Signature Verified).
     */
    public function webhook(Request $request, string $gateway): JsonResponse
    {
        try {
            $rawBody = $request->getContent();
            $signatureHeader = $request->header('X-Razorpay-Signature') 
                ?? $request->header('Stripe-Signature') 
                ?? $request->header('X-Signature') 
                ?? '';

            $result = $this->paymentService->handleWebhook($gateway, $rawBody, $signatureHeader);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received.',
                'result' => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
