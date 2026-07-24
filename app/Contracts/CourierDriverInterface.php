<?php

namespace App\Contracts;

use App\Models\Shipment;

interface CourierDriverInterface
{
    /**
     * Generate courier shipment label and tracking AWB number.
     */
    public function createShipmentLabel(Shipment $shipment): array;

    /**
     * Fetch real-time tracking logs for an AWB tracking number.
     */
    public function trackShipment(string $trackingNumber): array;

    /**
     * Cancel shipment with courier partner.
     */
    public function cancelShipment(Shipment $shipment): bool;
}
