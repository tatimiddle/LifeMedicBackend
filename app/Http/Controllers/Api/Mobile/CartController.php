<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Services\CartService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    /**
     * Devuelve el carrito activo del usuario autenticado.
     */
    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->activeCartFor($request->user());

        return ApiResponse::success(
            $this->cartService->formatCart($cart),
            'Carrito obtenido correctamente.'
        );
    }

    /**
     * Agrega un medicamento o producto al carrito activo validando stock.
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medicine_id' => ['required_without:codigo', 'nullable', 'integer', 'exists:medicines,id'],
            'codigo' => ['required_without:medicine_id', 'nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $result = $this->cartService->addItem($request->user(), $validated);

        return ApiResponse::created($result['cart'], $result['message']);
    }

    /**
     * Actualiza la cantidad de un ítem del carrito activo.
     */
    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $result = $this->cartService->updateItem(
            $request->user(),
            $cartItem,
            (int) $validated['quantity']
        );

        return ApiResponse::success($result['cart'], $result['message']);
    }

    /**
     * Elimina un ítem del carrito activo del usuario.
     */
    public function remove(Request $request, CartItem $cartItem): JsonResponse
    {
        $result = $this->cartService->removeItem($request->user(), $cartItem);

        return ApiResponse::success($result['cart'], $result['message']);
    }
}
