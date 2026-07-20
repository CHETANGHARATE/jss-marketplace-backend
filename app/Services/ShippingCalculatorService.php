<?php

namespace App\Services;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;

class ShippingCalculatorService
{
    /**
     * Dynamically calculate shipping rate for a destination pincode & cart total.
     */
    public function calculateRate(string $pincode, float $cartTotal = 0.0, float $weightKg = 0.5): array
    {
        // 1. Find matching zone by pincode
        $zone = ShippingZone::where('is_active', true)
            ->where(function ($q) use ($pincode) {
                $q->whereJsonContains('pincodes', $pincode)
                  ->orWhereNull('pincodes');
            })
            ->first();

        // 2. Resolve shipping method
        $method = $zone 
            ? ShippingMethod::where('shipping_zone_id', $zone->id)->where('is_active', true)->first()
            : ShippingMethod::where('is_active', true)->first();

        if (!$method) {
            return [
                'shipping_cost' => 50.00, // Default fallback
                'is_free' => false,
                'estimated_days' => 3,
                'zone_name' => 'Standard Shipping Zone',
            ];
        }

        // Check Free Shipping Threshold
        if ($method->free_shipping_threshold && $cartTotal >= $method->free_shipping_threshold) {
            return [
                'shipping_cost' => 0.00,
                'is_free' => true,
                'estimated_days' => $method->estimated_days,
                'zone_name' => $zone?->name ?? 'Standard Shipping Zone',
                'method_name' => $method->name,
            ];
        }

        $calculatedCost = $method->base_cost + ($weightKg * $method->cost_per_kg);

        return [
            'shipping_cost' => (float) round($calculatedCost, 2),
            'is_free' => false,
            'estimated_days' => $method->estimated_days,
            'zone_name' => $zone?->name ?? 'Standard Shipping Zone',
            'method_name' => $method->name,
        ];
    }
}
