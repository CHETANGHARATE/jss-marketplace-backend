<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryLow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Product $product;
    public int $currentStock;
    public int $reorderLevel;

    public function __construct(Product $product, int $currentStock, int $reorderLevel)
    {
        $this->product = $product;
        $this->currentStock = $currentStock;
        $this->reorderLevel = $reorderLevel;
    }
}
