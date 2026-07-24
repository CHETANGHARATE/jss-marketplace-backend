<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateShippingRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\ShippingCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    protected ShippingCalculatorService $calculatorService;

    public function __construct(ShippingCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    /**
     * Public calculation of shipping fee by pincode.
     */
    public function calculate(CalculateShippingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $rate = $this->calculatorService->calculateRate(
            $validated['pincode'],
            (float) ($validated['cart_total'] ?? 0.0),
            (float) ($validated['weight_kg'] ?? 0.5)
        );

        return response()->json([
            'success' => true,
            'data' => $rate,
        ], 200);
    }

    /**
     * Public tracking of shipment timeline by AWB / Tracking number.
     */
    public function track(string $trackingNumber): JsonResponse
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['courier', 'logs'])
            ->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment tracking number not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ShipmentResource($shipment),
        ], 200);
    }

    /**
     * Customer view of shipment for an order.
     */
    public function orderShipment(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $shipment = Shipment::where('order_id', $order->id)->with(['courier', 'logs'])->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not created for this order yet.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ShipmentResource($shipment),
        ], 200);
    }
}
