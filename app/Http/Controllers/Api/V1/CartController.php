<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\MergeCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
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
}
