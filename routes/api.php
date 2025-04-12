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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/products', [AdminController::class, 'getProducts']);
        Route::post('/admin/products', [AdminController::class, 'createProduct']);
        Route::put('/admin/products/{product}', [AdminController::class, 'updateProduct']);
        Route::delete('/admin/products/{product}', [AdminController::class, 'deleteProduct']);

        Route::get('/admin/orders', [AdminController::class, 'getOrders']);
        Route::put('/admin/orders/{order}/status', [AdminController::class, 'updateOrderStatus']);
        Route::get('/admin/orders/statistics', [AdminController::class, 'getOrderStatistics']);
    });

    // Customer routes
    Route::middleware('role:customer')->group(function () {
        Route::get('/products', [CustomerController::class, 'getProducts']);
        Route::get('/products/{product}', [CustomerController::class, 'getProduct']);
        Route::get('/orders', [CustomerController::class, 'getOrders']);
        Route::get('/orders/{order}', [CustomerController::class, 'getOrder']);
        Route::post('/orders', [CustomerController::class, 'createOrder']);
        Route::post('/orders/{order}/cancel', [CustomerController::class, 'cancelOrder']);
    });
}); 