<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\MergeCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\SavedCartItem;
use App\Services\CartMergeService;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class CartController extends Controller
{
    protected CartService $cartService;
    protected CartMergeService $cartMergeService;

    public function __construct(CartService $cartService, CartMergeService $cartMergeService)
    {
        $this->cartService = $cartService;
        $this->cartMergeService = $cartMergeService;
    }

    /**
     * Resolve active cart from auth user or guest session ID.
     */
    protected function resolveCart(Request $request): Cart
    {
        $userId = auth('sanctum')->id();
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id') ?? 'GUEST-' . session()->getId();

        return $this->cartService->getOrCreateCart($userId, $sessionId);
    }

    /**
     * Fetch current cart.
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
        ], 200);
    }

    /**
     * Add item to cart.
     */
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        try {
            $cart = $this->resolveCart($request);
            $validated = $request->validated();

            $this->cartService->addItem($cart, $validated['product_id'], $validated['quantity'] ?? 1);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart.',
                'data' => new CartResource($cart->fresh(['items.product.primaryImage', 'items.product.category', 'items.product.brand'])),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update item quantity in cart.
     */
    public function updateItem(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        try {
            $cart = $this->resolveCart($request);
            $validated = $request->validated();

            $this->cartService->updateItem($cart, $id, $validated['quantity']);

            return response()->json([
                'success' => true,
                'message' => 'Cart updated.',
                'data' => new CartResource($cart->fresh(['items.product.primaryImage', 'items.product.category', 'items.product.brand'])),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $this->cartService->removeItem($cart, $id);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'data' => new CartResource($cart->fresh(['items.product.primaryImage', 'items.product.category', 'items.product.brand'])),
        ], 200);
    }

    /**
     * Clear all items from cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $this->cartService->clearCart($cart);

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared.',
            'data' => new CartResource($cart->fresh(['items'])),
        ], 200);
    }

    /**
     * Merge guest cart into user cart.
     */
    public function merge(MergeCartRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userCart = $this->cartMergeService->mergeGuestCartToUser($validated['session_id'], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Guest cart merged successfully.',
            'data' => new CartResource($userCart),
        ], 200);
    }

    /**
     * Display abandoned carts report (Admin only).
     */
    public function abandonedCarts(): JsonResponse
    {
        $abandoned = Cart::where('status', 'active')
            ->where('updated_at', '<', now()->subHours(24))
            ->has('items')
            ->with(['user', 'items.product'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => CartResource::collection($abandoned),
        ], 200);
    }

    /**
     * Fetch saved for later items (Feature 15).
     */
    public function getSavedForLater(Request $request): JsonResponse
    {
        $userId = auth('sanctum')->id();
        if (!$userId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
            ], 200);
        }

        $savedItems = SavedCartItem::where('user_id', $userId)
            ->with(['product.primaryImage', 'product.brand', 'product.category'])
            ->latest('saved_at')
            ->get();

        $data = $savedItems->map(function ($item) {
            $product = $item->product;
            if (!$product) {
                return null;
            }

            $currentPrice = (float) ($product->sale_price ?? $product->price);
            $savedPrice = (float) $item->price_snapshot;
            $priceChanged = abs($currentPrice - $savedPrice) > 0.01;

            return [
                'id' => $item->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->primaryImage?->url ?? $product->main_image ?? '/images/placeholder.png',
                'quantity' => $item->quantity,
                'current_price' => $currentPrice,
                'saved_price' => $savedPrice,
                'price_changed' => $priceChanged,
                'price_difference' => round($currentPrice - $savedPrice, 2),
                'in_stock' => $product->stock_quantity > 0,
                'stock_quantity' => $product->stock_quantity,
                'is_active' => $product->status === 'approved',
                'saved_at' => $item->saved_at,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Move an active cart item to Saved for Later (Feature 15).
     */
    public function saveForLater(Request $request, int $itemId): JsonResponse
    {
        $userId = auth('sanctum')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to save items for later.',
            ], 401);
        }

        $cart = $this->resolveCart($request);
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $itemId)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found.',
            ], 404);
        }

        $product = Product::find($cartItem->product_id);
        $livePrice = $product ? (float) ($product->sale_price ?? $product->price) : (float) $cartItem->unit_price;

        SavedCartItem::updateOrCreate(
            [
                'user_id' => $userId,
                'product_id' => $cartItem->product_id,
            ],
            [
                'quantity' => $cartItem->quantity,
                'price_snapshot' => $livePrice,
                'saved_at' => now(),
            ]
        );

        $this->cartService->removeItem($cart, $itemId);

        return response()->json([
            'success' => true,
            'message' => 'Item moved to Saved for Later.',
            'data' => new CartResource($cart->fresh(['items.product.primaryImage', 'items.product.category', 'items.product.brand'])),
        ], 200);
    }

    /**
     * Move saved item back into active Cart with live revalidation (Feature 15).
     */
    public function moveToCart(Request $request, int $savedId): JsonResponse
    {
        $userId = auth('sanctum')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to manage your cart.',
            ], 401);
        }

        $savedItem = SavedCartItem::where('id', $savedId)
            ->where('user_id', $userId)
            ->with('product')
            ->first();

        if (!$savedItem || !$savedItem->product) {
            return response()->json([
                'success' => false,
                'message' => 'Saved item not found.',
            ], 404);
        }

        $product = $savedItem->product;

        // Revalidate active status
        if ($product->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'This product is no longer available.',
            ], 422);
        }

        // Revalidate stock
        if ($product->stock_quantity <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This product is currently out of stock.',
            ], 422);
        }

        $cart = $this->resolveCart($request);
        $livePrice = (float) ($product->sale_price ?? $product->price);
        $qtyToAdd = min($savedItem->quantity, $product->stock_quantity);

        $this->cartService->addItem($cart, $product, $qtyToAdd, $livePrice);
        $savedItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item moved to your active cart.',
            'data' => new CartResource($cart->fresh(['items.product.primaryImage', 'items.product.category', 'items.product.brand'])),
        ], 200);
    }

    /**
     * Remove item from Saved for Later (Feature 15).
     */
    public function removeSavedItem(Request $request, int $savedId): JsonResponse
    {
        $userId = auth('sanctum')->id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to manage saved items.',
            ], 401);
        }

        $savedItem = SavedCartItem::where('id', $savedId)
            ->where('user_id', $userId)
            ->first();

        if ($savedItem) {
            $savedItem->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Item removed from Saved for Later.',
        ], 200);
    }
}
