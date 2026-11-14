<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.store');
Route::post('/logout', [WebAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/ventas', SaleController::class)->name('ventas.index');
    Route::get('/productos', [ProductController::class, 'index'])->name('productos.index');
    Route::post('/productos', [ProductController::class, 'store'])->name('productos.store');
    Route::patch('/productos/{codigo}', [ProductController::class, 'update'])->name('productos.update');
    Route::patch('/productos/{codigo}/inventario', [ProductController::class, 'updateInventory'])->name('productos.updateInventory');
    Route::patch('/productos/{codigo}/baja', [ProductController::class, 'toggleStatus'])->name('productos.toggleStatus');
    Route::delete('/productos/{codigo}', [ProductController::class, 'destroy'])->name('productos.destroy');
    Route::get('/clientes', CustomerController::class)->name('clientes.index');

    Route::get('/categorias', [CategoryController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoryController::class, 'store'])->name('categorias.store');
    Route::patch('/categorias/{slug}', [CategoryController::class, 'update'])->name('categorias.update');
    Route::patch('/categorias/{slug}/estado', [CategoryController::class, 'toggleStatus'])->name('categorias.toggleStatus');
    Route::delete('/categorias/{slug}', [CategoryController::class, 'destroy'])->name('categorias.destroy');

    Route::get('/medicamentos', [MedicineController::class, 'index'])->name('medicamentos.index');
    Route::post('/medicamentos', [MedicineController::class, 'store'])->name('medicamentos.store');
    Route::patch('/medicamentos/{codigo}', [MedicineController::class, 'update'])->name('medicamentos.update');
    Route::patch('/medicamentos/{codigo}/inventario', [MedicineController::class, 'updateInventory'])->name('medicamentos.updateInventory');
    Route::patch('/medicamentos/{codigo}/baja', [MedicineController::class, 'toggleStatus'])->name('medicamentos.toggleStatus');
    Route::delete('/medicamentos/{codigo}', [MedicineController::class, 'destroy'])->name('medicamentos.destroy');

    Route::get('/facturas', InvoiceController::class)->name('facturas.index');

    Route::get('/pedidos', [OrderController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('pedidos.show');
    Route::patch('/pedidos/{order}/estado', [OrderController::class, 'updateStatus'])->name('pedidos.status');

    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::match(['put', 'patch'], '/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::patch('/usuarios/{user}/rol', [UserController::class, 'updateRole'])->name('usuarios.role');
    Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])->name('usuarios.status');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    Route::get('/plan-pro', PlanController::class)->name('plan-pro.index');
});
