<?php

namespace App\Services;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FlashSaleService
{
    /**
     * Get currently active flash sale campaigns.
     */
    public function getActiveFlashSales()
    {
        $now = now();

        return FlashSale::with(['products.product'])
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->get();
    }

    /**
     * Create a new Flash Sale campaign (Admin).
     */
    public function createFlashSale(array $data, array $products): FlashSale
    {
        return DB::transaction(function () use ($data, $products) {
            $slug = Str::slug($data['title']) . '-' . Str::random(4);

            $flashSale = FlashSale::create([
                'title' => $data['title'],
                'slug' => $slug,
                'discount_percentage' => $data['discount_percentage'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($products as $item) {
                $product = Product::findOrFail($item['product_id']);
                $discount = (float) $data['discount_percentage'];
                $flashPrice = round($product->original_price * (1 - ($discount / 100)), 2);

                FlashSaleProduct::create([
                    'flash_sale_id' => $flashSale->id,
                    'product_id' => $product->id,
                    'flash_price' => $flashPrice,
                    'quantity_limit' => $item['quantity_limit'] ?? 50,
                    'sold_quantity' => 0,
                ]);
            }

            return $flashSale->load('products.product');
        });
    }
}
