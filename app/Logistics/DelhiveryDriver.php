<?php

namespace App\Logistics;

use App\Contracts\CourierDriverInterface;
use App\Models\Shipment;
use Illuminate\Support\Str;

class DelhiveryDriver implements CourierDriverInterface
{
    public function createShipmentLabel(Shipment $shipment): array
    {
        $awbNumber = 'DEL-' . rand(10000000, 99999999);

        return [
            'tracking_number' => $awbNumber,
            'label_url' => "https://track.delhivery.com/labels/{$awbNumber}.pdf",
            'status' => 'label_created',
        ];
    }

    public function trackShipment(string $trackingNumber): array
    {
        return [
            'tracking_number' => $trackingNumber,
            'current_status' => 'in_transit',
            'events' => [
                ['status' => 'picked_up', 'location' => 'Mumbai Sorting Hub', 'time' => now()->subHours(6)->toIso8601String()],
                ['status' => 'in_transit', 'location' => 'Central Hub Terminal', 'time' => now()->subHours(2)->toIso8601String()],
            ]
        ];
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        return true;
    }
}
