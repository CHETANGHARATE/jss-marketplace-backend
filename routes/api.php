<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminShippingController;
use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Versioned REST API endpoints for JSS Solutions Multi Vendor Marketplace.
|
*/

Route::prefix('v1')->group(function () {
    
    // Authentication Endpoints (Rate Limited to 6 attempts/minute)
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        });

        // Authenticated User Endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'profile']);
            Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationNotification'])
                ->middleware('throttle:6,1');
        });
    });

    // Public System Settings
    Route::get('/settings', [SettingController::class, 'index']);

    // Public Catalog Foundation Endpoints (Module 2)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);

    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brands/{slug}', [BrandController::class, 'show']);

    Route::get('/attributes', [AttributeController::class, 'index']);
    Route::get('/attributes/{id}', [AttributeController::class, 'show']);

    // Public Product Management Engine Endpoints (Module 3)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/trending', [ProductController::class, 'trending']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    // Public Warehouse Endpoints (Module 4)
    Route::get('/warehouses', [WarehouseController::class, 'index']);
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);

    // Public / Guest Shopping Cart Endpoints (Module 5)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/items/{id}', [CartController::class, 'removeItem']);
        Route::post('/clear', [CartController::class, 'clear']);
        
        // Merge guest cart on login
        Route::middleware('auth:sanctum')->post('/merge', [CartController::class, 'merge']);
    });

    // Public Gateway Webhook Listener (Module 7)
    Route::post('/payments/webhook/{gateway}', [PaymentController::class, 'webhook']);

    // Public Shipping Rate Calculation & AWB Tracking (Module 8)
    Route::post('/shipping/calculate', [ShippingController::class, 'calculate']);
    Route::get('/shipments/track/{trackingNumber}', [ShippingController::class, 'track']);

    // Protected Customer Operations (Modules 5, 6, 7, 8)
    Route::middleware('auth:sanctum')->group(function () {
        // Customer Addresses
        Route::prefix('addresses')->group(function () {
            Route::get('/', [AddressController::class, 'index']);
            Route::post('/', [AddressController::class, 'store']);
            Route::delete('/{id}', [AddressController::class, 'destroy']);
        });

        // Customer Checkout & Orders Engine
        Route::post('/checkout/process', [OrderController::class, 'checkout']);
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/{orderNumber}', [OrderController::class, 'show']);
            Route::post('/{orderNumber}/cancel', [OrderController::class, 'cancel']);
            Route::get('/{orderNumber}/shipment', [ShippingController::class, 'orderShipment']);
        });

        // Customer Payments Engine (Module 7)
        Route::prefix('payments')->group(function () {
            Route::post('/initiate', [PaymentController::class, 'initiate']);
            Route::post('/verify', [PaymentController::class, 'verify']);
            Route::get('/{paymentNumber}', [PaymentController::class, 'show']);
        });

        // Customer Wishlist Endpoints
        Route::prefix('wishlist')->group(function () {
            Route::get('/', [WishlistController::class, 'index']);
            Route::post('/toggle', [WishlistController::class, 'toggle']);
            Route::delete('/{productId}', [WishlistController::class, 'destroy']);
        });
    });

    // Protected Admin Operations (Modules 1-8)
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        // System Settings Admin
        Route::put('/settings', [SettingController::class, 'update']);

        // Category Management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Brand Management
        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{id}', [BrandController::class, 'update']);
        Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

        // Attribute Management
        Route::post('/attributes', [AttributeController::class, 'store']);
        Route::delete('/attributes/{id}', [AttributeController::class, 'destroy']);

        // Media Upload
        Route::post('/media/upload', [MediaController::class, 'upload']);

        // Product Engine Admin Management
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::patch('/products/{id}/status', [ProductController::class, 'updateStatus']);

        // Warehouse Management (Module 4)
        Route::post('/warehouses', [WarehouseController::class, 'store']);
        Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy']);

        // Inventory Management Engine (Module 4)
        Route::get('/inventories', [InventoryController::class, 'index']);
        Route::post('/inventories/add-stock', [InventoryController::class, 'addStock']);
        Route::post('/inventories/adjust-stock', [InventoryController::class, 'adjustStock']);
        Route::post('/inventories/transfer', [InventoryController::class, 'transfer']);
        Route::get('/inventories/low-stock', [InventoryController::class, 'lowStockReport']);
        Route::get('/stock-movements', [InventoryController::class, 'movements']);

        // Abandoned Carts Report (Module 5)
        Route::get('/carts/abandoned', [CartController::class, 'abandonedCarts']);

        // Admin Order Management (Module 6)
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
        Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);

        // Admin Payment & Refund Management (Module 7)
        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::get('/payments/logs', [AdminPaymentController::class, 'logs']);
        Route::post('/payments/refund', [AdminPaymentController::class, 'refund']);

        // Admin Shipping & Logistics Management (Module 8)
        Route::get('/shipping-zones', [AdminShippingController::class, 'zones']);
        Route::post('/shipping-zones', [AdminShippingController::class, 'storeZone']);
        Route::get('/couriers', [AdminShippingController::class, 'couriers']);
        Route::post('/couriers', [AdminShippingController::class, 'storeCourier']);
        Route::get('/shipments', [AdminShippingController::class, 'shipments']);
        Route::post('/shipments/create', [AdminShippingController::class, 'createShipment']);
        Route::patch('/shipments/{id}/status', [AdminShippingController::class, 'updateStatus']);
    });
});
