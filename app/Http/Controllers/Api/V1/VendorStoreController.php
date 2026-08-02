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
     * Vendor Create Product.
     */
    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'original_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'status' => 'nullable|string|in:draft,published,pending_approval',
        ]);

        $sellerId = $request->user()->id;
        $slug = \Illuminate\Support\Str::slug($validated['name']) . '-' . \Illuminate\Support\Str::random(4);

        $product = Product::create([
            'seller_id' => $sellerId,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'sku' => $validated['sku'] ?? ('SKU-' . strtoupper(\Illuminate\Support\Str::random(8))),
            'original_price' => $validated['original_price'],
            'sale_price' => $validated['sale_price'] ?? $validated['original_price'],
            'offer_price' => $validated['offer_price'] ?? $validated['sale_price'] ?? $validated['original_price'],
            'stock_quantity' => $validated['stock_quantity'],
            'stock_status' => $validated['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock',
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'published',
            'is_approved' => true,
        ]);

        if (!empty($validated['images'])) {
            foreach ($validated['images'] as $index => $imgUrl) {
                \App\Models\ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $imgUrl,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product->fresh(['category', 'brand', 'primaryImage'])),
        ], 201);
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
            'brand_id' => 'nullable|exists:brands,id',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'original_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'status' => 'nullable|string|in:draft,published,pending_approval',
        ]);

        if (isset($validated['stock_quantity'])) {
            $validated['stock_status'] = $validated['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock';
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->fresh(['category', 'brand', 'primaryImage'])),
        ], 200);
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
