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
}
