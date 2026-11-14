<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['details.medicine'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'No autorizado para consultar este pedido.'], 403);
        }

        return response()->json([
            'data' => $order->load(['details.medicine']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_method' => ['nullable', 'string', 'in:Domicilio,Sucursal,domicilio,sucursal,pickup,delivery'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.medicine_id' => ['required_with:items', 'integer', 'exists:medicines,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:999'],
        ]);

        $order = $this->orderService->checkout($request->user(), $validated);

        return response()->json([
            'message' => 'Pedido creado correctamente.',
            'data' => $order->load(['details.medicine']),
        ], 201);
    }
}
