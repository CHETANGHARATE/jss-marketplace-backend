<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seller = User::where('email', 'seller@jss.solutions')->first();
        $electronics = Category::where('slug', 'electronics')->first();
        $smartphones = Category::where('slug', 'smartphones')->first();
        $fashion = Category::where('slug', 'fashion')->first();
        $mensWear = Category::where('slug', 'mens-wear')->first();
        $samsung = Brand::where('slug', 'samsung')->first();
        $nike = Brand::where('slug', 'nike')->first();

        $productsData = [
            [
                'seller_id' => $seller?->id,
                'category_id' => $electronics?->id ?? 1,
                'subcategory_id' => $smartphones?->id,
                'brand_id' => $samsung?->id,
                'sku' => 'JSS-ELEC-SAM-01',
                'name' => ['en' => 'Samsung Galaxy Ultra Pro Wireless Headset', 'hi' => 'सैमसंग गैलेक्सी अल्ट्रा हेडसेट'],
                'slug' => 'samsung-galaxy-ultra-pro-wireless-headset',
                'short_description' => ['en' => 'Active Noise Cancelling Wireless Headphones with 40-hour Battery'],
                'description' => ['en' => 'Experience crystal clear audio with studio-grade active noise cancellation.'],
                'thumbnail' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
                'original_price' => 14999.00,
                'offer_price' => 11999.00,
                'stock_status' => 'in_stock',
                'stock_quantity' => 50,
                'rating' => 4.8,
                'reviews_count' => 124,
                'is_featured' => true,
                'is_trending' => true,
                'status' => 'approved',
                'images' => [
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1484704849700-f032a568e944?auto=format&fit=crop&w=800&q=80',
                ],
                'specifications' => [
                    ['key' => 'Battery Life', 'value' => '40 Hours'],
                    ['key' => 'Connectivity', 'value' => 'Bluetooth 5.3'],
                    ['key' => 'Noise Cancellation', 'value' => 'Active ANC (Hybrid)'],
                ],
                'tags' => ['wireless', 'headphones', 'bluetooth', 'audio', 'samsung'],
            ],
            [
                'seller_id' => $seller?->id,
                'category_id' => $fashion?->id ?? 2,
                'subcategory_id' => $mensWear?->id,
                'brand_id' => $nike?->id,
                'sku' => 'JSS-FASH-NKE-02',
                'name' => ['en' => 'Nike Air Max Breathable Running Shoes', 'hi' => 'नाइकी एयर मैक्स रनिंग शूज़'],
                'slug' => 'nike-air-max-breathable-running-shoes',
                'short_description' => ['en' => 'Lightweight running shoes with responsive air cushion sole'],
                'description' => ['en' => 'Designed for peak performance and ultimate comfort during marathon runs.'],
                'thumbnail' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
                'original_price' => 8999.00,
                'offer_price' => 6999.00,
                'stock_status' => 'in_stock',
                'stock_quantity' => 30,
                'rating' => 4.6,
                'reviews_count' => 89,
                'is_featured' => true,
                'is_trending' => true,
                'status' => 'approved',
                'images' => [
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
                ],
                'specifications' => [
                    ['key' => 'Sole Material', 'value' => 'Rubber Cushion Air Sole'],
                    ['key' => 'Closure', 'value' => 'Lace-Up'],
                ],
                'tags' => ['nike', 'shoes', 'running', 'footwear', 'sports'],
            ],
        ];

        foreach ($productsData as $pData) {
            $images = $pData['images'] ?? [];
            $specifications = $pData['specifications'] ?? [];
            $tags = $pData['tags'] ?? [];

            unset($pData['images'], $pData['specifications'], $pData['tags']);

            $product = Product::updateOrCreate(
                ['sku' => $pData['sku']],
                $pData
            );

            // Seed Images
            foreach ($images as $index => $imgUrl) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'image_url' => $imgUrl],
                    ['is_primary' => $index === 0, 'sort_order' => $index]
                );
            }

            // Seed Specifications
            foreach ($specifications as $index => $spec) {
                ProductSpecification::updateOrCreate(
                    ['product_id' => $product->id, 'spec_key' => $spec['key']],
                    ['spec_value' => $spec['value'], 'sort_order' => $index]
                );
            }

            // Seed Tags
            foreach ($tags as $tagStr) {
                ProductTag::firstOrCreate(
                    ['product_id' => $product->id, 'tag' => strtolower($tagStr)]
                );
            }
        }
    }
}
