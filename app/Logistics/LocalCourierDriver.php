<?php

namespace App\Logistics;

use App\Contracts\CourierDriverInterface;
use App\Models\Shipment;

class LocalCourierDriver implements CourierDriverInterface
{
    public function createShipmentLabel(Shipment $shipment): array
    {
        $awbNumber = 'LOCAL-' . rand(100000, 999999);

        return [
            'tracking_number' => $awbNumber,
            'label_url' => null,
            'status' => 'label_created',
        ];
    }

    public function trackShipment(string $trackingNumber): array
    {
        return [
            'tracking_number' => $trackingNumber,
            'current_status' => 'picked_up',
            'events' => [
                ['status' => 'picked_up', 'location' => 'Merchant Store', 'time' => now()->toIso8601String()],
            ]
        ];
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        return true;
    }
}
