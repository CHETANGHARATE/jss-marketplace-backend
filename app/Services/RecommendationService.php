<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;

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
            ->get();
    }
}
