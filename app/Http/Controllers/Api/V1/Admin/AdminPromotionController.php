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

    /**
     * List all coupons.
     */
    public function indexCoupons(): JsonResponse
    {
        $coupons = Coupon::latest()->paginate(15);

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
        $validated['code'] = strtoupper($validated['code']);

        $coupon = Coupon::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully.',
            'data' => new CouponResource($coupon),
        ], 201);
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

    /**
     * List flash sales.
     */
    public function indexFlashSales(): JsonResponse
    {
        $flashSales = FlashSale::with('products.product')->latest()->paginate(15);

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
        $flashSale = $this->flashSaleService->createFlashSale($validated, $validated['products']);

        return response()->json([
            'success' => true,
            'message' => 'Flash sale campaign created successfully.',
            'data' => new FlashSaleResource($flashSale),
        ], 201);
    }
}
