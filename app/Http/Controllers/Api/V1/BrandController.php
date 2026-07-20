<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BrandController extends Controller
{
    /**
     * Display a listing of active brands.
     */
    public function index(Request $request): JsonResponse
    {
        $featuredOnly = $request->boolean('featured');
        $cacheKey = "brands_list_" . ($featuredOnly ? 'featured' : 'all');

        $brands = Cache::remember($cacheKey, 3600, function () use ($featuredOnly) {
            $query = Brand::where('is_active', true)->orderBy('sort_order', 'asc');
            if ($featuredOnly) {
                $query->where('is_featured', true);
            }
            return $query->get();
        });

        return response()->json([
            'success' => true,
            'data' => BrandResource::collection($brands),
        ], 200);
    }

    /**
     * Display details of a specific brand by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $brand = Brand::where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories', 'media'])
            ->first();

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BrandResource($brand),
        ], 200);
    }

    /**
     * Store a newly created brand (Admin only).
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $brand = Brand::create($validated);

        Cache::forget('brands_list_featured');
        Cache::forget('brands_list_all');

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully.',
            'data' => new BrandResource($brand),
        ], 201);
    }

    /**
     * Update an existing brand (Admin only).
     */
    public function update(UpdateBrandRequest $request, int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
            ], 404);
        }

        $brand->update($request->validated());

        Cache::forget('brands_list_featured');
        Cache::forget('brands_list_all');

        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully.',
            'data' => new BrandResource($brand->fresh()),
        ], 200);
    }

    /**
     * Delete a brand (Admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found.',
            ], 404);
        }

        $brand->delete();
        Cache::forget('brands_list_featured');
        Cache::forget('brands_list_all');

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully.',
        ], 200);
    }
}
