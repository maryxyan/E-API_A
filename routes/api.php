<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SwaggerJsonController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/swagger.json', [SwaggerJsonController::class, 'index'])->name('swagger.json');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index']);

// Protected routes
Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin routes
    Route::middleware('can:admin')->prefix('admin')->group(function () {
        Route::get('/products', [AdminController::class, 'getProducts']);
        Route::post('/products', [AdminController::class, 'createProduct']);
        Route::get('/products/{product}', [AdminController::class, 'getProduct']);
        Route::put('/products/{product}', [AdminController::class, 'updateProduct']);
        Route::delete('/products/{product}', [AdminController::class, 'deleteProduct']);

        Route::get('/orders', [AdminController::class, 'getOrders']);
        Route::put('/orders/{order}/status', [AdminController::class, 'updateOrderStatus']);
        Route::get('/orders/statistics', [AdminController::class, 'getOrderStatistics']);
    });

    // Customer routes
    Route::prefix('customer')->group(function () {
        Route::get('/products', [CustomerController::class, 'getProducts']);
        Route::get('/products/{product}', [CustomerController::class, 'getProduct']);
        Route::get('/orders', [CustomerController::class, 'getOrders']);
        Route::get('/orders/{order}', [CustomerController::class, 'getOrder']);
        Route::post('/orders', [CustomerController::class, 'createOrder']);
        Route::post('/orders/{order}/cancel', [CustomerController::class, 'cancelOrder']);
    });
}); 