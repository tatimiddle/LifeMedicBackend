<?php

namespace Tests\Feature\Api;

use App\Models\Medicine;
use App\Models\Order;
use App\Models\Role;
use App\Services\OrderService;
use App\Services\UserManagementService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCartAndOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_cart_and_order_endpoints_require_authentication(): void
    {
        $this->getJson('/api/cart')->assertStatus(401)->assertJsonPath('success', false);
        $this->postJson('/api/cart/items', ['medicine_id' => 1, 'quantity' => 1])->assertStatus(401);
        $this->getJson('/api/orders')->assertStatus(401);
        $this->postJson('/api/orders', [])->assertStatus(401);
    }

    public function test_mobile_cart_crud_and_checkout_from_cart_decrement_stock_and_clear_cart(): void
    {
        $this->seed(DatabaseSeeder::class);

        $medicineA = Medicine::query()->where('codigo', 'MED-00101')->firstOrFail();
        $medicineB = Medicine::query()->where('stock', '>=', 5)->whereKeyNot($medicineA->id)->firstOrFail();
        $initialStockA = (int) $medicineA->stock;
        $initialStockB = (int) $medicineB->stock;

        $buyer = app(UserManagementService::class)->createUser([
            'name' => 'Valeria Móvil',
            'email' => 'valeria.movil@lifemedic.com',
            'phone' => '+52 55 3344 5566',
            'address' => 'Av. Paseo de la Reforma 120',
            'password' => 'Secret123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $token = $buyer->createToken('mobile-test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->postJson('/api/cart/items', [
                'medicine_id' => $medicineA->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items_count', 2)
            ->assertJsonCount(1, 'data.items');

        $addSecond = $this->withHeaders($headers)
            ->postJson('/api/cart/items', [
                'medicine_id' => $medicineB->id,
                'quantity' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items_count', 5);

        $itemId = $addSecond->json('data.items.0.id');

        $this->withHeaders($headers)
            ->patchJson("/api/cart/items/{$itemId}", ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items_count', 4);

        $orderResponse = $this->withHeaders($headers)
            ->postJson('/api/orders', [
                'delivery_method' => 'Domicilio',
                'delivery_address' => 'Calle Durango 45, Roma Norte',
                'shipping_cost' => 45,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Order::STATUS_PENDING)
            ->assertJsonPath('data.status_label', 'Pendiente')
            ->assertJsonPath('data.delivery_method', 'Domicilio')
            ->assertJsonCount(2, 'data.items');

        $orderId = $orderResponse->json('data.id');
        $firstItemQty = $medicineA->id === $addSecond->json('data.items.0.medicine_id') ? 1 : 2;
        $secondItemQty = $medicineB->id === $addSecond->json('data.items.0.medicine_id') ? 1 : 3;

        $this->assertSame($initialStockA - $firstItemQty, (int) $medicineA->fresh()->stock);
        $this->assertSame($initialStockB - $secondItemQty, (int) $medicineB->fresh()->stock);

        $this->withHeaders($headers)
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonCount(0, 'data.items');

        $this->withHeaders($headers)
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $orderId)
            ->assertJsonPath('data.0.status_label', 'Pendiente');

        $this->withHeaders($headers)
            ->getJson("/api/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_mobile_checkout_rolls_back_transaction_when_any_item_lacks_stock(): void
    {
        $this->seed(DatabaseSeeder::class);

        $enoughStock = Medicine::query()->where('stock', '>=', 5)->firstOrFail();
        $lowStock = Medicine::query()->where('stock', '>=', 1)->whereKeyNot($enoughStock->id)->firstOrFail();
        $lowStock->update(['stock' => 2]);

        $initialEnough = (int) $enoughStock->stock;

        $buyer = app(UserManagementService::class)->createUser([
            'name' => 'Cliente Rollback',
            'email' => 'rollback@lifemedic.com',
            'password' => 'Secret123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $token = $buyer->createToken('mobile-test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/orders', [
                'delivery_method' => 'Sucursal',
                'items' => [
                    ['medicine_id' => $enoughStock->id, 'quantity' => 2],
                    ['medicine_id' => $lowStock->id, 'quantity' => 99],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame($initialEnough, (int) $enoughStock->fresh()->stock);
        $this->assertSame(2, (int) $lowStock->fresh()->stock);
        $this->assertSame(0, Order::query()->where('user_id', $buyer->id)->count());
    }

    public function test_authenticated_user_cannot_view_another_users_order_and_cancellation_restores_stock(): void
    {
        $this->seed(DatabaseSeeder::class);

        $medicine = Medicine::query()->where('codigo', 'MED-00101')->firstOrFail();
        $initialStock = (int) $medicine->stock;

        $owner = app(UserManagementService::class)->createUser([
            'name' => 'Owner User',
            'email' => 'owner.mobile@lifemedic.com',
            'password' => 'Secret123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $intruder = app(UserManagementService::class)->createUser([
            'name' => 'Intruder User',
            'email' => 'intruder.mobile@lifemedic.com',
            'password' => 'Secret123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $ownerToken = $owner->createToken('owner')->plainTextToken;
        $intruderToken = $intruder->createToken('intruder')->plainTextToken;

        $orderRes = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
            ->postJson('/api/orders', [
                'delivery_method' => 'Sucursal',
                'items' => [
                    ['medicine_id' => $medicine->id, 'quantity' => 3],
                ],
            ])
            ->assertCreated();

        $order = Order::query()->findOrFail($orderRes->json('data.id'));
        $this->assertSame($initialStock - 3, (int) $medicine->fresh()->stock);

        $this->app['auth']->forgetGuards();

        $this->withToken($intruderToken)
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        app(OrderService::class)->updateStatus($order, 'cancelado', 'Cliente canceló');
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame('Cancelado', $order->fresh()->status_label);
        $this->assertSame($initialStock, (int) $medicine->fresh()->stock);
    }
}
