<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdatedEvent
{
    use Dispatchable, SerializesModels;

    public Shipment $shipment;
    public string $status;

    public function __construct(Shipment $shipment, string $status)
    {
        $this->shipment = $shipment;
        $this->status = $status;
    }
}
