<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Services\B2BPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTierController extends Controller
{
    /**
     * Get wholesale pricing tiers and MOQ settings for a product (Feature 50 & 52).
     */
    public function getTiers(int $productId): JsonResponse
    {
        $product = Product::with('priceTiers')->findOrFail($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'is_wholesale_enabled' => (bool) $product->is_wholesale_enabled,
                'wholesale_moq' => (int) ($product->wholesale_moq ?: 1),
                'tiers' => $product->priceTiers,
            ],
        ], 200);
    }

    /**
     * Set or update wholesale pricing tiers and MOQ for a product (Admin & Verified Sellers).
     */
    public function syncTiers(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        // Authorization check: User must be admin or the owner seller of this product
        $user = $request->user();
        if ($user->role !== 'admin' && $product->seller_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'is_wholesale_enabled' => 'required|boolean',
            'wholesale_moq' => 'nullable|integer|min:1',
            'tiers' => 'nullable|array',
            'tiers.*.min_quantity' => 'required|integer|min:1',
            'tiers.*.max_quantity' => 'nullable|integer',
            'tiers.*.unit_price' => 'required|numeric|min:0.01',
        ]);

        $product->update([
            'is_wholesale_enabled' => $validated['is_wholesale_enabled'],
            'wholesale_moq' => $validated['wholesale_moq'] ?? 1,
        ]);

        // Replace / sync price tiers
        if (isset($validated['tiers'])) {
            // Remove existing tiers
            $product->priceTiers()->delete();

            foreach ($validated['tiers'] as $tier) {
                // Ensure min <= max if max is provided
                $maxQty = !empty($tier['max_quantity']) ? (int) $tier['max_quantity'] : null;
                if ($maxQty !== null && $maxQty < (int) $tier['min_quantity']) {
                    continue; // Skip invalid tier
                }

                ProductPriceTier::create([
                    'product_id' => $product->id,
                    'min_quantity' => (int) $tier['min_quantity'],
                    'max_quantity' => $maxQty,
                    'unit_price' => (float) $tier['unit_price'],
                    'is_active' => true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Wholesale pricing tiers updated successfully.',
            'data' => [
                'product_id' => $product->id,
                'is_wholesale_enabled' => (bool) $product->is_wholesale_enabled,
                'wholesale_moq' => (int) $product->wholesale_moq,
                'tiers' => $product->fresh('priceTiers')->priceTiers,
            ],
        ], 200);
    }

    /**
     * Calculate dynamic volume price for a given quantity (Features 50 & 52).
     */
    public function calculatePrice(Request $request, int $productId, B2BPricingService $pricingService): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $quantity = max(1, (int) $request->query('quantity', 1));
        $user = $request->user('sanctum');

        $priceData = $pricingService->calculateItemPrice($product, $quantity, $user);
        $moqCheck = $pricingService->validateMoq($product, $quantity, (bool) $request->query('wholesale'));

        return response()->json([
            'success' => true,
            'data' => array_merge($priceData, [
                'moq_validation' => $moqCheck,
            ]),
        ], 200);
    }
}
