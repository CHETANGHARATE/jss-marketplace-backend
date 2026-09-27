<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\CheckoutProcessRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class OrderController extends Controller
{
    protected CheckoutService $checkoutService;
    protected OrderService $orderService;

    public function __construct(CheckoutService $checkoutService, OrderService $orderService)
    {
        $this->checkoutService = $checkoutService;
        $this->orderService = $orderService;
    }

    /**
     * Process checkout and place order (supports JSS Coins & Coupons).
     */
    public function checkout(CheckoutProcessRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $order = $this->checkoutService->processCheckout(
                $request->user(),
                $validated['shipping_address_id'],
                $validated['billing_address_id'] ?? null,
                $validated['payment_method'] ?? 'cod',
                $validated['points_to_redeem'] ?? null,
                $validated['coupon_code'] ?? null,
                $validated['shipping_method'] ?? 'standard',
                $validated['cart_items'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => new OrderResource($order),
            ], 201);
        } catch (Throwable $e) {
            Log::error("CHECKOUT_ERROR: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display a listing of customer's order history.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product.primaryImage', 'items.product.sellerStore'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ]
        ], 200);
    }

    /**
     * Resolve customer order by canonical public order number or numeric ID.
     */
    protected function findCustomerOrder(int $userId, string $orderIdentifier, array $with = []): ?Order
    {
        $clean = ltrim(urldecode(trim($orderIdentifier)), '#');

        $query = Order::where('user_id', $userId)
            ->where(function ($q) use ($clean, $orderIdentifier) {
                $q->where('order_number', $clean)
                  ->orWhere('order_number', $orderIdentifier)
                  ->orWhere('order_number', '#' . $clean);
                if (is_numeric($clean)) {
                    $q->orWhere('id', (int) $clean);
                }
            });

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first();
    }

    /**
     * Display single order details by order number.
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->findCustomerOrder($request->user()->id, $orderNumber, [
            'items.product.primaryImage',
            'items.product.sellerStore',
        ]);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ], 200);
    }

    /**
     * Cancel an entire order.
     */
    public function cancel(CancelOrderRequest $request, string $orderNumber): JsonResponse
    {
        try {
            $order = $this->findCustomerOrder($request->user()->id, $orderNumber, ['items']);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            $cancelledOrder = $this->orderService->cancelOrder($order, $request->validated()['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data' => new OrderResource($cancelledOrder),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel an individual line item from a multi-vendor order (Feature 139).
     */
    public function cancelItem(Request $request, string $orderNumber, int $itemId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $order = $this->findCustomerOrder($request->user()->id, $orderNumber, ['items.product.primaryImage']);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            $updatedOrder = $this->orderService->cancelOrderItem(
                $order,
                $itemId,
                $request->input('reason'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Item cancelled successfully.',
                'data' => new OrderResource($updatedOrder),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download Server-Side Generated GST Tax Invoice PDF (Feature 53).
     */
    public function downloadInvoice(Request $request, string $orderNumber)
    {
        $user = $request->user();
        $clean = ltrim(urldecode(trim($orderNumber)), '#');

        // Check ownership or admin privilege
        $query = Order::where(function ($q) use ($clean, $orderNumber) {
                $q->where('order_number', $clean)
                  ->orWhere('order_number', $orderNumber)
                  ->orWhere('order_number', '#' . $clean);
                if (is_numeric($clean)) {
                    $q->orWhere('id', (int) $clean);
                }
            })
            ->with(['items.product.primaryImage', 'items.product.sellerStore', 'user', 'shippingAddress', 'billingAddress']);

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        $order = $query->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or access unauthorized.',
            ], 404);
        }

        try {
            $pdf = Pdf::loadView('invoices.gst_invoice', compact('order'));
            $pdf->setPaper('a4', 'portrait');

            $filename = "Tax_Invoice_{$order->order_number}.pdf";

            return $pdf->download($filename);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF invoice: ' . $e->getMessage(),
            ], 500);
        }
    }
}
