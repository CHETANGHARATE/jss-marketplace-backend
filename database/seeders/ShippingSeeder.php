<?php

namespace Database\Seeders;

use App\Models\Courier;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Couriers
        $couriers = [
            ['name' => 'Delhivery Express', 'code' => 'DELHIVERY', 'contact_phone' => '+911246719500'],
            ['name' => 'Blue Dart Logistics', 'code' => 'BLUEDART', 'contact_phone' => '+9118602331234'],
            ['name' => 'Shiprocket Aggregator', 'code' => 'SHIPROCKET', 'contact_phone' => '+919911974444'],
            ['name' => 'Local Merchant Delivery', 'code' => 'LOCAL', 'contact_phone' => '+919876543210'],
        ];

        foreach ($couriers as $cData) {
            Courier::updateOrCreate(['code' => $cData['code']], $cData);
        }

        // 2. Shipping Zones
        $westZone = ShippingZone::updateOrCreate(
            ['code' => 'ZONE-WEST'],
            [
                'name' => 'West India Zone',
                'countries' => ['India'],
                'states' => ['Maharashtra', 'Gujarat', 'Goa'],
                'pincodes' => ['400001', '400093', '380001', '403001'],
                'is_active' => true,
            ]
        );

        $northZone = ShippingZone::updateOrCreate(
            ['code' => 'ZONE-NORTH'],
            [
                'name' => 'North India Zone',
                'countries' => ['India'],
                'states' => ['Delhi', 'Haryana', 'Punjab', 'Uttar Pradesh'],
                'pincodes' => ['110001', '122001', '160017', '201301'],
                'is_active' => true,
            ]
        );

        // 3. Shipping Methods
        ShippingMethod::updateOrCreate(
            ['code' => 'STD-WEST'],
            [
                'shipping_zone_id' => $westZone->id,
                'name' => 'Standard Ground Delivery',
                'base_cost' => 50.00,
                'cost_per_kg' => 20.00,
                'free_shipping_threshold' => 999.00,
                'estimated_days' => 3,
                'is_active' => true,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'STD-NORTH'],
            [
                'shipping_zone_id' => $northZone->id,
                'name' => 'Standard Regional Delivery',
                'base_cost' => 70.00,
                'cost_per_kg' => 25.00,
                'free_shipping_threshold' => 999.00,
                'estimated_days' => 4,
                'is_active' => true,
            ]
        );
    }
}
