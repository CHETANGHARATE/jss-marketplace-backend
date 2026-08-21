<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRestocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Product $product;
    public int $oldStock;
    public int $newStock;

    public function __construct(Product $product, int $oldStock, int $newStock)
    {
        $this->product = $product;
        $this->oldStock = $oldStock;
        $this->newStock = $newStock;
    }
}
