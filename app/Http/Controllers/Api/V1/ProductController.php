<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductStatusRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\ProductTag;
use App\Services\ProductFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a filtered & paginated listing of approved products.
     */
    public function index(Request $request, ProductFilterService $filterService): JsonResponse
    {
        $query = $filterService->apply($request)
            ->with(['category', 'subcategory', 'brand', 'seller', 'primaryImage']);

        $perPage = min((int) $request->input('per_page', 15), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ], 200);
    }

    /**
     * Display a listing of featured products.
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 8), 20);

        $products = Product::approved()
            ->where('is_featured', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ], 200);
    }

    /**
     * Display a listing of trending products.
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 8), 20);

        $products = Product::approved()
            ->where('is_trending', true)
            ->with(['category', 'brand', 'primaryImage'])
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ], 200);
    }

    /**
     * Display single product details by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with([
                'category',
                'subcategory',
                'brand',
                'seller',
                'images',
                'specifications',
                'tags',
                'attributeValues.attribute',
            ])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductDetailResource($product),
        ], 200);
    }

    /**
     * Store a newly created product (Admin / Seller).
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated, $request) {
            $sellerId = $request->user()->isSeller() ? $request->user()->id : ($validated['seller_id'] ?? null);

            $images = $validated['images'] ?? [];
            $specifications = $validated['specifications'] ?? [];
            $attributeValueIds = $validated['attribute_value_ids'] ?? [];
            $tags = $validated['tags'] ?? [];

            unset($validated['images'], $validated['specifications'], $validated['attribute_value_ids'], $validated['tags']);

            $validated['seller_id'] = $sellerId;

            // Sellers default to pending_approval unless Admin creates
            if ($request->user()->isSeller()) {
                $validated['status'] = 'pending_approval';
            }

            $product = Product::create($validated);

            // 1. Gallery Images
            foreach ($images as $index => $imgUrl) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $imgUrl,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }

            // 2. Specifications
            foreach ($specifications as $index => $spec) {
                ProductSpecification::create([
                    'product_id' => $product->id,
                    'spec_key' => $spec['key'],
                    'spec_value' => $spec['value'],
                    'sort_order' => $index,
                ]);
            }

            // 3. Attribute Values Mapping
            if (!empty($attributeValueIds)) {
                $attrValues = AttributeValue::whereIn('id', $attributeValueIds)->get();
                $pivotData = [];
                foreach ($attrValues as $av) {
                    $pivotData[] = [
                        'product_id' => $product->id,
                        'attribute_id' => $av->attribute_id,
                        'attribute_value_id' => $av->id,
                    ];
                }
                DB::table('product_attribute_values')->insert($pivotData);
            }

            // 4. Tags
            foreach ($tags as $tagStr) {
                ProductTag::create([
                    'product_id' => $product->id,
                    'tag' => strtolower(trim($tagStr)),
                ]);
            }

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => new ProductDetailResource($product->fresh(['category', 'subcategory', 'brand', 'images', 'specifications', 'tags', 'attributeValues'])),
        ], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Authorization check: Seller can only edit own products
        if ($request->user()->isSeller() && $product->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized operation.',
            ], 403);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($product, $validated) {
            $images = $validated['images'] ?? null;
            $specifications = $validated['specifications'] ?? null;
            $attributeValueIds = $validated['attribute_value_ids'] ?? null;
            $tags = $validated['tags'] ?? null;

            unset($validated['images'], $validated['specifications'], $validated['attribute_value_ids'], $validated['tags']);

            $product->update($validated);

            if ($images !== null) {
                $product->images()->delete();
                foreach ($images as $index => $imgUrl) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imgUrl,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            if ($specifications !== null) {
                $product->specifications()->delete();
                foreach ($specifications as $index => $spec) {
                    ProductSpecification::create([
                        'product_id' => $product->id,
                        'spec_key' => $spec['key'],
                        'spec_value' => $spec['value'],
                        'sort_order' => $index,
                    ]);
                }
            }

            if ($attributeValueIds !== null) {
                DB::table('product_attribute_values')->where('product_id', $product->id)->delete();
                if (!empty($attributeValueIds)) {
                    $attrValues = AttributeValue::whereIn('id', $attributeValueIds)->get();
                    $pivotData = [];
                    foreach ($attrValues as $av) {
                        $pivotData[] = [
                            'product_id' => $product->id,
                            'attribute_id' => $av->attribute_id,
                            'attribute_value_id' => $av->id,
                        ];
                    }
                    DB::table('product_attribute_values')->insert($pivotData);
                }
            }

            if ($tags !== null) {
                $product->tags()->delete();
                foreach ($tags as $tagStr) {
                    ProductTag::create([
                        'product_id' => $product->id,
                        'tag' => strtolower(trim($tagStr)),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => new ProductDetailResource($product->fresh(['category', 'subcategory', 'brand', 'images', 'specifications', 'tags', 'attributeValues'])),
        ], 200);
    }

    /**
     * Update product approval status (Admin only).
     */
    public function updateStatus(UpdateProductStatusRequest $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $validated = $request->validated();
        $product->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Product status updated to {$validated['status']}.",
            'data' => new ProductDetailResource($product->fresh()),
        ], 200);
    }

    /**
     * Delete a product (SoftDelete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        if ($request->user()->isSeller() && $product->seller_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized operation.',
            ], 403);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ], 200);
    }
}
