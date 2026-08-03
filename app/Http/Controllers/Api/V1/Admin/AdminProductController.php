<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    /**
     * Display pending products for moderation review.
     */
    public function pending(Request $request): JsonResponse
    {
        $products = Product::whereIn('status', ['pending_approval', 'pending_review'])
            ->with(['category', 'brand', 'seller', 'primaryImage', 'images', 'specifications', 'variants'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    /**
     * Approve a vendor product to make it live on the marketplace.
     */
    public function approve(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->update([
            'status' => 'approved',
            'is_active' => true,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product approved and published to marketplace.',
            'data' => new ProductResource($product->fresh(['category', 'brand', 'seller', 'primaryImage'])),
        ], 200);
    }

    /**
     * Reject a vendor product with reason.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $product = Product::findOrFail($id);

        $product->update([
            'status' => 'rejected',
            'is_active' => false,
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product rejected.',
            'data' => new ProductResource($product->fresh(['category', 'brand', 'seller'])),
        ], 200);
    }

    /**
     * Request changes for a vendor product (reverts to draft).
     */
    public function requestChanges(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'instructions' => 'required|string|max:1000',
        ]);

        $product = Product::findOrFail($id);

        $product->update([
            'status' => 'draft',
            'is_active' => false,
            'rejection_reason' => 'Changes Requested: ' . $validated['instructions'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product sent back to vendor for changes.',
            'data' => new ProductResource($product->fresh()),
        ], 200);
    }
}
