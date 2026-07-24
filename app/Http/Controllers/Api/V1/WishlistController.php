<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleWishlistRequest;
use App\Http\Resources\WishlistResource;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Display customer's wishlist items.
     */
    public function index(Request $request): JsonResponse
    {
        $wishlist = Wishlist::where('user_id', $request->user()->id)
            ->with(['product.primaryImage', 'product.category', 'product.brand'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => WishlistResource::collection($wishlist),
        ], 200);
    }

    /**
     * Toggle product in user's wishlist.
     */
    public function toggle(ToggleWishlistRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;
        $productId = $validated['product_id'];

        $existing = Wishlist::where('user_id', $userId)->where('product_id', $productId)->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist.',
                'in_wishlist' => false,
            ], 200);
        }

        $wishlist = Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
        ])->load(['product.primaryImage', 'product.category', 'product.brand']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist.',
            'in_wishlist' => true,
            'data' => new WishlistResource($wishlist),
        ], 201);
    }

    /**
     * Remove product from wishlist.
     */
    public function destroy(Request $request, int $productId): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist.',
        ], 200);
    }
}
