<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Lista el historial de pedidos del usuario autenticado y su estado actual.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with(['details.medicine'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $normalizedStatus = Order::normalizeStatus((string) $request->query('status'));

            if ($normalizedStatus !== null) {
                $query->where('status', $normalizedStatus);
            }
        }

        $orders = $query->get()
            ->map(fn (Order $order): array => $this->orderService->formatOrder($order))
            ->values()
            ->all();

        return ApiResponse::success(
            $orders,
            'Historial de pedidos obtenido correctamente.',
            Response::HTTP_OK,
            ['total' => count($orders)]
        );
    }

    /**
     * Muestra el detalle completo de un pedido del usuario autenticado.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            return ApiResponse::error(
                'No autorizado para consultar este pedido.',
                Response::HTTP_FORBIDDEN
            );
        }

        return ApiResponse::success(
            $this->orderService->formatOrder($order),
            'Detalle del pedido obtenido correctamente.'
        );
    }

    /**
     * Procesa la compra desde el carrito o ítems directos descontando inventario en transacción.
     */
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

        return ApiResponse::created(
            $this->orderService->formatOrder($order),
            'Pedido registrado correctamente.'
        );
    }
}
