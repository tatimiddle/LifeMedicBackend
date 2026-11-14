<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Medicine;
use App\Models\User;
use App\Support\CatalogDataLoader;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Obtiene o crea el carrito activo del usuario autenticado.
     */
    public function activeCartFor(User $user): Cart
    {
        return Cart::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => Cart::STATUS_ACTIVE,
            ],
            [
                'status' => Cart::STATUS_ACTIVE,
            ]
        );
    }

    /**
     * Agrega o incrementa un medicamento dentro del carrito activo validando stock.
     *
     * @param  array{medicine_id?: int|null, codigo?: string|null, quantity: int}  $validated
     * @return array{cart: array<string, mixed>, message: string}
     */
    public function addItem(User $user, array $validated): array
    {
        $medicine = ! empty($validated['medicine_id'])
            ? Medicine::query()->find($validated['medicine_id'])
            : Medicine::query()->whereRaw('LOWER(codigo) = ?', [mb_strtolower(trim((string) ($validated['codigo'] ?? '')))])->first();

        if (! $medicine || ! $medicine->is_active) {
            throw ValidationException::withMessages([
                'medicine_id' => ['El medicamento seleccionado no se encuentra disponible.'],
            ]);
        }

        $cart = $this->activeCartFor($user);
        $existingItem = $cart->items()->where('medicine_id', $medicine->id)->first();
        $newQuantity = (int) $validated['quantity'] + (int) ($existingItem?->quantity ?? 0);

        if ($newQuantity > (int) $medicine->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Stock insuficiente para {$medicine->name}. Disponible: {$medicine->stock}."],
            ]);
        }

        if ($existingItem !== null) {
            $existingItem->update([
                'quantity' => $newQuantity,
                'unit_price' => (float) $medicine->price,
            ]);
        } else {
            $cart->items()->create([
                'medicine_id' => $medicine->id,
                'quantity' => (int) $validated['quantity'],
                'unit_price' => (float) $medicine->price,
            ]);
        }

        return [
            'message' => 'Producto agregado al carrito.',
            'cart' => $this->formatCart($cart->fresh()),
        ];
    }

    /**
     * Actualiza la cantidad de un ítem del carrito activo o lo elimina si es 0.
     *
     * @return array{cart: array<string, mixed>, message: string}
     */
    public function updateItem(User $user, CartItem $item, int $quantity): array
    {
        $cart = $item->cart;

        abort_unless(
            $cart !== null
            && (int) $cart->user_id === (int) $user->id
            && $cart->status === Cart::STATUS_ACTIVE,
            404
        );

        if ($quantity === 0) {
            $item->delete();

            return [
                'message' => 'Producto eliminado del carrito.',
                'cart' => $this->formatCart($cart->fresh()),
            ];
        }

        $medicine = $item->medicine;

        if (! $medicine || ! $medicine->is_active) {
            throw ValidationException::withMessages([
                'medicine_id' => ['El producto ya no está disponible.'],
            ]);
        }

        if ($quantity > (int) $medicine->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Stock insuficiente para {$medicine->name}. Disponible: {$medicine->stock}."],
            ]);
        }

        $item->update([
            'quantity' => $quantity,
            'unit_price' => (float) $medicine->price,
        ]);

        return [
            'message' => 'Cantidad actualizada correctamente.',
            'cart' => $this->formatCart($cart->fresh()),
        ];
    }

    /**
     * Elimina un ítem perteneciente al carrito activo del usuario.
     *
     * @return array{cart: array<string, mixed>, message: string}
     */
    public function removeItem(User $user, CartItem $item): array
    {
        $cart = $item->cart;

        abort_unless(
            $cart !== null
            && (int) $cart->user_id === (int) $user->id
            && $cart->status === Cart::STATUS_ACTIVE,
            404
        );

        $item->delete();

        return [
            'message' => 'Producto eliminado del carrito.',
            'cart' => $this->formatCart($cart->fresh()),
        ];
    }

    /**
     * Serializa el carrito activo y sus subtotales.
     *
     * @return array<string, mixed>
     */
    public function formatCart(Cart $cart): array
    {
        $cart->load(['items.medicine.category']);

        $subtotal = 0.0;
        $totalUnits = 0;

        $items = $cart->items->map(function (CartItem $item) use (&$subtotal, &$totalUnits): array {
            $medicine = $item->medicine;
            $unitPrice = round((float) ($medicine?->price ?? $item->unit_price), 2);
            $lineTotal = round($unitPrice * (int) $item->quantity, 2);
            $subtotal += $lineTotal;
            $totalUnits += (int) $item->quantity;

            return [
                'id' => $item->id,
                'medicine_id' => $item->medicine_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'medicine' => $medicine !== null ? [
                    'id' => $medicine->id,
                    'codigo' => $medicine->codigo,
                    'name' => $medicine->name,
                    'brand' => $medicine->brand,
                    'price' => (float) $medicine->price,
                    'stock' => (int) $medicine->stock,
                    'is_active' => (bool) $medicine->is_active,
                    'image_url' => CatalogDataLoader::formatImageUrl($medicine->image_url ?: $medicine->image_path),
                ] : null,
            ];
        })->values()->all();

        return [
            'id' => $cart->id,
            'status' => $cart->status,
            'items_count' => $totalUnits,
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal, 2),
            'items' => $items,
        ];
    }
}
