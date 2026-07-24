<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CheckoutService
{
    protected InventoryService $inventoryService;
    protected CartService $cartService;

    public function __construct(InventoryService $inventoryService, CartService $cartService)
    {
        $this->inventoryService = $inventoryService;
        $this->cartService = $cartService;
    }

    /**
     * Process checkout from user's active cart.
     */
    public function processCheckout(User $user, int $shippingAddressId, ?int $billingAddressId = null, string $paymentMethod = 'cod'): Order
    {
        return DB::transaction(function () use ($user, $shippingAddressId, $billingAddressId, $paymentMethod) {
            // 1. Fetch active cart
            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->with(['items.product.primaryImage'])
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                throw new Exception("Your shopping cart is empty.");
            }

            // 2. Validate Addresses
            $shippingAddress = Address::where('user_id', $user->id)->where('id', $shippingAddressId)->firstOrFail();
            $billingAddress = $billingAddressId 
                ? Address::where('user_id', $user->id)->where('id', $billingAddressId)->firstOrFail()
                : $shippingAddress;

            // 3. Primary Warehouse Resolution
            $primaryWarehouse = Warehouse::where('is_primary', true)->first() 
                ?? Warehouse::where('is_active', true)->first();

            // 4. Validate stock for every item in cart
            foreach ($cart->items as $item) {
                $product = Product::approved()->find($item->product_id);
                if (!$product) {
                    throw new Exception("Product '{$item->product_id}' is no longer available.");
                }

                if ($product->stock_quantity < $item->quantity) {
                    throw new Exception("Product '{$product->name['en']}' has insufficient stock (Requested: {$item->quantity}, Available: {$product->stock_quantity}).");
                }
            }

            // 5. Generate Unique Order Number (e.g. ORD-20260720-98214)
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // 6. Calculate Financials
            $subtotal = $cart->subtotal;
            $taxAmount = 0.00; // Future Module expansion
            $shippingAmount = 0.00; // Future Module expansion
            $discountAmount = 0.00; // Future Module expansion
            $totalAmount = $subtotal + $taxAmount + $shippingAmount - $discountAmount;

            // 7. Create Order Record
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $paymentMethod,
                'shipping_address_snapshot' => $shippingAddress->toSnapshotArray(),
                'billing_address_snapshot' => $billingAddress->toSnapshotArray(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            // 8. Create Order Items & Deduct Inventory
            foreach ($cart->items as $item) {
                $product = $item->product;

                $locale = app()->getLocale();
                $prodName = is_array($product->name) 
                    ? ($product->name[$locale] ?? $product->name['en'] ?? reset($product->name)) 
                    : $product->name;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'warehouse_id' => $primaryWarehouse?->id,
                    'product_name' => $prodName,
                    'product_sku' => $product->sku,
                    'product_thumbnail' => $product->thumbnail ?? $product->primaryImage?->image_url,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->total_price,
                    'status' => 'pending',
                ]);

                // Deduct stock using InventoryService if warehouse exists
                if ($primaryWarehouse) {
                    $inv = Inventory::where('warehouse_id', $primaryWarehouse->id)->where('product_id', $product->id)->first();
                    if ($inv) {
                        $this->inventoryService->adjustStock(
                            $primaryWarehouse->id,
                            $product->id,
                            max(0, $inv->quantity - $item->quantity),
                            "Order Fulfillment: {$orderNumber}",
                            $user->id
                        );
                    } else {
                        // Directly update product stock if no specific inventory record exists
                        $product->decrement('stock_quantity', $item->quantity);
                        $product->update([
                            'stock_status' => $product->fresh()->stock_quantity > 0 ? 'in_stock' : 'out_of_stock'
                        ]);
                    }
                } else {
                    $product->decrement('stock_quantity', $item->quantity);
                    $product->update([
                        'stock_status' => $product->fresh()->stock_quantity > 0 ? 'in_stock' : 'out_of_stock'
                    ]);
                }
            }

            // 9. Mark cart as converted and clear items
            $cart->update(['status' => 'converted']);
            $this->cartService->clearCart($cart);

            return $order->fresh(['items.product', 'user']);
        });
    }
}
