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

            return $query->get()->map(function ($cat) {
                if ($cat->relationLoaded('children')) {
                    $cat->setRelation('children', $cat->children->unique('id')->values());
                }
                return $cat;
            });
        });

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ], 200);
    }

    /**
     * Display details of a specific category by ID or slug.
     */
    public function show(string $slugOrId): JsonResponse
    {
        $query = Category::where('is_active', true)
            ->with([
                'children' => fn($q) => $q->where('is_active', true),
                'brands' => fn($q) => $q->where('is_active', true),
                'attributes.values',
                'media',
            ]);

        if (is_numeric($slugOrId)) {
            $category = (clone $query)->where('id', (int) $slugOrId)->first()
                ?? $query->where('slug', $slugOrId)->first();
        } else {
            $category = $query->where('slug', $slugOrId)->first();
        }

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

    /**
     * Get dynamic attribute templates and attributes configured for a specific category.
     */
    public function getCategoryAttributes(int $id): JsonResponse
    {
        $category = Category::with([
            'attributes.values',
            'attributeTemplates.attributes.values',
            'parent.attributes.values',
            'parent.attributeTemplates.attributes.values',
        ])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        // Aggregate attributes from category, category templates, and parent category
        $directAttributes = $category->attributes;
        $templateAttributes = $category->attributeTemplates->flatMap->attributes;
        $parentAttributes = $category->parent ? $category->parent->attributes : collect();
        $parentTemplateAttributes = $category->parent ? $category->parent->attributeTemplates->flatMap->attributes : collect();

        $allAttributes = $directAttributes
            ->concat($templateAttributes)
            ->concat($parentAttributes)
            ->concat($parentTemplateAttributes)
            ->unique('id')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'attributes' => $allAttributes,
                'templates' => $category->attributeTemplates,
            ],
        ], 200);
    }
}
