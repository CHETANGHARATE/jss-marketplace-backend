<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddStockRequest;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\TransferStockRequest;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of inventory records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with(['warehouse', 'product']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->boolean('low_stock')) {
            $query->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold');
        }

        $inventories = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => InventoryResource::collection($inventories),
            'meta' => [
                'current_page' => $inventories->currentPage(),
                'last_page' => $inventories->lastPage(),
                'total' => $inventories->total(),
            ]
        ], 200);
    }

    /**
     * Add inbound stock to a warehouse.
     */
    public function addStock(AddStockRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $inventory = $this->inventoryService->addStock(
                $validated['warehouse_id'],
                $validated['product_id'],
                $validated['quantity'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully.',
                'data' => new InventoryResource($inventory),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Audit adjust stock quantity.
     */
    public function adjustStock(AdjustStockRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $inventory = $this->inventoryService->adjustStock(
                $validated['warehouse_id'],
                $validated['product_id'],
                $validated['new_quantity'],
                $validated['reason'],
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'data' => new InventoryResource($inventory),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Inter-warehouse stock transfer.
     */
    public function transfer(TransferStockRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $this->inventoryService->transferStock(
                $validated['from_warehouse_id'],
                $validated['to_warehouse_id'],
                $validated['product_id'],
                $validated['quantity'],
                $validated['reference'] ?? null,
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock transferred successfully.',
                'data' => [
                    'source' => new InventoryResource($result['source']),
                    'destination' => new InventoryResource($result['destination']),
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Low stock alert report.
     */
    public function lowStockReport(): JsonResponse
    {
        $lowStockItems = Inventory::with(['warehouse', 'product'])
            ->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold')
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventoryResource::collection($lowStockItems),
        ], 200);
    }

    /**
     * Display audit trail of stock movements.
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['warehouse', 'product', 'creator'])->latest();

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        $movements = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => StockMovementResource::collection($movements),
        ], 200);
    }
}
