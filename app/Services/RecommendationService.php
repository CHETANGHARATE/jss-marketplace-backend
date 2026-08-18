<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get related product recommendations by category and brand.
     */
    public function getRelatedProducts(Product $product, int $limit = 6)
    {
        return Product::where('status', 'published')
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                $q->where('category_id', $product->category_id)
                    ->orWhere('brand_id', $product->brand_id);
            })
            ->latest()
            ->take($limit)
            ->with(['primaryImage', 'brand', 'sellerStore'])
            ->get();
    }

    /**
     * Get trending products based on rating and reviews count.
     */
    public function getTrendingProducts(int $limit = 6)
    {
        return Product::where('status', 'published')
            ->orderByDesc('reviews_count')
            ->orderByDesc('rating')
            ->take($limit)
            ->with(['primaryImage', 'brand', 'sellerStore'])
            ->get();
    }

    /**
     * Get personalized product recommendations for user based on wishlist & purchase history.
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 6)
    {
        // Fetch categories from user's wishlist
        $wishlistCategoryIds = Wishlist::where('user_id', $user->id)
            ->join('products', 'wishlists.product_id', '=', 'products.id')
            ->pluck('products.category_id')
            ->unique();

        if ($wishlistCategoryIds->isEmpty()) {
            return $this->getTrendingProducts($limit);
        }

        return Product::where('status', 'published')
            ->whereIn('category_id', $wishlistCategoryIds)
            ->orderByDesc('rating')
            ->take($limit)
            ->with(['primaryImage', 'brand', 'sellerStore'])
            ->get();
    }

    /**
     * Get Frequently Bought Together products based on real historical co-purchase correlation (Feature 19).
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 2)
    {
        // 1. Find all order IDs that contain this product
        $orderIds = OrderItem::where('product_id', $product->id)->pluck('order_id');

        $coPurchasedIds = [];
        if ($orderIds->isNotEmpty()) {
            // 2. Aggregate co-purchased product IDs from the same orders
            $coPurchasedIds = OrderItem::whereIn('order_id', $orderIds)
                ->where('product_id', '!=', $product->id)
                ->select('product_id', DB::raw('count(*) as frequency'))
                ->groupBy('product_id')
                ->orderByDesc('frequency')
                ->take($limit)
                ->pluck('product_id')
                ->toArray();
        }

        $results = collect();
        if (!empty($coPurchasedIds)) {
            $results = Product::whereIn('id', $coPurchasedIds)
                ->where('status', 'published')
                ->where('stock_quantity', '>', 0)
                ->with(['primaryImage', 'brand', 'sellerStore'])
                ->get();
        }

        // 3. Fallback to complementary top-rated items in the same category if co-purchase history is sparse
        if ($results->count() < $limit) {
            $needed = $limit - $results->count();
            $excludeIds = $results->pluck('id')->push($product->id)->toArray();

            $complementary = Product::where('status', 'published')
                ->where('stock_quantity', '>', 0)
                ->whereNotIn('id', $excludeIds)
                ->where(function ($q) use ($product) {
                    $q->where('category_id', $product->category_id);
                    if (!empty($product->subcategory_id)) {
                        $q->orWhere('subcategory_id', $product->subcategory_id);
                    }
                })
                ->orderByDesc('rating')
                ->orderByDesc('reviews_count')
                ->take($needed)
                ->with(['primaryImage', 'brand', 'sellerStore'])
                ->get();

            $results = $results->concat($complementary);
        }

        return $results->values();
    }
}
