<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyCouponRequest;
use App\Http\Resources\FlashSaleResource;
use App\Http\Resources\LoyaltyPointResource;
use App\Models\LoyaltyPoint;
use App\Services\FlashSaleService;
use App\Services\PromotionEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class PromotionController extends Controller
{
    protected PromotionEngineService $promotionService;
    protected FlashSaleService $flashSaleService;

    public function __construct(PromotionEngineService $promotionService, FlashSaleService $flashSaleService)
    {
        $this->promotionService = $promotionService;
        $this->flashSaleService = $flashSaleService;
    }

    /**
     * Validate & Apply Coupon Code.
     */
    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $this->promotionService->applyCoupon(
                $validated['code'],
                (float) $validated['cart_total'],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Coupon code applied successfully.',
                'data' => $result,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get active flash sale campaigns.
     */
    public function flashSales(): JsonResponse
    {
        $flashSales = $this->flashSaleService->getActiveFlashSales();

        return response()->json([
            'success' => true,
            'data' => FlashSaleResource::collection($flashSales),
        ], 200);
    }

    /**
     * Get user loyalty points balance.
     */
    public function loyaltyPoints(Request $request): JsonResponse
    {
        $loyalty = LoyaltyPoint::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['points_balance' => 0, 'total_earned' => 0]
        );

        return response()->json([
            'success' => true,
            'data' => new LoyaltyPointResource($loyalty),
        ], 200);
    }
}
