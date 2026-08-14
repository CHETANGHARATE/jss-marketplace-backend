<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessSettlementRequest;
use App\Http\Requests\VerifyKYCRequest;
use App\Http\Resources\SettlementResource;
use App\Http\Resources\VendorStoreResource;
use App\Models\Settlement;
use App\Models\VendorStore;
use App\Services\VendorCommissionService;
use App\Services\VendorStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AdminVendorController extends Controller
{
    protected VendorStoreService $storeService;
    protected VendorCommissionService $commissionService;

    public function __construct(VendorStoreService $storeService, VendorCommissionService $commissionService)
    {
        $this->storeService = $storeService;
        $this->commissionService = $commissionService;
    }

    /**
     * List all vendor stores with search & status filters.
     */
    public function stores(Request $request): JsonResponse
    {
        $query = VendorStore::with(['user', 'wallet']);

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->input('kyc_status'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'approved') {
                $query->whereIn('status', ['active', 'approved']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_email', 'like', "%{$search}%")
                    ->orWhere('store_phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $stores = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => VendorStoreResource::collection($stores),
            'meta' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
            ]
        ], 200);
    }

    /**
     * Get vendor statistics breakdown from DB.
     */
    public function stats(): JsonResponse
    {
        $pending = VendorStore::where('status', 'pending')->count();
        $approved = VendorStore::whereIn('status', ['active', 'approved'])->count();
        $suspended = VendorStore::where('status', 'suspended')->count();
        $rejected = VendorStore::where('status', 'rejected')->count();
        $total = VendorStore::count();

        return response()->json([
            'success' => true,
            'data' => [
                'pending_count' => $pending,
                'approved_count' => $approved,
                'suspended_count' => $suspended,
                'rejected_count' => $rejected,
                'total_count' => $total,
            ]
        ], 200);
    }

    /**
     * Get single Vendor Store detailed profile & metrics.
     */
    public function show(int $id): JsonResponse
    {
        $store = VendorStore::with(['user', 'wallet', 'settlements'])->findOrFail($id);

        $totalProducts = \App\Models\Product::where('seller_id', $store->user_id)->count();
        $activeProducts = \App\Models\Product::where('seller_id', $store->user_id)->where('status', 'active')->count();
        $pendingProducts = \App\Models\Product::where('seller_id', $store->user_id)->where('status', 'pending')->count();
        $rejectedProducts = \App\Models\Product::where('seller_id', $store->user_id)->where('status', 'rejected')->count();

        $recentProducts = \App\Models\Product::where('seller_id', $store->user_id)
            ->latest('id')
            ->take(10)
            ->get(['id', 'name', 'slug', 'base_price', 'stock', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'store' => new VendorStoreResource($store),
                'catalog' => [
                    'total_products' => $totalProducts,
                    'active_products' => $activeProducts,
                    'pending_products' => $pendingProducts,
                    'rejected_products' => $rejectedProducts,
                    'recent_products' => $recentProducts,
                ],
                'financial' => [
                    'balance' => (float) ($store->wallet?->balance ?? 0),
                    'pending_balance' => (float) ($store->wallet?->pending_balance ?? 0),
                    'commission_rate' => (float) $store->commission_rate,
                    'settlements_count' => $store->settlements->count(),
                ],
            ]
        ], 200);
    }

    /**
     * Moderate vendor KYC & activate store.
     */
    public function verifyKYC(VerifyKYCRequest $request, int $id): JsonResponse
    {
        $store = VendorStore::findOrFail($id);
        $validated = $request->validated();

        $updatedStore = $this->storeService->verifyKYC($store, $validated['kyc_status']);

        return response()->json([
            'success' => true,
            'message' => "Vendor KYC status updated to '{$validated['kyc_status']}'.",
            'data' => new VendorStoreResource($updatedStore),
        ], 200);
    }

    /**
     * List payout settlement requests.
     */
    public function settlements(Request $request): JsonResponse
    {
        $query = Settlement::with('store');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $settlements = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => SettlementResource::collection($settlements),
        ], 200);
    }

    /**
     * Process settlement payout status.
     */
    public function processSettlement(ProcessSettlementRequest $request, int $id): JsonResponse
    {
        try {
            $settlement = Settlement::findOrFail($id);
            $validated = $request->validated();

            $updatedSettlement = $this->commissionService->processSettlement(
                $settlement,
                $validated['status'],
                $validated['reference_number'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "Settlement status updated to '{$validated['status']}'.",
                'data' => new SettlementResource($updatedSettlement),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve a vendor store.
     */
    public function approveStore(int $id): JsonResponse
    {
        $store = VendorStore::findOrFail($id);
        $updatedStore = $this->storeService->verifyKYC($store, 'verified');

        return response()->json([
            'success' => true,
            'message' => 'Vendor store approved and activated.',
            'data'    => new VendorStoreResource($updatedStore),
        ], 200);
    }

    /**
     * Reject a vendor store.
     */
    public function rejectStore(Request $request, int $id): JsonResponse
    {
        $store = VendorStore::findOrFail($id);
        $store->update([
            'status' => 'rejected',
            'kyc_status' => 'rejected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor store application rejected.',
            'data'    => new VendorStoreResource($store->fresh()),
        ], 200);
    }

    /**
     * Suspend a vendor store.
     */
    public function suspendStore(int $id): JsonResponse
    {
        $store = VendorStore::findOrFail($id);
        $store->update(['status' => 'suspended']);

        return response()->json([
            'success' => true,
            'message' => 'Vendor store has been suspended.',
            'data'    => new VendorStoreResource($store->fresh()),
        ], 200);
    }

    /**
     * Activate / Reactivate a vendor store.
     */
    public function activateStore(int $id): JsonResponse
    {
        $store = VendorStore::findOrFail($id);
        $store->update([
            'status' => 'active',
            'kyc_status' => 'verified',
        ]);

        if ($store->user) {
            $store->user->assignRoleSafely(\App\Enums\UserRole::SELLER->value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vendor store has been activated.',
            'data'    => new VendorStoreResource($store->fresh()),
        ], 200);
    }
}
