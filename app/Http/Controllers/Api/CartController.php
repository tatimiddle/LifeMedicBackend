<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->activeCartFor($request->user());

        return response()->json([
            'data' => $this->cartService->formatCart($cart),
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medicine_id' => ['required_without:codigo', 'nullable', 'integer', 'exists:medicines,id'],
            'codigo' => ['required_without:medicine_id', 'nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $result = $this->cartService->addItem($request->user(), $validated);

        return response()->json([
            'message' => 'Producto agregado al carrito correctamente.',
            'data' => $result['cart'],
        ], 201);
    }

    public function update(Request $request, CartItem $item): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $result = $this->cartService->updateItem(
            $request->user(),
            $item,
            (int) $validated['quantity']
        );

        return response()->json([
            'message' => $result['message'],
            'data' => $result['cart'],
        ]);
    }

    public function remove(Request $request, CartItem $item): JsonResponse
    {
        $result = $this->cartService->removeItem($request->user(), $item);

        return response()->json([
            'message' => 'Producto eliminado del carrito correctamente.',
            'data' => $result['cart'],
        ]);
    }
}
