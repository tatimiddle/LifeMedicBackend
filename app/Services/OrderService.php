<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Medicine;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Support\CatalogDataLoader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Registra un pedido desde el carrito activo o ítems explícitos dentro de DB::transaction.
     *
     * @param  array{
     *     delivery_method?: string|null,
     *     delivery_address?: string|null,
     *     shipping_cost?: float|int|string|null,
     *     items?: array<int, array{medicine_id: int|string, quantity: int|string}>|null
     * }  $validated
     */
    public function checkout(User $user, array $validated): Order
    {
        return DB::transaction(function () use ($user, $validated): Order {
            $activeCart = Cart::query()
                ->with('items')
                ->where('user_id', $user->id)
                ->where('status', Cart::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            $usingCart = empty($validated['items']);
            $groupedQuantities = [];

            if ($usingCart) {
                if ($activeCart === null || $activeCart->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => ['Tu carrito está vacío. Agrega productos antes de confirmar el pedido.'],
                    ]);
                }

                foreach ($activeCart->items as $cartItem) {
                    $medicineId = (int) $cartItem->medicine_id;
                    $groupedQuantities[$medicineId] = ($groupedQuantities[$medicineId] ?? 0) + (int) $cartItem->quantity;
                }
            } else {
                foreach ($validated['items'] as $entry) {
                    $medicineId = (int) $entry['medicine_id'];
                    $groupedQuantities[$medicineId] = ($groupedQuantities[$medicineId] ?? 0) + (int) $entry['quantity'];
                }
            }

            $medicineIds = array_keys($groupedQuantities);
            sort($medicineIds);

            $medicines = Medicine::query()
                ->whereIn('id', $medicineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0.0;
            $preparedDetails = [];

            foreach ($groupedQuantities as $medicineId => $qty) {
                $medicine = $medicines->get($medicineId);

                if (! $medicine || ! $medicine->is_active) {
                    throw ValidationException::withMessages([
                        'items' => ['Uno de los productos solicitados no se encuentra disponible.'],
                    ]);
                }

                if ((int) $medicine->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuficiente para {$medicine->name}. Disponible: {$medicine->stock}."],
                    ]);
                }

                $unitPrice = round((float) $medicine->price, 2);
                $lineTotal = round($unitPrice * $qty, 2);
                $subtotal += $lineTotal;

                $preparedDetails[] = [
                    'medicine' => $medicine,
                    'quantity' => $qty,
                    'codigo' => $medicine->codigo,
                    'name' => $medicine->name,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $rawMethod = mb_strtolower(trim((string) ($validated['delivery_method'] ?? 'Domicilio')));
            $deliveryMethod = in_array($rawMethod, ['sucursal', 'pickup'], true) ? 'Sucursal' : 'Domicilio';
            $shippingCost = array_key_exists('shipping_cost', $validated) && $validated['shipping_cost'] !== null
                ? round((float) $validated['shipping_cost'], 2)
                : 0.0;
            $total = round($subtotal + $shippingCost, 2);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => $this->generateUniqueOrderNumber(),
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'phone' => $user->phone,
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $validated['delivery_address'] ?? $user->address,
                'free_shipping' => $shippingCost === 0.0,
                'shipping_cost' => $shippingCost,
                'subtotal' => round($subtotal, 2),
                'total' => $total,
                'status' => Order::STATUS_PENDING,
                'placed_at' => now(),
                'status_updated_at' => now(),
            ]);

            foreach ($preparedDetails as $detail) {
                OrderDetail::query()->create([
                    'order_id' => $order->id,
                    'medicine_id' => $detail['medicine']->id,
                    'codigo' => $detail['codigo'],
                    'name' => $detail['name'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $detail['unit_price'],
                    'line_total' => $detail['line_total'],
                ]);

                $detail['medicine']->decrement('stock', $detail['quantity']);
            }

            if ($usingCart && $activeCart !== null) {
                $activeCart->items()->delete();
                $activeCart->update(['status' => Cart::STATUS_CHECKED_OUT]);
            }

            return $order->load(['details.medicine', 'user']);
        });
    }

    /**
     * Actualiza el estado de un pedido ajustando inventario si se cancela o reactiva.
     */
    public function updateStatus(Order $order, string $rawStatus, ?string $adminNote = null): Order
    {
        $targetStatus = Order::normalizeStatus($rawStatus);

        if ($targetStatus === null) {
            throw ValidationException::withMessages([
                'status' => ['El estado seleccionado para el pedido no es válido.'],
            ]);
        }

        return DB::transaction(function () use ($order, $targetStatus, $adminNote): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->with('details')
                ->lockForUpdate()
                ->firstOrFail();

            $previousStatus = $lockedOrder->status;

            if ($previousStatus !== Order::STATUS_CANCELLED && $targetStatus === Order::STATUS_CANCELLED) {
                $this->adjustStockForOrder($lockedOrder, restore: true);
            } elseif ($previousStatus === Order::STATUS_CANCELLED && $targetStatus !== Order::STATUS_CANCELLED) {
                $this->adjustStockForOrder($lockedOrder, restore: false);
            }

            $attributes = [
                'status' => $targetStatus,
                'status_updated_at' => now(),
            ];

            if ($adminNote !== null) {
                $trimmedNote = trim($adminNote);
                $attributes['admin_note'] = $trimmedNote !== '' ? $trimmedNote : null;
            }

            $lockedOrder->update($attributes);

            return $lockedOrder->fresh(['details.medicine', 'user']);
        });
    }

    /**
     * Serializa un pedido para respuestas de la API móvil.
     *
     * @return array<string, mixed>
     */
    public function formatOrder(Order $order): array
    {
        $order->loadMissing(['details.medicine']);

        $itemsCount = 0;

        $items = $order->details->map(function (OrderDetail $detail) use (&$itemsCount): array {
            $quantity = (int) $detail->quantity;
            $unitPrice = round((float) $detail->unit_price, 2);
            $lineTotal = round((float) ($detail->line_total ?? ($unitPrice * $quantity)), 2);
            $itemsCount += $quantity;

            $medicine = $detail->medicine;

            return [
                'id' => $detail->id,
                'medicine_id' => $detail->medicine_id,
                'codigo' => $detail->codigo ?? $medicine?->codigo,
                'name' => $detail->name ?? $medicine?->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'image_url' => $medicine !== null
                    ? CatalogDataLoader::formatImageUrl($medicine->image_url ?: $medicine->image_path)
                    : null,
            ];
        })->values()->all();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'delivery_method' => $order->delivery_method,
            'delivery_method_label' => $order->deliveryMethodLabel(),
            'delivery_address' => $order->delivery_address,
            'free_shipping' => (bool) $order->free_shipping,
            'shipping_cost' => round((float) $order->shipping_cost, 2),
            'subtotal' => round((float) $order->subtotal, 2),
            'total' => round((float) $order->total, 2),
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'phone' => $order->phone,
            'admin_note' => $order->admin_note,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'status_updated_at' => $order->status_updated_at?->toIso8601String(),
            'items_count' => $itemsCount,
            'items' => $items,
        ];
    }

    /**
     * Ajusta el inventario de los medicamentos asociados al pedido dentro de una transacción.
     */
    private function adjustStockForOrder(Order $order, bool $restore): void
    {
        $medicineIds = $order->details
            ->pluck('medicine_id')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($medicineIds === []) {
            return;
        }

        $medicines = Medicine::query()
            ->whereIn('id', $medicineIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($order->details as $detail) {
            $medicine = $medicines->get($detail->medicine_id);

            if ($medicine === null) {
                continue;
            }

            $quantity = (int) $detail->quantity;

            if ($restore) {
                $medicine->increment('stock', $quantity);

                continue;
            }

            if ((int) $medicine->stock < $quantity) {
                throw ValidationException::withMessages([
                    'status' => ["Stock insuficiente para reactivar {$medicine->name}. Disponible: {$medicine->stock}."],
                ]);
            }

            $medicine->decrement('stock', $quantity);
        }
    }

    private function generateUniqueOrderNumber(): string
    {
        do {
            $candidate = 'LM-'.date('Y').'-'.strtoupper(Str::random(6));
        } while (Order::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
