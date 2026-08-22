<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Product360Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductMedia360Controller extends Controller
{
    /**
     * Get 360° frames & AR asset configuration for a product (Features 157, 158, 159).
     */
    public function get360AndAr(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $frames = $product->threeSixtyMedia()->where('is_active', true)->orderBy('sort_order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'has_360' => $frames->isNotEmpty(),
                'frames_count' => $frames->count(),
                'frames' => $frames->pluck('frame_url'),
                'is_ar_enabled' => (bool) $product->is_ar_enabled,
                'ar_model_glb' => $product->ar_model_glb,
                'ar_model_usdz' => $product->ar_model_usdz,
                'is_try_on_enabled' => (bool) $product->is_try_on_enabled,
                'try_on_category' => $product->try_on_category,
            ],
        ], 200);
    }

    /**
     * Admin / Seller upload frames for 360 Degree View.
     */
    public function uploadFrames(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'frame_urls' => 'required|array|min:8|max:72',
            'frame_urls.*' => 'required|string|url',
        ]);

        // Clear existing frames and re-insert in order
        $product->threeSixtyMedia()->delete();

        foreach ($validated['frame_urls'] as $idx => $url) {
            Product360Media::create([
                'product_id' => $product->id,
                'frame_url' => $url,
                'sort_order' => $idx,
                'is_active' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '360° product view frames uploaded successfully.',
            'frames_count' => count($validated['frame_urls']),
        ], 200);
    }

    /**
     * Admin / Seller configure AR 3D assets.
     */
    public function updateAr(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'is_ar_enabled' => 'required|boolean',
            'ar_model_glb' => 'nullable|string|url',
            'ar_model_usdz' => 'nullable|string|url',
            'is_try_on_enabled' => 'sometimes|boolean',
            'try_on_category' => 'nullable|string|in:apparel,eyewear,accessories,jewelry,shoes',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'AR and 3D product attributes updated successfully.',
            'data' => [
                'is_ar_enabled' => (bool) $product->is_ar_enabled,
                'ar_model_glb' => $product->ar_model_glb,
                'ar_model_usdz' => $product->ar_model_usdz,
                'is_try_on_enabled' => (bool) $product->is_try_on_enabled,
                'try_on_category' => $product->try_on_category,
            ],
        ], 200);
    }
}
