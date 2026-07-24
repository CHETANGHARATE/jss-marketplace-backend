<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Display a listing of top-level categories with nested subcategories.
     */
    public function index(Request $request): JsonResponse
    {
        $featuredOnly = $request->boolean('featured');
        $locale = $request->header('Accept-Language', 'en');
        $cacheKey = "categories_tree_{$locale}_" . ($featuredOnly ? 'featured' : 'all');

        $categories = Cache::remember($cacheKey, 3600, function () use ($featuredOnly) {
            $query = Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order', 'asc');
                }])
                ->orderBy('sort_order', 'asc');

            if ($featuredOnly) {
                $query->where('is_featured', true);
            }

            return $query->get();
        });

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ], 200);
    }

    /**
     * Display details of a specific category by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'children' => fn($q) => $q->where('is_active', true),
                'brands' => fn($q) => $q->where('is_active', true),
                'attributes.values',
                'media',
            ])
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ], 200);
    }

    /**
     * Store a newly created category (Admin only).
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $brandIds = $validated['brand_ids'] ?? [];
        $attributeIds = $validated['attribute_ids'] ?? [];

        unset($validated['brand_ids'], $validated['attribute_ids']);

        $category = Category::create($validated);

        if (!empty($brandIds)) {
            $category->brands()->sync($brandIds);
        }

        if (!empty($attributeIds)) {
            $category->attributes()->sync($attributeIds);
        }

        Cache::flush(); // Flush category caches

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => new CategoryResource($category->fresh(['children', 'brands', 'attributes'])),
        ], 201);
    }

    /**
     * Update an existing category (Admin only).
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $validated = $request->validated();
        $brandIds = $validated['brand_ids'] ?? null;
        $attributeIds = $validated['attribute_ids'] ?? null;

        unset($validated['brand_ids'], $validated['attribute_ids']);

        $category->update($validated);

        if ($brandIds !== null) {
            $category->brands()->sync($brandIds);
        }

        if ($attributeIds !== null) {
            $category->attributes()->sync($attributeIds);
        }

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => new CategoryResource($category->fresh(['children', 'brands', 'attributes'])),
        ], 200);
    }

    /**
     * Delete a category (Admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $category->delete();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ], 200);
    }
}
