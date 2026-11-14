<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Api\Mobile\CartController as MobileCartController;
use App\Http\Controllers\Api\Mobile\CatalogController as MobileCatalogController;
use App\Http\Controllers\Api\Mobile\OrderController as MobileOrderController;
use App\Http\Controllers\Api\OrderController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| LifeMedic API Routes (/api/v1/* & Mobile REST /api/*)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/medicines', [CatalogController::class, 'medicines']);
    Route::get('/medicines/{medicine}', [CatalogController::class, 'show']);
    Route::get('/products', [CatalogController::class, 'products']);
    Route::get('/products/{identifier}', [CatalogController::class, 'show']);

    Route::middleware(['auth:sanctum', 'active.user'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateMe']);
        Route::patch('/me', [AuthController::class, 'updateMe']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'add']);
        Route::patch('/cart/items/{item}', [CartController::class, 'update']);
        Route::delete('/cart/items/{item}', [CartController::class, 'remove']);

        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        Route::middleware('role:admin')->prefix('admin')->group(function (): void {
            Route::post('/medicines', [MedicineController::class, 'store']);
            Route::put('/medicines/{medicine}', [MedicineController::class, 'update']);
            Route::patch('/medicines/{medicine}', [MedicineController::class, 'update']);
            Route::delete('/medicines/{medicine}', [MedicineController::class, 'destroy']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Mobile REST Endpoints (/api/auth/*, /api/categories, /api/medicines, /api/products)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [MobileAuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [MobileAuthController::class, 'login'])->name('api.auth.login');

    Route::middleware(['auth:sanctum', 'active.user'])->group(function (): void {
        Route::get('/profile', [MobileAuthController::class, 'profile'])->name('api.auth.profile');
        Route::put('/profile', [MobileAuthController::class, 'updateProfile'])->name('api.auth.profile.update');
        Route::post('/logout', [MobileAuthController::class, 'logout'])->name('api.auth.logout');
    });
});

Route::get('/categories', [MobileCatalogController::class, 'categories'])->name('api.categories.index');

Route::get('/medicines', [MobileCatalogController::class, 'index'])->name('api.medicines.index');
Route::get('/medicines/{identifier}', [MobileCatalogController::class, 'show'])->name('api.medicines.show');

Route::get('/products', [MobileCatalogController::class, 'index'])->name('api.products.index');
Route::get('/products/{identifier}', [MobileCatalogController::class, 'show'])->name('api.products.show');

Route::middleware(['auth:sanctum', 'active.user'])->group(function (): void {
    Route::get('/cart', [MobileCartController::class, 'show'])->name('api.cart.show');
    Route::post('/cart/items', [MobileCartController::class, 'add'])->name('api.cart.items.add');
    Route::match(['put', 'patch'], '/cart/items/{cartItem}', [MobileCartController::class, 'update'])->name('api.cart.items.update');
    Route::delete('/cart/items/{cartItem}', [MobileCartController::class, 'remove'])->name('api.cart.items.remove');

    Route::get('/orders', [MobileOrderController::class, 'index'])->name('api.orders.index');
    Route::get('/orders/{order}', [MobileOrderController::class, 'show'])->name('api.orders.show');
    Route::post('/orders', [MobileOrderController::class, 'store'])->name('api.orders.store');
});

Route::fallback(function () {
    return ApiResponse::error(
        'Endpoint API no encontrado.',
        Response::HTTP_NOT_FOUND
    );
});
