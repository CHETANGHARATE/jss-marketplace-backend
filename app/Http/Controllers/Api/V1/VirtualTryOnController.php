<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Vision\VirtualTryOnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VirtualTryOnController extends Controller
{
    protected VirtualTryOnService $tryOnService;

    public function __construct(VirtualTryOnService $tryOnService)
    {
        $this->tryOnService = $tryOnService;
    }

    /**
     * Check if product is eligible for Virtual Try-On (Feature 156).
     */
    public function eligibility(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $eligibleCategories = ['apparel', 'clothing', 'eyewear', 'glasses', 'accessories', 'jewelry', 'shoes', 'fashion'];
        $catName = strtolower($product->category?->name ?? '');

        $isEligible = $product->is_try_on_enabled || collect($eligibleCategories)->contains(fn($c) => str_contains($catName, $c));

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'is_eligible' => (bool) $isEligible,
                'try_on_category' => $product->try_on_category ?: ($isEligible ? 'apparel' : null),
            ],
        ], 200);
    }

    /**
     * Generate Virtual Try-On preview.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'photo' => 'required_without:photo_data|nullable|file|image|max:10240',
            'photo_data' => 'required_without:photo|nullable|string',
            'consent_agreed' => 'required|boolean',
        ]);

        if (!$validated['consent_agreed']) {
            return response()->json([
                'success' => false,
                'message' => 'Privacy consent is required for virtual try-on processing.',
            ], 422);
        }

        $product = Product::findOrFail($validated['product_id']);
        $inputPhoto = $request->file('photo') ?: $request->input('photo_data');

        $result = $this->tryOnService->generateTryOn(
            $product,
            $inputPhoto,
            (bool) $validated['consent_agreed']
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
