<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoryService = app(InventoryService::class);
        $primaryWh = Warehouse::where('code', 'WH-MUM-01')->first();
        $delhiWh = Warehouse::where('code', 'WH-DEL-01')->first();
        $admin = User::where('email', 'admin@jss.solutions')->first();
        $products = Product::all();

        foreach ($products as $product) {
            if ($primaryWh) {
                $inventoryService->addStock(
                    $primaryWh->id,
                    $product->id,
                    50,
                    'INITIAL-SEED',
                    'Initial stock seeding for primary warehouse',
                    $admin?->id
                );
            }

            if ($delhiWh) {
                $inventoryService->addStock(
                    $delhiWh->id,
                    $product->id,
                    25,
                    'INITIAL-SEED-DELHI',
                    'Initial stock seeding for Delhi regional warehouse',
                    $admin?->id
                );
            }
        }
    }
}
