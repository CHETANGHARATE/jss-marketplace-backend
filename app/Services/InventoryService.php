<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    /**
     * Add physical stock to a warehouse inventory atomically.
     */
    public function addStock(int $warehouseId, int $productId, int $quantity, ?string $reference = null, ?string $notes = null, ?int $userId = null): Inventory
    {
        if ($quantity <= 0) {
            throw new Exception("Stock addition quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $reference, $notes, $userId) {
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $inventory = Inventory::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $beforeQty = $inventory->quantity;
            $afterQty = $beforeQty + $quantity;

            $inventory->update(['quantity' => $afterQty]);

            StockMovement::create([
                'inventory_id' => $inventory->id,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'created_by' => $userId,
                'type' => 'inbound',
                'quantity' => $quantity,
                'before_quantity' => $beforeQty,
                'after_quantity' => $afterQty,
                'reference_type' => $reference,
                'notes' => $notes ?? 'Inbound stock addition',
            ]);

            $this->syncProductGlobalStock($productId);

            return $inventory->fresh(['warehouse', 'product']);
        });
    }

    /**
     * Audit adjust physical stock to a set value.
     */
    public function adjustStock(int $warehouseId, int $productId, int $newQuantity, ?string $reason = null, ?int $userId = null): Inventory
    {
        if ($newQuantity < 0) {
            throw new Exception("Adjusted stock quantity cannot be negative.");
        }

        return DB::transaction(function () use ($warehouseId, $productId, $newQuantity, $reason, $userId) {
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            $beforeQty = $inventory->quantity;
            $difference = $newQuantity - $beforeQty;

            $inventory->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'inventory_id' => $inventory->id,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'created_by' => $userId,
                'type' => 'adjustment',
                'quantity' => $difference,
                'before_quantity' => $beforeQty,
                'after_quantity' => $newQuantity,
                'reference_type' => 'Stock Audit',
                'notes' => $reason ?? 'Inventory count adjustment',
            ]);

            $this->syncProductGlobalStock($productId);

            return $inventory->fresh(['warehouse', 'product']);
        });
    }

    /**
     * Transfer stock from one warehouse to another atomically.
     */
    public function transferStock(int $fromWarehouseId, int $toWarehouseId, int $productId, int $quantity, ?string $reference = null, ?int $userId = null): array
    {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new Exception("Source and destination warehouses must be different.");
        }

        if ($quantity <= 0) {
            throw new Exception("Transfer quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $quantity, $reference, $userId) {
            // Source Warehouse
            $sourceInv = Inventory::where('warehouse_id', $fromWarehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sourceInv->available_quantity < $quantity) {
                throw new Exception("Insufficient available stock in source warehouse.");
            }

            $sourceBefore = $sourceInv->quantity;
            $sourceAfter = $sourceBefore - $quantity;
            $sourceInv->update(['quantity' => $sourceAfter]);

            StockMovement::create([
                'inventory_id' => $sourceInv->id,
                'warehouse_id' => $fromWarehouseId,
                'product_id' => $productId,
                'created_by' => $userId,
                'type' => 'transfer',
                'quantity' => -$quantity,
                'before_quantity' => $sourceBefore,
                'after_quantity' => $sourceAfter,
                'reference_type' => $reference ?? 'WAREHOUSE-TRANSFER',
                'notes' => "Transfer outbound to Warehouse ID: {$toWarehouseId}",
            ]);

            // Destination Warehouse
            $destInv = Inventory::where('warehouse_id', $toWarehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$destInv) {
                $destInv = Inventory::create([
                    'warehouse_id' => $toWarehouseId,
                    'product_id' => $productId,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $destBefore = $destInv->quantity;
            $destAfter = $destBefore + $quantity;
            $destInv->update(['quantity' => $destAfter]);

            StockMovement::create([
                'inventory_id' => $destInv->id,
                'warehouse_id' => $toWarehouseId,
                'product_id' => $productId,
                'created_by' => $userId,
                'type' => 'transfer',
                'quantity' => $quantity,
                'before_quantity' => $destBefore,
                'after_quantity' => $destAfter,
                'reference_type' => $reference ?? 'WAREHOUSE-TRANSFER',
                'notes' => "Transfer inbound from Warehouse ID: {$fromWarehouseId}",
            ]);

            $this->syncProductGlobalStock($productId);

            return [
                'source' => $sourceInv->fresh(),
                'destination' => $destInv->fresh(),
            ];
        });
    }

    /**
     * Synchronize total product stock quantity & stock status across all warehouses.
     */
    public function syncProductGlobalStock(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        $totalAvailable = Inventory::where('product_id', $productId)
            ->where('is_active', true)
            ->selectRaw('SUM(quantity - reserved_quantity) as total')
            ->value('total') ?? 0;

        $totalAvailable = max(0, (int) $totalAvailable);

        $product->update([
            'stock_quantity' => $totalAvailable,
            'stock_status' => $totalAvailable > 0 ? 'in_stock' : 'out_of_stock',
        ]);
    }
}
