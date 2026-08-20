<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

class B2BPricingService
{
    /**
     * Calculate effective unit price based on volume quantity and buyer business status (Feature 50).
     */
    public function calculateItemPrice(Product $product, int $quantity, ?User $user = null, bool $forceWholesale = false): array
    {
        $retailPrice = (float) ($product->offer_price ?? $product->price ?? $product->original_price ?? 0);

        // If product does not have wholesale enabled, return retail price
        if (!$product->is_wholesale_enabled) {
            return [
                'unit_price' => $retailPrice,
                'is_wholesale' => false,
                'tier_applied' => null,
                'total_price' => $retailPrice * $quantity,
                'savings_amount' => 0.00,
                'savings_percent' => 0,
            ];
        }

        // Check if user is a verified business buyer or wholesale mode requested
        $isBusinessBuyer = $user && $user->businessAccount && $user->businessAccount->status === 'verified';
        $eligibleForWholesale = $isBusinessBuyer || $forceWholesale || $quantity >= $product->wholesale_moq;

        if (!$eligibleForWholesale) {
            return [
                'unit_price' => $retailPrice,
                'is_wholesale' => false,
                'tier_applied' => null,
                'total_price' => $retailPrice * $quantity,
                'savings_amount' => 0.00,
                'savings_percent' => 0,
            ];
        }

        // Find applicable volume tier
        $matchedTier = $product->priceTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('min_quantity', 'desc')
            ->first();

        if ($matchedTier) {
            $tierPrice = (float) $matchedTier->unit_price;
            $savingsPerUnit = max(0, $retailPrice - $tierPrice);
            $totalSavings = $savingsPerUnit * $quantity;
            $savingsPercent = $retailPrice > 0 ? round(($savingsPerUnit / $retailPrice) * 100) : 0;

            return [
                'unit_price' => $tierPrice,
                'is_wholesale' => true,
                'tier_applied' => [
                    'id' => $matchedTier->id,
                    'min_quantity' => $matchedTier->min_quantity,
                    'max_quantity' => $matchedTier->max_quantity,
                    'unit_price' => $tierPrice,
                ],
                'total_price' => $tierPrice * $quantity,
                'savings_amount' => $totalSavings,
                'savings_percent' => $savingsPercent,
            ];
        }

        return [
            'unit_price' => $retailPrice,
            'is_wholesale' => false,
            'tier_applied' => null,
            'total_price' => $retailPrice * $quantity,
            'savings_amount' => 0.00,
            'savings_percent' => 0,
        ];
    }

    /**
     * Validate MOQ constraints (Feature 52).
     */
    public function validateMoq(Product $product, int $quantity, bool $wholesaleMode = false): array
    {
        if ($product->is_wholesale_enabled && $wholesaleMode) {
            $moq = (int) ($product->wholesale_moq ?: 1);
            if ($quantity < $moq) {
                return [
                    'valid' => false,
                    'moq' => $moq,
                    'message' => "Minimum Order Quantity (MOQ) for wholesale is {$moq} units.",
                ];
            }
        }

        return [
            'valid' => true,
            'moq' => 1,
            'message' => null,
        ];
    }
}
