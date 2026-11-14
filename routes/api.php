<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\MedicineController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/medicines', [CatalogController::class, 'medicines']);
    Route::get('/medicines/{medicine}', [CatalogController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'add']);
        Route::patch('/cart/items/{item}', [CartController::class, 'update']);
        Route::delete('/cart/items/{item}', [CartController::class, 'remove']);

        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::post('/medicines', [MedicineController::class, 'store']);
            Route::put('/medicines/{medicine}', [MedicineController::class, 'update']);
            Route::patch('/medicines/{medicine}', [MedicineController::class, 'update']);
            Route::delete('/medicines/{medicine}', [MedicineController::class, 'destroy']);
        });
    });
});