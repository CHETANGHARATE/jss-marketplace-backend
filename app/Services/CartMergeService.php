<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartMergeService
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Merge guest cart into user's active cart upon login or guest merge request.
     */
    public function mergeGuestCartToUser(string $sessionId, User $user): Cart
    {
        return DB::transaction(function () use ($sessionId, $user) {
            $guestCart = Cart::where('session_id', $sessionId)
                ->where('status', 'active')
                ->with('items')
                ->first();

            $userCart = $this->cartService->getOrCreateCart($user->id);

            if (!$guestCart || $guestCart->items->isEmpty()) {
                return $userCart;
            }

            foreach ($guestCart->items as $gItem) {
                try {
                    $this->cartService->addItem($userCart, $gItem->product_id, $gItem->quantity);
                } catch (\Exception $e) {
                    // Ignore item if stock unavailable during merge
                    continue;
                }
            }

            // Mark guest cart as converted
            $guestCart->update(['status' => 'converted']);

            return $userCart->fresh(['items.product.primaryImage', 'items.product.category', 'items.product.brand']);
        });
    }
}
