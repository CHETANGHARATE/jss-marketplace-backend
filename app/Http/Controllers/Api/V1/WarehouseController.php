<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of active warehouses.
     */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => WarehouseResource::collection($warehouses),
        ], 200);
    }

    /**
     * Display single warehouse details.
     */
    public function show(int $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new WarehouseResource($warehouse),
        ], 200);
    }

    /**
     * Store a newly created warehouse (Admin only).
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $warehouse = Warehouse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully.',
            'data' => new WarehouseResource($warehouse),
        ], 201);
    }

    /**
     * SoftDelete a warehouse (Admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found.',
            ], 404);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully.',
        ], 200);
    }
}
