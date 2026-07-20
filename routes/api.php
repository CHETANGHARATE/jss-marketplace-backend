<?php

use App\Http\Controllers\Api\V1\AttributeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SettingController;
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

    // Protected Admin Operations (Modules 1, 2, 3)
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
    });
});
