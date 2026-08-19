<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\StoreFollower;
use App\Models\UserFavoriteBrand;
use App\Models\UserFavoriteCategory;
use App\Models\VendorStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get user's favorite brands (Feature 66).
     */
    public function getFavoriteBrands(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $favorites = UserFavoriteBrand::where('user_id', $userId)
            ->with(['brand' => function ($q) {
                $q->withCount('products');
            }])
            ->latest()
            ->get();

        $data = $favorites->map(function ($fav) {
            if (!$fav->brand) return null;
            return [
                'id' => $fav->brand->id,
                'favorite_id' => $fav->id,
                'name' => $fav->brand->name,
                'slug' => $fav->brand->slug,
                'logo' => $fav->brand->logo,
                'description' => $fav->brand->description,
                'products_count' => $fav->brand->products_count ?? 0,
                'favorited_at' => $fav->created_at,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Add brand to favorites (Feature 66).
     */
    public function addFavoriteBrand(Request $request, int $brandId): JsonResponse
    {
        $userId = $request->user()->id;

        $brand = Brand::where('id', $brandId)->where('is_active', true)->first();
        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found or inactive.',
            ], 404);
        }

        $fav = UserFavoriteBrand::firstOrCreate([
            'user_id' => $userId,
            'brand_id' => $brandId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Added {$brand->name} to your favorite brands.",
            'data' => [
                'id' => $brand->id,
                'is_favorite' => true,
            ],
        ], 200);
    }

    /**
     * Remove brand from favorites (Feature 66).
     */
    public function removeFavoriteBrand(Request $request, int $brandId): JsonResponse
    {
        $userId = $request->user()->id;

        UserFavoriteBrand::where('user_id', $userId)
            ->where('brand_id', $brandId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand removed from favorites.',
            'data' => [
                'id' => $brandId,
                'is_favorite' => false,
            ],
        ], 200);
    }

    /**
     * Get user's favorite categories (Feature 67).
     */
    public function getFavoriteCategories(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $favorites = UserFavoriteCategory::where('user_id', $userId)
            ->with(['category' => function ($q) {
                $q->withCount('products');
            }])
            ->latest()
            ->get();

        $data = $favorites->map(function ($fav) {
            if (!$fav->category) return null;
            return [
                'id' => $fav->category->id,
                'favorite_id' => $fav->id,
                'name' => $fav->category->name,
                'slug' => $fav->category->slug,
                'image' => $fav->category->image,
                'icon' => $fav->category->icon,
                'description' => $fav->category->description,
                'products_count' => $fav->category->products_count ?? 0,
                'favorited_at' => $fav->created_at,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Add category to favorites (Feature 67).
     */
    public function addFavoriteCategory(Request $request, int $categoryId): JsonResponse
    {
        $userId = $request->user()->id;

        $category = Category::where('id', $categoryId)->where('status', 'active')->first();
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found or inactive.',
            ], 404);
        }

        $fav = UserFavoriteCategory::firstOrCreate([
            'user_id' => $userId,
            'category_id' => $categoryId,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Added {$category->name} to your favorite categories.",
            'data' => [
                'id' => $category->id,
                'is_favorite' => true,
            ],
        ], 200);
    }

    /**
     * Remove category from favorites (Feature 67).
     */
    public function removeFavoriteCategory(Request $request, int $categoryId): JsonResponse
    {
        $userId = $request->user()->id;

        UserFavoriteCategory::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category removed from favorites.',
            'data' => [
                'id' => $categoryId,
                'is_favorite' => false,
            ],
        ], 200);
    }

    /**
     * Follow a vendor store (Feature 65).
     */
    public function followStore(Request $request, int $storeId): JsonResponse
    {
        $userId = $request->user()->id;

        $store = VendorStore::where('id', $storeId)->where('status', 'active')->first();
        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor store not found.',
            ], 404);
        }

        StoreFollower::firstOrCreate([
            'user_id' => $userId,
            'vendor_store_id' => $storeId,
        ]);

        $followersCount = StoreFollower::where('vendor_store_id', $storeId)->count();

        return response()->json([
            'success' => true,
            'message' => "You are now following {$store->store_name}.",
            'data' => [
                'store_id' => $storeId,
                'is_following' => true,
                'followers_count' => $followersCount,
            ],
        ], 200);
    }

    /**
     * Unfollow a vendor store (Feature 65).
     */
    public function unfollowStore(Request $request, int $storeId): JsonResponse
    {
        $userId = $request->user()->id;

        StoreFollower::where('user_id', $userId)
            ->where('vendor_store_id', $storeId)
            ->delete();

        $followersCount = StoreFollower::where('vendor_store_id', $storeId)->count();

        return response()->json([
            'success' => true,
            'message' => 'You have unfollowed this store.',
            'data' => [
                'store_id' => $storeId,
                'is_following' => false,
                'followers_count' => $followersCount,
            ],
        ], 200);
    }

    /**
     * Get followed stores for user (Feature 65).
     */
    public function getFollowedStores(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $followed = StoreFollower::where('user_id', $userId)
            ->with(['store'])
            ->latest()
            ->get();

        $data = $followed->map(function ($item) {
            $store = $item->store;
            if (!$store) return null;

            return [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'slug' => $store->slug,
                'logo' => $store->logo,
                'banner' => $store->banner,
                'rating' => (float) ($store->rating ?? 4.8),
                'followers_count' => StoreFollower::where('vendor_store_id', $store->id)->count(),
                'followed_at' => $item->created_at,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }
}
