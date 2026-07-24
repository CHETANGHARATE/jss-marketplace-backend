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
     * List all vendor stores.
     */
    public function stores(Request $request): JsonResponse
    {
        $query = VendorStore::with(['user', 'wallet']);

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->input('kyc_status'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $stores = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => VendorStoreResource::collection($stores),
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
     * Approve a vendor store (shortcut for verifyKYC with 'verified' status).
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
}
