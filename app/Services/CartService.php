<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class CartService
{
    /**
     * Retrieve or create an active cart for an authenticated user or guest session.
     */
    public function getOrCreateCart(?int $userId = null, ?string $sessionId = null): Cart
    {
        if (!$userId && !$sessionId) {
            throw new Exception("Either user ID or session ID must be provided.");
        }

        if ($userId) {
            return Cart::firstOrCreate(
                ['user_id' => $userId, 'status' => 'active'],
                ['session_id' => $sessionId]
            )->load(['items.product.primaryImage', 'items.product.category', 'items.product.brand']);
        }

        return Cart::firstOrCreate(
            ['session_id' => $sessionId, 'status' => 'active'],
            ['user_id' => null]
        )->load(['items.product.primaryImage', 'items.product.category', 'items.product.brand']);
    }

    /**
     * Add product to cart with stock validation.
     */
    public function addItem(Cart $cart, int $productId, int $quantity = 1): CartItem
    {
        if ($quantity <= 0) {
            throw new Exception("Quantity must be at least 1.");
        }

        $product = Product::approved()->find($productId);
        if (!$product) {
            throw new Exception("Product is unavailable or out of stock.");
        }

        return DB::transaction(function () use ($cart, $product, $quantity) {
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            $newQuantity = ($existingItem ? $existingItem->quantity : 0) + $quantity;

            if ($product->stock_quantity < $newQuantity) {
                throw new Exception("Requested quantity exceeds available stock ({$product->stock_quantity} available).");
            }

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $newQuantity,
                    'unit_price' => $product->offer_price,
                    'total_price' => $newQuantity * $product->offer_price,
                ]);
                return $existingItem->fresh('product');
            }

            return CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->offer_price,
                'total_price' => $quantity * $product->offer_price,
            ])->load('product');
        });
    }

    /**
     * Update quantity of an item in cart.
     */
    public function updateItem(Cart $cart, int $itemId, int $quantity): CartItem
    {
        $item = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->firstOrFail();

        if ($quantity <= 0) {
            $item->delete();
            return $item;
        }

        $product = Product::approved()->findOrFail($item->product_id);
        if ($product->stock_quantity < $quantity) {
            throw new Exception("Requested quantity exceeds available stock ({$product->stock_quantity} available).");
        }

        $item->update([
            'quantity' => $quantity,
            'unit_price' => $product->offer_price,
            'total_price' => $quantity * $product->offer_price,
        ]);

        return $item->fresh('product');
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Cart $cart, int $itemId): bool
    {
        return (bool) CartItem::where('cart_id', $cart->id)->where('id', $itemId)->delete();
    }

    /**
     * Clear all items from cart.
     */
    public function clearCart(Cart $cart): bool
    {
        return (bool) CartItem::where('cart_id', $cart->id)->delete();
    }
}
