<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateShipmentRequest;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\StoreShippingZoneRequest;
use App\Http\Requests\UpdateShipmentStatusRequest;
use App\Http\Resources\CourierResource;
use App\Http\Resources\ShipmentResource;
use App\Http\Resources\ShippingZoneResource;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingZone;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AdminShippingController extends Controller
{
    protected ShipmentService $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * List all shipping zones.
     */
    public function zones(): JsonResponse
    {
        $zones = ShippingZone::with('methods')->get();

        return response()->json([
            'success' => true,
            'data' => ShippingZoneResource::collection($zones),
        ], 200);
    }

    /**
     * Store new shipping zone.
     */
    public function storeZone(StoreShippingZoneRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $zone = ShippingZone::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Shipping zone created.',
            'data' => new ShippingZoneResource($zone),
        ], 201);
    }

    /**
     * List all couriers.
     */
    public function couriers(): JsonResponse
    {
        $couriers = Courier::all();

        return response()->json([
            'success' => true,
            'data' => CourierResource::collection($couriers),
        ], 200);
    }

    /**
     * Store new courier partner.
     */
    public function storeCourier(StoreCourierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $courier = Courier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Courier partner added.',
            'data' => new CourierResource($courier),
        ], 201);
    }

    /**
     * List marketplace shipments across orders.
     */
    public function shipments(Request $request): JsonResponse
    {
        $query = Shipment::with(['courier', 'order', 'logs']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $shipments = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ShipmentResource::collection($shipments),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'total' => $shipments->total(),
            ]
        ], 200);
    }

    /**
     * Create shipment for an order.
     */
    public function createShipment(CreateShipmentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $order = Order::findOrFail($validated['order_id']);

            $shipment = $this->shipmentService->createShipmentForOrder(
                $order,
                $validated['courier_id'],
                $validated['warehouse_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Shipment created and label generated.',
                'data' => new ShipmentResource($shipment),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update shipment status and location log.
     */
    public function updateStatus(UpdateShipmentStatusRequest $request, int $id): JsonResponse
    {
        try {
            $shipment = Shipment::findOrFail($id);
            $validated = $request->validated();

            $updatedShipment = $this->shipmentService->updateShipmentStatus(
                $shipment,
                $validated['status'],
                $validated['location'] ?? null,
                $validated['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "Shipment status updated to '{$validated['status']}'.",
                'data' => new ShipmentResource($updatedShipment),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
