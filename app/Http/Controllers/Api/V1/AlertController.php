<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BackInStockSubscription;
use App\Models\PriceDropAlert;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Get user's active price drop alerts (Feature 40).
     */
    public function getPriceDropAlerts(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $alerts = PriceDropAlert::where('user_id', $userId)
            ->with(['product.primaryImage', 'product.brand'])
            ->latest()
            ->get();

        $data = $alerts->map(function ($alert) {
            $product = $alert->product;
            if (!$product) return null;

            $currentPrice = (float) ($product->sale_price ?? $product->price);

            return [
                'id' => $alert->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'product_image' => $product->primaryImage?->url ?? $product->main_image ?? '/images/placeholder.png',
                'brand' => $product->brand?->name,
                'initial_price' => (float) $alert->initial_price,
                'target_price' => $alert->target_price ? (float) $alert->target_price : null,
                'current_price' => $currentPrice,
                'status' => $alert->status,
                'created_at' => $alert->created_at,
                'triggered_at' => $alert->triggered_at,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Subscribe to price drop alert for a product (Feature 40).
     */
    public function subscribePriceDrop(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'target_price' => 'nullable|numeric|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $currentPrice = (float) ($product->sale_price ?? $product->price);

        $alert = PriceDropAlert::updateOrCreate(
            [
                'user_id' => $userId,
                'product_id' => $product->id,
            ],
            [
                'initial_price' => $currentPrice,
                'target_price' => $validated['target_price'] ?? null,
                'status' => 'active',
                'triggered_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Price drop alert set for '{$product->name}'.",
            'data' => [
                'id' => $alert->id,
                'product_id' => $product->id,
                'initial_price' => $alert->initial_price,
                'target_price' => $alert->target_price,
                'status' => $alert->status,
            ],
        ], 200);
    }

    /**
     * Cancel/delete a price drop alert (Feature 40).
     */
    public function cancelPriceDrop(Request $request, int $productId): JsonResponse
    {
        $userId = $request->user()->id;

        PriceDropAlert::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Price drop alert cancelled.',
        ], 200);
    }

    /**
     * Get user's back in stock subscriptions (Feature 41).
     */
    public function getBackInStockSubscriptions(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $subscriptions = BackInStockSubscription::where('user_id', $userId)
            ->with(['product.primaryImage', 'product.brand'])
            ->latest('subscribed_at')
            ->get();

        $data = $subscriptions->map(function ($sub) {
            $product = $sub->product;
            if (!$product) return null;

            return [
                'id' => $sub->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'product_image' => $product->primaryImage?->url ?? $product->main_image ?? '/images/placeholder.png',
                'brand' => $product->brand?->name,
                'current_stock' => $product->stock_quantity,
                'in_stock' => $product->stock_quantity > 0,
                'price' => (float) ($product->sale_price ?? $product->price),
                'status' => $sub->status,
                'subscribed_at' => $sub->subscribed_at,
                'notified_at' => $sub->notified_at,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Subscribe to back-in-stock alert (Feature 41).
     */
    public function subscribeBackInStock(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $subscription = BackInStockSubscription::updateOrCreate(
            [
                'user_id' => $userId,
                'product_id' => $product->id,
            ],
            [
                'status' => 'active',
                'subscribed_at' => now(),
                'notified_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "You will be notified as soon as '{$product->name}' is back in stock.",
            'data' => [
                'id' => $subscription->id,
                'product_id' => $product->id,
                'status' => $subscription->status,
            ],
        ], 200);
    }

    /**
     * Cancel/delete a back-in-stock subscription (Feature 41).
     */
    public function cancelBackInStock(Request $request, int $productId): JsonResponse
    {
        $userId = $request->user()->id;

        BackInStockSubscription::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Back in stock alert cancelled.',
        ], 200);
    }
}
