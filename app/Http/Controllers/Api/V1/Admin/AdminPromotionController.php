<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Requests\StoreFlashSaleRequest;
use App\Http\Resources\CouponResource;
use App\Http\Resources\FlashSaleResource;
use App\Models\Coupon;
use App\Models\FlashSale;
use App\Services\FlashSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPromotionController extends Controller
{
    protected FlashSaleService $flashSaleService;

    public function __construct(FlashSaleService $flashSaleService)
    {
        $this->flashSaleService = $flashSaleService;
    }

    // ─── Coupons ─────────────────────────────────────────────────────────────

    /**
     * List all coupons.
     */
    public function indexCoupons(): JsonResponse
    {
        $coupons = Coupon::latest('id')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => CouponResource::collection($coupons),
        ], 200);
    }

    /**
     * Store new coupon.
     */
    public function storeCoupon(StoreCouponRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper(trim($validated['code']));

        $coupon = Coupon::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Coupon '{$coupon->code}' created successfully.",
            'data' => new CouponResource($coupon),
        ], 201);
    }

    /**
     * Update existing coupon.
     */
    public function updateCoupon(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code,' . $coupon->id],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', 'string', 'in:percentage,fixed_amount'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $coupon->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Coupon '{$coupon->code}' updated successfully.",
            'data' => new CouponResource($coupon),
        ], 200);
    }

    /**
     * Toggle coupon active/inactive status.
     */
    public function toggleCouponStatus(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $newStatus = !$coupon->is_active;
        $coupon->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Coupon '{$coupon->code}' is now " . ($newStatus ? 'active' : 'inactive') . '.',
            'data' => new CouponResource($coupon),
        ], 200);
    }

    /**
     * Delete coupon.
     */
    public function destroyCoupon(int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully.',
        ], 200);
    }

    // ─── Flash Sales ─────────────────────────────────────────────────────────

    /**
     * List flash sales.
     */
    public function indexFlashSales(): JsonResponse
    {
        $flashSales = FlashSale::with('products.product')->latest('id')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => FlashSaleResource::collection($flashSales),
        ], 200);
    }

    /**
     * Create flash sale campaign.
     */
    public function storeFlashSale(StoreFlashSaleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $products = $validated['products'] ?? [];
        $flashSale = $this->flashSaleService->createFlashSale($validated, $products);

        return response()->json([
            'success' => true,
            'message' => "Flash sale event '{$flashSale->title}' created successfully.",
            'data' => new FlashSaleResource($flashSale),
        ], 201);
    }

    /**
     * Update existing flash sale.
     */
    public function updateFlashSale(Request $request, int $id): JsonResponse
    {
        $flashSale = FlashSale::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'discount_percentage' => ['sometimes', 'required', 'numeric', 'between:1,99'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'required', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $flashSale->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Flash sale '{$flashSale->title}' updated successfully.",
            'data' => new FlashSaleResource($flashSale->fresh('products.product')),
        ], 200);
    }

    /**
     * Toggle flash sale active status.
     */
    public function toggleFlashSaleStatus(int $id): JsonResponse
    {
        $flashSale = FlashSale::findOrFail($id);
        $newStatus = !$flashSale->is_active;
        $flashSale->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Flash sale '{$flashSale->title}' is now " . ($newStatus ? 'active' : 'inactive') . '.',
            'data' => new FlashSaleResource($flashSale->fresh('products.product')),
        ], 200);
    }

    /**
     * Delete flash sale.
     */
    public function destroyFlashSale(int $id): JsonResponse
    {
        $flashSale = FlashSale::findOrFail($id);
        $flashSale->products()->delete();
        $flashSale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Flash sale event deleted successfully.',
        ], 200);
    }
}
