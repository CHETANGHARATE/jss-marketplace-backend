<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Refund;
use App\Services\OrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderReturnController extends Controller
{
    /**
     * Customer returns listing (Feature 36 & 37).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $returns = OrderReturn::where('user_id', $userId)
            ->with(['order', 'orderItem.product.primaryImage'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'total' => $returns->total(),
            ],
        ], 200);
    }

    /**
     * Create a new return request with evidence upload (Features 36 & 37).
     */
    public function store(Request $request, OrderNotificationService $notificationService): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_item_id' => 'nullable|exists:order_items,id',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'evidence_urls' => 'nullable|array',
            'evidence_urls.*' => 'string',
            'pickup_address_snapshot' => 'nullable|array',
        ]);

        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        $refundAmount = 0.00;

        if (!empty($validated['order_item_id'])) {
            $item = OrderItem::where('id', $validated['order_item_id'])
                ->where('order_id', $order->id)
                ->firstOrFail();

            $refundAmount = (float) $item->total_price;
        } else {
            $refundAmount = (float) $order->total_amount;
        }

        $returnNumber = 'RET-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $orderReturn = OrderReturn::create([
            'return_number' => $returnNumber,
            'order_id' => $order->id,
            'order_item_id' => $validated['order_item_id'] ?? null,
            'user_id' => $userId,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'evidence_urls' => $validated['evidence_urls'] ?? [],
            'pickup_address_snapshot' => $validated['pickup_address_snapshot'] ?? $order->shipping_address,
            'status' => 'requested',
            'refund_amount' => $refundAmount,
        ]);

        // Trigger return notification
        try {
            $notificationService->sendReturnStatusNotification($orderReturn);
        } catch (\Throwable $e) {
            // Non-breaking notification
        }

        return response()->json([
            'success' => true,
            'message' => 'Return request submitted successfully. Our team will review and schedule reverse pickup.',
            'data' => $orderReturn->load(['order', 'orderItem.product']),
        ], 201);
    }

    /**
     * Track return and refund progress by return number (Features 36 & 37).
     */
    public function show(Request $request, string $returnNumber): JsonResponse
    {
        $userId = $request->user()->id;

        $orderReturn = OrderReturn::where('return_number', $returnNumber)
            ->where('user_id', $userId)
            ->with(['order', 'orderItem.product.primaryImage'])
            ->firstOrFail();

        // Timeline progression status mapping
        $stages = [
            ['key' => 'requested', 'label' => 'Return Requested', 'completed' => true],
            ['key' => 'approved', 'label' => 'Approved by Merchant', 'completed' => in_array($orderReturn->status, ['approved', 'pickup_scheduled', 'picked_up', 'received', 'inspected', 'approved_for_refund', 'refund_processing', 'refunded'])],
            ['key' => 'pickup_scheduled', 'label' => 'Pickup Scheduled', 'completed' => in_array($orderReturn->status, ['pickup_scheduled', 'picked_up', 'received', 'inspected', 'approved_for_refund', 'refund_processing', 'refunded'])],
            ['key' => 'picked_up', 'label' => 'Item Picked Up', 'completed' => in_array($orderReturn->status, ['picked_up', 'received', 'inspected', 'approved_for_refund', 'refund_processing', 'refunded'])],
            ['key' => 'received', 'label' => 'Received at Warehouse', 'completed' => in_array($orderReturn->status, ['received', 'inspected', 'approved_for_refund', 'refund_processing', 'refunded'])],
            ['key' => 'inspected', 'label' => 'Quality Inspected', 'completed' => in_array($orderReturn->status, ['inspected', 'approved_for_refund', 'refund_processing', 'refunded'])],
            ['key' => 'refunded', 'label' => 'Refund Credited', 'completed' => $orderReturn->status === 'refunded'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'return' => $orderReturn,
                'timeline' => $stages,
                'courier' => [
                    'courier_name' => $orderReturn->courier_name ?? 'Delhivery Reverse Logistics',
                    'tracking_number' => $orderReturn->tracking_number ?? 'DEL-REV-' . $orderReturn->id,
                ],
            ],
        ], 200);
    }

    /**
     * Admin/Vendor: List all return requests.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $query = OrderReturn::with(['user', 'order', 'orderItem.product'])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $returns = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'total' => $returns->total(),
            ],
        ], 200);
    }

    /**
     * Admin/Vendor: Update return request status.
     */
    public function updateStatus(Request $request, int $id, OrderNotificationService $notificationService): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:requested,approved,pickup_scheduled,picked_up,received,inspected,approved_for_refund,refund_processing,refunded,rejected',
            'courier_name' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $orderReturn = OrderReturn::findOrFail($id);

        $orderReturn->update([
            'status' => $validated['status'],
            'courier_name' => $validated['courier_name'] ?? $orderReturn->courier_name,
            'tracking_number' => $validated['tracking_number'] ?? $orderReturn->tracking_number,
            'rejection_reason' => $validated['rejection_reason'] ?? $orderReturn->rejection_reason,
            'processed_at' => in_array($validated['status'], ['refunded', 'rejected']) ? now() : $orderReturn->processed_at,
        ]);

        // If status moved to refunded, generate official Refund record
        if ($validated['status'] === 'refunded') {
            Refund::create([
                'refund_number' => 'REF-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'payment_id' => $orderReturn->order->payment_id ?? 1,
                'order_id' => $orderReturn->order_id,
                'gateway_refund_id' => 'GAT-REF-' . Str::random(10),
                'amount' => $orderReturn->refund_amount,
                'reason' => 'Return refund for ' . $orderReturn->return_number,
                'status' => 'completed',
                'processed_at' => now(),
            ]);
        }

        // Trigger notification
        try {
            $notificationService->sendReturnStatusNotification($orderReturn);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => "Return status updated to {$validated['status']}.",
            'data' => $orderReturn->fresh(['order', 'orderItem.product']),
        ], 200);
    }
}
