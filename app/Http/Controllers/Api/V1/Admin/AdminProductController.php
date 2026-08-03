<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * Display a listing of all products for admin management.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'brand', 'seller', 'primaryImage', 'images', 'variants']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->latest()->paginate($perPage);

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
     * Store a newly created admin product (auto-approved).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'child_category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sku' => 'nullable|string|unique:products,sku',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'original_price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'gst_percent' => 'nullable|numeric|min:0',
            'tax_inclusive' => 'nullable|boolean',
            'stock_quantity' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'attribute_values' => 'nullable|array',
            'variants' => 'nullable|array',
            'weight' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'dispatch_days' => 'nullable|integer',
            'shipping_charge' => 'nullable|numeric',
            'is_free_shipping' => 'nullable|boolean',
            'is_cod_available' => 'nullable|boolean',
            'return_policy' => 'nullable|string',
            'replacement_policy' => 'nullable|string',
            'warranty_summary' => 'nullable|string',
            'guarantee_summary' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'canonical_url' => 'nullable|string',
            'og_image' => 'nullable|string',
            'highlights' => 'nullable|array',
            'search_keywords' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $status = $request->get('status', 'approved');
        $isActive = ($status === 'approved');

        $sku = $validated['sku'] ?? 'SKU-ADM-' . strtoupper(Str::random(8));

        $productData = array_merge($validated, [
            'seller_id' => $request->user()->id,
            'sku' => $sku,
            'slug' => Str::slug($validated['name']) . '-' . strtolower(Str::random(5)),
            'status' => $status,
            'is_active' => $isActive,
        ]);

        unset($productData['images'], $productData['attribute_values'], $productData['variants']);

        $product = Product::create($productData);

        // Attach images
        if (!empty($validated['images'])) {
            foreach ($validated['images'] as $index => $url) {
                $product->images()->create([
                    'image_path' => $url,
                    'is_primary' => ($index === 0),
                    'sort_order' => $index,
                ]);
            }
        }

        // Attach attribute values
        if (!empty($validated['attribute_values'])) {
            $product->attributeValues()->sync($validated['attribute_values']);
        }

        // Attach variants
        if (!empty($validated['variants'])) {
            foreach ($validated['variants'] as $v) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $v['sku'] ?? ($sku . '-' . strtoupper(Str::random(4))),
                    'barcode' => $v['barcode'] ?? null,
                    'title' => $v['title'] ?? 'Default Variant',
                    'price' => $v['price'] ?? $product->original_price,
                    'offer_price' => $v['offer_price'] ?? $product->offer_price,
                    'stock_quantity' => $v['stock_quantity'] ?? 0,
                    'image' => $v['image'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully by Admin.',
            'data' => new ProductResource($product->load(['category', 'brand', 'seller', 'images', 'variants', 'attributeValues'])),
        ], 201);
    }

    /**
     * Display detailed information for a single product.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with([
            'category',
            'subcategory',
            'childCategory',
            'brand',
            'seller',
            'primaryImage',
            'images',
            'variants',
            'attributeValues.attribute',
            'reviews'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ], 200);
    }

    /**
     * Update an existing product (Admin full control).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'child_category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sku' => 'nullable|string|unique:products,sku,' . $id,
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'original_price' => 'sometimes|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'gst_percent' => 'nullable|numeric|min:0',
            'tax_inclusive' => 'nullable|boolean',
            'stock_quantity' => 'sometimes|integer|min:0',
            'images' => 'nullable|array',
            'attribute_values' => 'nullable|array',
            'variants' => 'nullable|array',
            'weight' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'dispatch_days' => 'nullable|integer',
            'shipping_charge' => 'nullable|numeric',
            'is_free_shipping' => 'nullable|boolean',
            'is_cod_available' => 'nullable|boolean',
            'return_policy' => 'nullable|string',
            'replacement_policy' => 'nullable|string',
            'warranty_summary' => 'nullable|string',
            'guarantee_summary' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'canonical_url' => 'nullable|string',
            'og_image' => 'nullable|string',
            'highlights' => 'nullable|array',
            'search_keywords' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $product->name) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . strtolower(Str::random(5));
        }

        if (isset($validated['status'])) {
            $validated['is_active'] = ($validated['status'] === 'approved');
        }

        $images = $validated['images'] ?? null;
        $attrValues = $validated['attribute_values'] ?? null;
        $variants = $validated['variants'] ?? null;

        unset($validated['images'], $validated['attribute_values'], $validated['variants']);

        $product->update($validated);

        // Update images if provided
        if (is_array($images)) {
            $product->images()->delete();
            foreach ($images as $index => $url) {
                $product->images()->create([
                    'image_path' => $url,
                    'is_primary' => ($index === 0),
                    'sort_order' => $index,
                ]);
            }
        }

        // Update attribute values if provided
        if (is_array($attrValues)) {
            $product->attributeValues()->sync($attrValues);
        }

        // Update variants if provided
        if (is_array($variants)) {
            $product->variants()->delete();
            foreach ($variants as $v) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $v['sku'] ?? ($product->sku . '-' . strtoupper(Str::random(4))),
                    'barcode' => $v['barcode'] ?? null,
                    'title' => $v['title'] ?? 'Variant',
                    'price' => $v['price'] ?? $product->original_price,
                    'offer_price' => $v['offer_price'] ?? $product->offer_price,
                    'stock_quantity' => $v['stock_quantity'] ?? 0,
                    'image' => $v['image'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->fresh(['category', 'brand', 'seller', 'images', 'variants', 'attributeValues'])),
        ], 200);
    }

    /**
     * Delete a product permanently.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->images()->delete();
        $product->variants()->delete();
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted permanently.',
        ], 200);
    }

    /**
     * Clone/Duplicate a product.
     */
    public function duplicate(int $id): JsonResponse
    {
        $original = Product::with(['images', 'variants', 'attributeValues'])->findOrFail($id);

        $newProduct = $original->replicate(['id', 'created_at', 'updated_at']);
        $newProduct->name = 'Copy of ' . $original->name;
        $newProduct->slug = Str::slug($newProduct->name) . '-' . strtolower(Str::random(5));
        $newProduct->sku = 'SKU-CLONE-' . strtoupper(Str::random(8));
        $newProduct->status = 'draft';
        $newProduct->is_active = false;
        $newProduct->rejection_reason = null;
        $newProduct->save();

        foreach ($original->images as $img) {
            $newProduct->images()->create([
                'image_path' => $img->image_path,
                'is_primary' => $img->is_primary,
                'sort_order' => $img->sort_order,
            ]);
        }

        foreach ($original->variants as $variant) {
            $newVariant = $variant->replicate(['id', 'created_at', 'updated_at']);
            $newVariant->product_id = $newProduct->id;
            $newVariant->sku = 'SKU-VAR-' . strtoupper(Str::random(8));
            $newVariant->save();
        }

        if ($original->attributeValues->count()) {
            $newProduct->attributeValues()->sync($original->attributeValues->pluck('id'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Product duplicated successfully as Draft.',
            'data' => new ProductResource($newProduct->fresh(['category', 'brand', 'seller', 'images', 'variants'])),
        ], 201);
    }

    /**
     * Approve a vendor product to make it live on the marketplace.
     */
    public function approve(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        if ($product->status === 'approved' && $product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product is already approved and live.',
            ], 400);
        }

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
     * Reject a vendor product submission with remarks.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $product = Product::findOrFail($id);

        if ($product->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Product is already rejected.',
            ], 400);
        }

        $product->update([
            'status' => 'rejected',
            'is_active' => false,
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product submission rejected.',
            'data' => new ProductResource($product->fresh(['category', 'brand', 'seller'])),
        ], 200);
    }

    /**
     * Request changes for a vendor product (reverts to draft with remarks).
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
            'message' => 'Product sent back to vendor with change requests.',
            'data' => new ProductResource($product->fresh()),
        ], 200);
    }

    /**
     * Unpublish an approved product (sets status = hidden).
     */
    public function unpublish(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->update([
            'status' => 'hidden',
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product unpublished and hidden from marketplace.',
            'data' => new ProductResource($product->fresh()),
        ], 200);
    }

    /**
     * Re-publish a hidden or rejected product.
     */
    public function publish(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->update([
            'status' => 'approved',
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product published and live on marketplace.',
            'data' => new ProductResource($product->fresh()),
        ], 200);
    }
}
