<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductLaunched
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }
}
