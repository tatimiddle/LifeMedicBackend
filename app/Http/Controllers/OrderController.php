<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Lista los pedidos recibidos con filtros por estado, fecha y búsqueda.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $rawStatus = trim((string) $request->query('estado', $request->query('status', '')));
        $statusFilter = Order::normalizeStatus($rawStatus);
        $date = trim((string) $request->query('fecha', $request->query('date', '')));
        $dateFrom = trim((string) $request->query('fecha_desde', ''));
        $dateTo = trim((string) $request->query('fecha_hasta', ''));

        $query = Order::query()
            ->with(['user'])
            ->withCount('details')
            ->withSum('details as units_count', 'quantity');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $like = '%'.$search.'%';
                $builder->where('order_number', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('user', function (Builder $userQuery) use ($like): void {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        $dateColumn = DB::raw('COALESCE(placed_at, created_at)');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $query->whereDate($dateColumn, $date);
        } else {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
                $query->whereDate($dateColumn, '>=', $dateFrom);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
                $query->whereDate($dateColumn, '<=', $dateTo);
            }
        }

        $orders = $query
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $countsByStatus = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'total' => (int) $countsByStatus->sum(),
            'pending' => (int) ($countsByStatus->get(Order::STATUS_PENDING, 0)),
            'preparing' => (int) ($countsByStatus->get(Order::STATUS_PREPARING, 0)),
            'completed' => (int) ($countsByStatus->get(Order::STATUS_COMPLETED, 0)),
            'cancelled' => (int) ($countsByStatus->get(Order::STATUS_CANCELLED, 0)),
            'revenue' => (float) Order::query()
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->sum('total'),
        ];

        return view('modules.pedidos.index', [
            'orders' => $orders,
            'summary' => $summary,
            'statusOptions' => Order::statusOptions(),
            'filters' => [
                'q' => $search,
                'estado' => $statusFilter ?? '',
                'fecha' => $date,
                'fecha_desde' => $dateFrom,
                'fecha_hasta' => $dateTo,
            ],
        ]);
    }

    /**
     * Muestra el detalle completo de un pedido y sus medicamentos solicitados.
     */
    public function show(Order $order): View
    {
        $order->load(['user.role', 'details.medicine']);

        return view('modules.pedidos.show', [
            'order' => $order,
            'statusOptions' => Order::statusOptions(),
        ]);
    }

    /**
     * Actualiza el estado del pedido desde la vista de detalle.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->orderService->updateStatus(
            $order,
            $validated['status'],
            $validated['admin_note'] ?? null
        );

        return redirect()
            ->route('pedidos.show', $updated)
            ->with('status_message', "Pedido {$updated->order_number} actualizado a estado \"{$updated->status_label}\".");
    }
}
