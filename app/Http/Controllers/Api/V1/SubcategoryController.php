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

class SubcategoryController extends Controller
{
    /**
     * Display a listing of subcategories.
     * Optionally filter by parent_id or search query.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::whereNotNull('parent_id')
            ->with(['parent']);

        if ($request->filled('category_id')) {
            $query->where('parent_id', $request->input('category_id'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name->en', 'like', "%{$search}%")
                  ->orWhere('name->hi', 'like', "%{$search}%")
                  ->orWhere('name->mr', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->has('active_only')) {
            $query->where('is_active', $request->boolean('active_only'));
        }

        $subcategories = $query->orderBy('sort_order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($subcategories),
        ], 200);
    }

    /**
     * Display details of a specific subcategory by slug or ID.
     */
    public function show(string $slugOrId): JsonResponse
    {
        $subcategory = Category::whereNotNull('parent_id')
            ->where(function ($q) use ($slugOrId) {
                if (is_numeric($slugOrId)) {
                    $q->where('id', (int) $slugOrId);
                } else {
                    $q->where('slug', $slugOrId);
                }
            })
            ->with(['parent', 'brands', 'attributes'])
            ->first();

        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategory not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($subcategory),
        ], 200);
    }

    /**
     * Store a newly created subcategory (Admin only).
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (empty($validated['parent_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'parent_id is required for creating a subcategory.',
            ], 422);
        }

        $brandIds = $validated['brand_ids'] ?? [];
        $attributeIds = $validated['attribute_ids'] ?? [];

        unset($validated['brand_ids'], $validated['attribute_ids']);

        $subcategory = Category::create($validated);

        if (!empty($brandIds)) {
            $subcategory->brands()->sync($brandIds);
        }

        if (!empty($attributeIds)) {
            $subcategory->attributes()->sync($attributeIds);
        }

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Subcategory created successfully.',
            'data' => new CategoryResource($subcategory->fresh(['parent', 'brands', 'attributes'])),
        ], 201);
    }

    /**
     * Update an existing subcategory (Admin only).
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $subcategory = Category::whereNotNull('parent_id')->find($id);

        if (!$subcategory) {
            // Also allow updating if parent_id is being assigned
            $subcategory = Category::find($id);
        }

        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategory not found.',
            ], 404);
        }

        $validated = $request->validated();
        $brandIds = $validated['brand_ids'] ?? null;
        $attributeIds = $validated['attribute_ids'] ?? null;

        unset($validated['brand_ids'], $validated['attribute_ids']);

        $subcategory->update($validated);

        if ($brandIds !== null) {
            $subcategory->brands()->sync($brandIds);
        }

        if ($attributeIds !== null) {
            $subcategory->attributes()->sync($attributeIds);
        }

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Subcategory updated successfully.',
            'data' => new CategoryResource($subcategory->fresh(['parent', 'brands', 'attributes'])),
        ], 200);
    }

    /**
     * Delete a subcategory (Admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $subcategory = Category::find($id);

        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategory not found.',
            ], 404);
        }

        $subcategory->delete();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Subcategory deleted successfully.',
        ], 200);
    }

    /**
     * Toggle status or update sort order for subcategory.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $subcategory = Category::find($id);

        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategory not found.',
            ], 404);
        }

        if ($request->has('is_active')) {
            $subcategory->is_active = $request->boolean('is_active');
        }

        if ($request->has('sort_order')) {
            $subcategory->sort_order = $request->integer('sort_order');
        }

        $subcategory->save();
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Subcategory status updated.',
            'data' => new CategoryResource($subcategory),
        ], 200);
    }
}
