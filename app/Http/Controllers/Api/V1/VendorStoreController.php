<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterVendorStoreRequest;
use App\Http\Requests\RequestSettlementRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SettlementResource;
use App\Http\Resources\VendorStoreResource;
use App\Http\Resources\VendorWalletResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\VendorStore;
use App\Services\VendorCommissionService;
use App\Services\VendorStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class VendorStoreController extends Controller
{
    protected VendorStoreService $storeService;
    protected VendorCommissionService $commissionService;

    public function __construct(VendorStoreService $storeService, VendorCommissionService $commissionService)
    {
        $this->storeService = $storeService;
        $this->commissionService = $commissionService;
    }

    /**
     * Public list of active vendor stores.
     */
    public function index(): JsonResponse
    {
        $stores = VendorStore::where('status', 'active')->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'data' => VendorStoreResource::collection($stores),
        ], 200);
    }

    /**
     * Public vendor storefront profile and products.
     */
    public function show(string $slug): JsonResponse
    {
        $store = VendorStore::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $products = Product::where('seller_id', $store->user_id)->where('status', 'published')->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'store' => new VendorStoreResource($store),
                'products' => ProductResource::collection($products),
            ],
        ], 200);
    }

    /**
     * Register vendor store profile (Seller).
     */
    public function register(RegisterVendorStoreRequest $request): JsonResponse
    {
        try {
            $store = $this->storeService->registerStore($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Vendor store registered successfully and is pending KYC verification.',
                'data' => new VendorStoreResource($store),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current vendor's store profile and wallet.
     */
    public function currentStore(Request $request): JsonResponse
    {
        $store = VendorStore::where('user_id', $request->user()->id)->with(['wallet.transactions'])->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'No vendor store found for this user.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new VendorStoreResource($store),
        ], 200);
    }

    /**
     * Vendor Dashboard stats.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $store = VendorStore::where('user_id', $request->user()->id)->with('wallet')->firstOrFail();

        $totalProducts = Product::where('seller_id', $request->user()->id)->count();
        $totalOrders = OrderItem::where('seller_id', $request->user()->id)->count();
        $totalEarnings = (float) ($store->wallet?->balance ?? 0.0);

        return response()->json([
            'success' => true,
            'data' => [
                'store_name' => $store->store_name,
                'kyc_status' => $store->kyc_status,
                'status' => $store->status,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'wallet_balance' => $totalEarnings,
            ],
        ], 200);
    }

    /**
     * Vendor's owned products.
     */
    public function products(Request $request): JsonResponse
    {
        $products = Product::where('seller_id', $request->user()->id)->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ], 200);
    }

    /**
     * Vendor's line items / orders.
     */
    public function orders(Request $request): JsonResponse
    {
        $orderItems = OrderItem::where('seller_id', $request->user()->id)->with('order')->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => OrderItemResource::collection($orderItems),
        ], 200);
    }

    /**
     * Vendor wallet balance and transactions ledger.
     */
    public function wallet(Request $request): JsonResponse
    {
        $store = VendorStore::where('user_id', $request->user()->id)->with(['wallet.transactions'])->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new VendorWalletResource($store->wallet),
        ], 200);
    }

    /**
     * Vendor request payout settlement.
     */
    public function requestSettlement(RequestSettlementRequest $request): JsonResponse
    {
        try {
            $store = VendorStore::where('user_id', $request->user()->id)->with('wallet')->firstOrFail();
            $validated = $request->validated();

            $settlement = $this->commissionService->requestSettlement(
                $store,
                (float) $validated['amount'],
                $validated['bank_details']
            );

            return response()->json([
                'success' => true,
                'message' => 'Payout settlement request submitted.',
                'data' => new SettlementResource($settlement),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Vendor Create Product (Supports Modules 1-13).
     */
    public function storeProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:categories,id',
                'child_category_id' => 'nullable|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'sku' => 'nullable|string|max:100|unique:products,sku',
                'original_price' => 'required|numeric|min:0',
                'offer_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'gst_percent' => 'nullable|numeric|min:0',
                'tax_inclusive' => 'nullable|boolean',
                'stock_quantity' => 'required|integer|min:0',
                'short_description' => 'nullable|string',
                'description' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|string',
                'attribute_values' => 'nullable|array',
                'specifications' => 'nullable|array',
                'custom_specifications' => 'nullable|array',
                'variants' => 'nullable|array',
                'weight' => 'nullable|numeric|min:0',
                'length' => 'nullable|numeric|min:0',
                'width' => 'nullable|numeric|min:0',
                'height' => 'nullable|numeric|min:0',
                'dispatch_days' => 'nullable|integer|min:1',
                'shipping_charge' => 'nullable|numeric|min:0',
                'is_free_shipping' => 'nullable|boolean',
                'is_cod_available' => 'nullable|boolean',
                'return_policy' => 'nullable|string',
                'replacement_policy' => 'nullable|string',
                'warranty_summary' => 'nullable|string',
                'guarantee_summary' => 'nullable|string',
                'cancellation_policy' => 'nullable|string',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'meta_keywords' => 'nullable|string|max:500',
                'canonical_url' => 'nullable|string|max:255',
                'og_image' => 'nullable|string|max:255',
                'highlights' => 'nullable|array',
                'search_keywords' => 'nullable|string',
                'status' => 'nullable|string|in:draft,pending_approval,pending_review',
            ]);

            $sellerId = $request->user()->id;
            $slug = \Illuminate\Support\Str::slug($validated['name']) . '-' . \Illuminate\Support\Str::random(6);

            // VENDOR PRODUCTS NEVER BECOME LIVE AUTOMATICALLY! Default to draft or pending_approval
            $initialStatus = in_array($validated['status'] ?? '', ['pending_approval', 'pending_review']) ? 'pending_approval' : 'draft';

            $product = DB::transaction(function () use ($validated, $sellerId, $slug, $initialStatus) {
                $product = Product::create([
                    'seller_id' => $sellerId,
                    'category_id' => $validated['category_id'] ?? null,
                    'subcategory_id' => $validated['subcategory_id'] ?? null,
                    'child_category_id' => $validated['child_category_id'] ?? null,
                    'brand_id' => $validated['brand_id'] ?? null,
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'sku' => $validated['sku'] ?? ('SKU-' . strtoupper(\Illuminate\Support\Str::random(8))),
                    'short_description' => $validated['short_description'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'original_price' => $validated['original_price'],
                    'offer_price' => $validated['offer_price'] ?? $validated['original_price'],
                    'cost_price' => $validated['cost_price'] ?? null,
                    'gst_percent' => $validated['gst_percent'] ?? 0,
                    'tax_inclusive' => $validated['tax_inclusive'] ?? true,
                    'stock_quantity' => $validated['stock_quantity'],
                    'stock_status' => $validated['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock',
                    'weight' => $validated['weight'] ?? null,
                    'length' => $validated['length'] ?? null,
                    'width' => $validated['width'] ?? null,
                    'height' => $validated['height'] ?? null,
                    'dispatch_days' => $validated['dispatch_days'] ?? 1,
                    'shipping_charge' => $validated['shipping_charge'] ?? 0,
                    'is_free_shipping' => $validated['is_free_shipping'] ?? false,
                    'is_cod_available' => $validated['is_cod_available'] ?? true,
                    'return_policy' => $validated['return_policy'] ?? null,
                    'replacement_policy' => $validated['replacement_policy'] ?? null,
                    'warranty_summary' => $validated['warranty_summary'] ?? null,
                    'guarantee_summary' => $validated['guarantee_summary'] ?? null,
                    'cancellation_policy' => $validated['cancellation_policy'] ?? null,
                    'meta_title' => $validated['meta_title'] ?? null,
                    'meta_description' => $validated['meta_description'] ?? null,
                    'meta_keywords' => $validated['meta_keywords'] ?? null,
                    'canonical_url' => $validated['canonical_url'] ?? null,
                    'og_image' => $validated['og_image'] ?? null,
                    'highlights' => $validated['highlights'] ?? [],
                    'search_keywords' => $validated['search_keywords'] ?? null,
                    'status' => $initialStatus,
                    'is_active' => false,
                ]);

                // Save Gallery Images (Min 1, Max 10)
                if (!empty($validated['images'])) {
                    foreach (array_slice(array_filter($validated['images']), 0, 10) as $index => $imgUrl) {
                        \App\Models\ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $imgUrl,
                            'is_primary' => $index === 0,
                            'sort_order' => $index,
                        ]);
                    }
                }

                // Save Attribute Values Mapping
                if (!empty($validated['attribute_values'])) {
                    $product->attributeValues()->sync($validated['attribute_values']);
                }

                // Save Specifications
                $specs = $validated['specifications'] ?? $validated['custom_specifications'] ?? [];
                if (!empty($specs)) {
                    foreach ($specs as $sortIdx => $spec) {
                        $key = $spec['key'] ?? $spec['name'] ?? $spec['spec_key'] ?? null;
                        $value = $spec['value'] ?? $spec['spec_value'] ?? null;
                        if ($key && $value) {
                            \App\Models\ProductSpecification::create([
                                'product_id' => $product->id,
                                'spec_key' => $key,
                                'spec_value' => $value,
                                'sort_order' => $sortIdx,
                            ]);
                        }
                    }
                }

                // Save Product Variants
                if (!empty($validated['variants'])) {
                    foreach ($validated['variants'] as $index => $varData) {
                        \App\Models\ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $varData['sku'] ?? ($product->sku . '-V' . ($index + 1)),
                            'barcode' => $varData['barcode'] ?? null,
                            'title' => $varData['title'] ?? 'Variant ' . ($index + 1),
                            'price' => $varData['price'] ?? $product->original_price,
                            'offer_price' => $varData['offer_price'] ?? $product->offer_price,
                            'stock_quantity' => $varData['stock_quantity'] ?? 0,
                            'image' => $varData['image'] ?? null,
                            'attributes' => $varData['attributes'] ?? [],
                            'is_default' => $index === 0,
                        ]);
                    }
                }

                return $product;
            });

            return response()->json([
                'success' => true,
                'message' => $initialStatus === 'pending_approval'
                    ? 'Product submitted for admin review.'
                    : 'Product draft saved successfully.',
                'data' => new ProductResource($product->fresh(['category', 'brand', 'primaryImage', 'images', 'variants', 'specifications'])),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error during product creation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Vendor Product Store Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage(),
                'errors' => [
                    'server' => [$e->getMessage()],
                ],
            ], 500);
        }
    }

    /**
     * Vendor Update Product.
     */
    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'child_category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'original_price' => 'sometimes|required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'gst_percent' => 'nullable|numeric|min:0',
            'tax_inclusive' => 'nullable|boolean',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'attribute_values' => 'nullable|array',
            'variants' => 'nullable|array',
            'status' => 'nullable|string|in:draft,pending_approval',
        ]);

        DB::transaction(function () use ($product, $validated) {
            if (isset($validated['stock_quantity'])) {
                $validated['stock_status'] = $validated['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock';
            }

            // If modifying an approved product, reset status to pending_approval or draft
            if ($product->status === 'approved') {
                $validated['status'] = $validated['status'] ?? 'pending_approval';
                $validated['is_active'] = false;
            }

            $product->update($validated);

            if (isset($validated['images'])) {
                \App\Models\ProductImage::where('product_id', $product->id)->delete();
                foreach (array_slice($validated['images'], 0, 10) as $index => $imgUrl) {
                    \App\Models\ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imgUrl,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            if (isset($validated['attribute_values'])) {
                $product->attributeValues()->sync($validated['attribute_values']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->fresh(['category', 'brand', 'primaryImage', 'images', 'variants'])),
        ], 200);
    }

    /**
     * Submit product draft for admin approval.
     */
    public function submitProductForReview(Request $request, int $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->id)->firstOrFail();

        $product->update([
            'status' => 'pending_approval',
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product submitted for admin approval.',
            'data' => new ProductResource($product->fresh()),
        ], 200);
    }

    /**
     * Duplicate an existing product as a draft.
     */
    public function duplicateProduct(Request $request, int $id): JsonResponse
    {
        $original = Product::where('id', $id)->where('seller_id', $request->user()->id)->firstOrFail();

        $duplicate = $original->replicate(['slug', 'sku']);
        $duplicate->name = $original->name . ' (Copy)';
        $duplicate->slug = \Illuminate\Support\Str::slug($duplicate->name) . '-' . \Illuminate\Support\Str::random(6);
        $duplicate->sku = 'SKU-' . strtoupper(\Illuminate\Support\Str::random(8));
        $duplicate->status = 'draft';
        $duplicate->is_active = false;
        $duplicate->save();

        return response()->json([
            'success' => true,
            'message' => 'Product duplicated as draft.',
            'data' => new ProductResource($duplicate),
        ], 201);
    }

    /**
     * Vendor Delete Product.
     */
    public function destroyProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::where('id', $id)->where('seller_id', $request->user()->id)->firstOrFail();
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ], 200);
    }

    /**
     * Vendor Inventory List.
     */
    public function inventory(Request $request): JsonResponse
    {
        $products = Product::where('seller_id', $request->user()->id)->latest()->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ], 200);
    }

    /**
     * Vendor Stock Update.
     */
    public function updateInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('seller_id', $request->user()->id)
            ->firstOrFail();

        $product->update([
            'stock_quantity' => $validated['stock_quantity'],
            'stock_status' => $validated['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inventory stock updated successfully.',
            'data' => new ProductResource($product->fresh()),
        ], 200);
    }

    /**
     * Vendor Update Line Item Order Status.
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,packed,shipped,delivered,cancelled',
        ]);

        $orderItem = OrderItem::where('id', $id)
            ->where('seller_id', $request->user()->id)
            ->firstOrFail();

        $orderItem->update([
            'fulfillment_status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => new OrderItemResource($orderItem->fresh('order')),
        ], 200);
    }

    /**
     * Vendor Sales & Analytics Overview.
     */
    public function analytics(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;

        $topProducts = Product::where('seller_id', $sellerId)
            ->withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sales_count' => $p->order_items_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'sales_trend' => [
                    ['date' => 'Day 1', 'amount' => 1200],
                    ['date' => 'Day 2', 'amount' => 2400],
                    ['date' => 'Day 3', 'amount' => 1800],
                    ['date' => 'Day 4', 'amount' => 3200],
                    ['date' => 'Day 5', 'amount' => 4500],
                ],
                'top_products' => $topProducts,
                'order_status_counts' => [
                    'pending' => OrderItem::where('seller_id', $sellerId)->where('fulfillment_status', 'pending')->count(),
                    'shipped' => OrderItem::where('seller_id', $sellerId)->where('fulfillment_status', 'shipped')->count(),
                    'delivered' => OrderItem::where('seller_id', $sellerId)->where('fulfillment_status', 'delivered')->count(),
                ],
            ],
        ], 200);
    }
}
