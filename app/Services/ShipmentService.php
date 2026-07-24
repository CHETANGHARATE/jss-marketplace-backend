<?php

namespace App\Services;

use App\Events\ShipmentStatusUpdatedEvent;
use App\Logistics\CourierManager;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ShipmentService
{
    protected CourierManager $courierManager;

    public function __construct(CourierManager $courierManager)
    {
        $this->courierManager = $courierManager;
    }

    /**
     * Create a shipment for an order with courier label & tracking AWB.
     */
    public function createShipmentForOrder(Order $order, int $courierId, ?int $warehouseId = null): Shipment
    {
        $courier = Courier::findOrFail($courierId);

        return DB::transaction(function () use ($order, $courier, $warehouseId) {
            $shipmentNumber = 'SHP-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $shipment = Shipment::create([
                'shipment_number' => $shipmentNumber,
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'warehouse_id' => $warehouseId,
                'status' => 'pending',
                'weight_kg' => 0.50,
                'shipping_cost' => $order->shipping_amount,
            ]);

            // Call Courier Driver to generate Label & AWB
            $driver = $this->courierManager->driver($courier->code);
            $labelData = $driver->createShipmentLabel($shipment);

            $shipment->update([
                'tracking_number' => $labelData['tracking_number'] ?? null,
                'status' => 'label_created',
            ]);

            // Create initial timeline log
            ShipmentLog::create([
                'shipment_id' => $shipment->id,
                'status' => 'label_created',
                'location' => 'Fulfillment Center',
                'description' => "Shipping label created via {$courier->name}. AWB: {$shipment->tracking_number}",
            ]);

            return $shipment->fresh(['courier', 'order', 'logs']);
        });
    }

    /**
     * Update shipment status and record timeline event log.
     */
    public function updateShipmentStatus(Shipment $shipment, string $status, ?string $location = null, ?string $description = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $status, $location, $description) {
            $updateData = ['status' => $status];

            if (in_array($status, ['picked_up', 'in_transit']) && !$shipment->shipped_at) {
                $updateData['shipped_at'] = now();
            }

            if ($status === 'delivered') {
                $updateData['delivered_at'] = now();
            }

            $shipment->update($updateData);

            ShipmentLog::create([
                'shipment_id' => $shipment->id,
                'status' => $status,
                'location' => $location ?? 'In Transit Hub',
                'description' => $description ?? "Shipment status updated to '{$status}'.",
            ]);

            event(new ShipmentStatusUpdatedEvent($shipment, $status));

            return $shipment->fresh(['logs', 'courier', 'order']);
        });
    }
}
