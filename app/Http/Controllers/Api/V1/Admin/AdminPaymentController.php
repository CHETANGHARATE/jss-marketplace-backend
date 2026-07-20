<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessRefundRequest;
use App\Http\Resources\PaymentLogResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\RefundResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AdminPaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of marketplace payments across users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['user', 'order']);

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->input('gateway'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $payments = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ]
        ], 200);
    }

    /**
     * Display payment gateway audit logs.
     */
    public function logs(): JsonResponse
    {
        $logs = PaymentLog::with('payment')->latest()->paginate(25);

        return response()->json([
            'success' => true,
            'data' => PaymentLogResource::collection($logs),
        ], 200);
    }

    /**
     * Process refund for an order.
     */
    public function refund(ProcessRefundRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $order = Order::findOrFail($validated['order_id']);

            $refund = $this->paymentService->processRefund($order, (float) $validated['amount'], $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully.',
                'data' => new RefundResource($refund),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
