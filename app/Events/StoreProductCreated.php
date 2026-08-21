<?php

namespace App\Events;

use App\Models\Product;
use App\Models\VendorStore;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreProductCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public VendorStore $store;
    public Product $product;

    public function __construct(VendorStore $store, Product $product)
    {
        $this->store = $store;
        $this->product = $product;
    }
}
