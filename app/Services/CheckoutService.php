<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Inventory;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CheckoutService
{
    protected InventoryService $inventoryService;
    protected CartService $cartService;
    protected OrderNotificationService $notificationService;

    public function __construct(
        InventoryService $inventoryService,
        CartService $cartService,
        OrderNotificationService $notificationService
    ) {
        $this->inventoryService = $inventoryService;
        $this->cartService = $cartService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process checkout from user's active cart.
     */
    public function processCheckout(
        User $user,
        int $shippingAddressId,
        ?int $billingAddressId = null,
        string $paymentMethod = 'cod',
        ?int $pointsToRedeem = null,
        ?string $couponCode = null
    ): Order {
        return DB::transaction(function () use (
            $user,
            $shippingAddressId,
            $billingAddressId,
            $paymentMethod,
            $pointsToRedeem,
            $couponCode
        ) {
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
                    $locale = app()->getLocale();
                    $nameStr = is_array($product->name) ? ($product->name[$locale] ?? $product->name['en'] ?? 'Item') : $product->name;
                    throw new Exception("Product '{$nameStr}' has insufficient stock (Requested: {$item->quantity}, Available: {$product->stock_quantity}).");
                }
            }

            // 5. Financial Calculations & Coupon Logic
            $subtotal = (float) $cart->subtotal;
            $discountAmount = 0.00;
            $appliedCoupon = null;

            if (!empty($couponCode)) {
                $coupon = Coupon::where('code', strtoupper(trim($couponCode)))
                    ->where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now())
                    ->first();

                if ($coupon && $subtotal >= (float) $coupon->min_spend) {
                    if ($coupon->discount_type === 'percentage') {
                        $calc = ($subtotal * (float) $coupon->discount_value) / 100;
                        $discountAmount = $coupon->max_discount ? min($calc, (float) $coupon->max_discount) : $calc;
                    } else {
                        $discountAmount = min((float) $coupon->discount_value, $subtotal);
                    }
                    $appliedCoupon = $coupon;
                }
            }

            // 6. JSS Coins / Loyalty Points Redemption Logic (Feature 25)
            $loyaltyPointsRedeemed = 0;
            $loyaltyDiscountAmount = 0.00;

            if ($pointsToRedeem && $pointsToRedeem > 0) {
                $loyalty = LoyaltyPoint::firstOrCreate(
                    ['user_id' => $user->id],
                    ['points_balance' => 0, 'total_earned' => 0]
                );

                if ($pointsToRedeem > $loyalty->points_balance) {
                    throw new Exception("You only have {$loyalty->points_balance} JSS Coins available.");
                }

                // Configurable conversion rate: 1 Coin = ₹1.00 (default)
                $pointValueInr = (float) Setting::get('loyalty_point_value_inr', 1.00);
                $maxRedemptionPercent = (float) Setting::get('loyalty_max_redemption_percent', 50.0);

                // Max usable value cannot exceed configurable % of subtotal (e.g. 50%)
                $maxAllowedDiscount = ($subtotal * $maxRedemptionPercent) / 100;
                $requestedDiscount = $pointsToRedeem * $pointValueInr;

                if ($requestedDiscount > $maxAllowedDiscount) {
                    $maxCoins = (int) floor($maxAllowedDiscount / $pointValueInr);
                    throw new Exception("You can redeem a maximum of {$maxCoins} JSS Coins (up to {$maxRedemptionPercent}% of order subtotal).");
                }

                // Ensure discount doesn't make total negative after coupon
                $remainingSubtotal = max(0.00, $subtotal - $discountAmount);
                $finalCoinsDiscount = min($requestedDiscount, $remainingSubtotal);

                if ($finalCoinsDiscount > 0) {
                    $loyaltyDiscountAmount = $finalCoinsDiscount;
                    $loyaltyPointsRedeemed = (int) ceil($finalCoinsDiscount / $pointValueInr);

                    // Atomically deduct points balance
                    $loyalty->decrement('points_balance', $loyaltyPointsRedeemed);
                }
            }

            // 7. Calculate Final Order Amounts
            $taxAmount = 0.00;
            $shippingAmount = ($subtotal >= 499) ? 0.00 : 49.00; // Free shipping over ₹499
            $netDiscount = $discountAmount + $loyaltyDiscountAmount;
            $totalAmount = max(0.00, $subtotal + $taxAmount + $shippingAmount - $netDiscount);

            // 8. Generate Unique Order Number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // 9. Create Order Record
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user->id,
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress->id,
                'status' => 'pending',
                'payment_status' => ($paymentMethod === 'cod') ? 'pending' : 'pending',
                'payment_method' => $paymentMethod,
                'shipping_address_snapshot' => $shippingAddress->toSnapshotArray(),
                'billing_address_snapshot' => $billingAddress->toSnapshotArray(),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'loyalty_points_redeemed' => $loyaltyPointsRedeemed,
                'loyalty_discount_amount' => $loyaltyDiscountAmount,
                'total_amount' => $totalAmount,
            ]);

            // 10. Record Loyalty Transaction Ledger (Feature 25)
            if ($loyaltyPointsRedeemed > 0) {
                LoyaltyTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'points' => -$loyaltyPointsRedeemed,
                    'type' => 'redeemed',
                    'inr_value' => $loyaltyDiscountAmount,
                    'notes' => "Redeemed {$loyaltyPointsRedeemed} JSS Coins for order #{$orderNumber}",
                ]);
            }

            // Record Coupon Usage if coupon was applied
            if ($appliedCoupon) {
                CouponUsage::create([
                    'coupon_id' => $appliedCoupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
                $appliedCoupon->increment('times_used');
            }

            // 11. Create Order Items & Deduct Inventory
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

            // 12. Mark cart as converted and clear items
            $cart->update(['status' => 'converted']);
            $this->cartService->clearCart($cart);

            // 13. Dispatch Multi-Channel Notifications (Feature 39)
            $this->notificationService->notifyOrderPlaced($order);

            return $order->fresh(['items.product.primaryImage', 'user', 'shippingAddress', 'billingAddress']);
        });
    }
}
