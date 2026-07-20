<?php

namespace App\Listeners;

use App\Events\ShipmentStatusUpdatedEvent;

class UpdateOrderOnShipmentStatusListener
{
    public function handle(ShipmentStatusUpdatedEvent $event): void
    {
        $shipment = $event->shipment;
        $order = $shipment->order;

        if (!$order) {
            return;
        }

        match ($event->status) {
            'in_transit', 'picked_up' => $order->update(['status' => 'shipped']),
            'delivered' => $order->update(['status' => 'delivered']),
            default => null,
        };
    }
}
