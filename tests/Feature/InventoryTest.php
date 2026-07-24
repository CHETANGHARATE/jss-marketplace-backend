<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_anyone_can_view_active_warehouses(): void
    {
        Warehouse::create([
            'name' => 'Mumbai Hub',
            'code' => 'WH-MUM-TEST',
            'address_line_1' => 'MIDC',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400093',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/warehouses');

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'WH-MUM-TEST']);
    }

    public function test_admin_can_add_stock_and_generate_movement_ledger(): void
    {
        $category = Category::create(['name' => ['en' => 'Tech'], 'slug' => 'tech']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'STOCK-PROD-01',
            'name' => ['en' => 'Test Product'],
            'slug' => 'test-product',
            'original_price' => 100,
            'offer_price' => 100,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Delhi Hub',
            'code' => 'WH-DEL-TEST',
            'address_line_1' => 'Pace City',
            'city' => 'Gurugram',
            'state' => 'Haryana',
            'pincode' => '122001',
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        $payload = [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'reference' => 'PO-1001',
            'notes' => 'Inbound shipment from supplier',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/inventories/add-stock', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['quantity' => 100]);

        $this->assertDatabaseHas('inventories', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'inbound',
            'quantity' => 100,
        ]);

        // Assert product global stock synced
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 100,
            'stock_status' => 'in_stock',
        ]);
    }

    public function test_admin_can_transfer_stock_between_warehouses(): void
    {
        $category = Category::create(['name' => ['en' => 'Books'], 'slug' => 'books']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'TRANSFER-PROD-01',
            'name' => ['en' => 'Transfer Product'],
            'slug' => 'transfer-product',
            'original_price' => 200,
            'offer_price' => 200,
        ]);

        $wh1 = Warehouse::create(['name' => 'Wh 1', 'code' => 'WH-1', 'address_line_1' => 'A', 'city' => 'C1', 'state' => 'S1', 'pincode' => '111']);
        $wh2 = Warehouse::create(['name' => 'Wh 2', 'code' => 'WH-2', 'address_line_1' => 'B', 'city' => 'C2', 'state' => 'S2', 'pincode' => '222']);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $admin->assignRole(UserRole::ADMIN->value);

        // Add 50 units to WH1
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/inventories/add-stock', [
            'warehouse_id' => $wh1->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        // Transfer 20 units from WH1 to WH2
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/inventories/transfer', [
            'from_warehouse_id' => $wh1->id,
            'to_warehouse_id' => $wh2->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('inventories', ['warehouse_id' => $wh1->id, 'quantity' => 30]);
        $this->assertDatabaseHas('inventories', ['warehouse_id' => $wh2->id, 'quantity' => 20]);
    }
}
