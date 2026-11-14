<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use App\Services\UserManagementService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderAndUserAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_non_admin_users_cannot_access_pedidos_or_usuarios(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/pedidos')->assertRedirect('/login');
        $this->get('/usuarios')->assertRedirect('/login');

        $client = app(UserManagementService::class)->createUser([
            'name' => 'Cliente Web',
            'email' => 'cliente.web@lifemedic.com',
            'password' => 'Secret123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $this->actingAs($client)->get('/pedidos')->assertForbidden();
        $this->actingAs($client)->get('/usuarios')->assertForbidden();
    }

    public function test_admin_can_list_and_filter_orders_by_status_date_and_search(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsAdmin();

        $customerA = app(UserManagementService::class)->createUser([
            'name' => 'Laura Sánchez',
            'email' => 'laura.sanchez@lifemedic.com',
            'phone' => '+52 55 1111 2222',
            'address' => 'Av. Universidad 300',
            'password' => 'Secret123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $pendingOrder = Order::query()->create([
            'user_id' => $customerA->id,
            'order_number' => 'LM-2026-PEND01',
            'customer_name' => $customerA->name,
            'customer_email' => $customerA->email,
            'phone' => $customerA->phone,
            'delivery_method' => 'Domicilio',
            'delivery_address' => $customerA->address,
            'free_shipping' => true,
            'shipping_cost' => 0,
            'subtotal' => 150.00,
            'total' => 150.00,
            'status' => Order::STATUS_PENDING,
            'placed_at' => Carbon::parse('2026-11-10 10:15:00'),
            'status_updated_at' => Carbon::parse('2026-11-10 10:15:00'),
        ]);

        $preparingOrder = Order::query()->create([
            'user_id' => $customerA->id,
            'order_number' => 'LM-2026-PREP02',
            'customer_name' => 'Carlos Méndez',
            'customer_email' => 'carlos.mendez@lifemedic.com',
            'phone' => '+52 55 3333 4444',
            'delivery_method' => 'Sucursal',
            'delivery_address' => null,
            'free_shipping' => true,
            'shipping_cost' => 0,
            'subtotal' => 280.00,
            'total' => 280.00,
            'status' => Order::STATUS_PREPARING,
            'placed_at' => Carbon::parse('2026-11-11 12:30:00'),
            'status_updated_at' => Carbon::parse('2026-11-11 12:30:00'),
        ]);

        $completedOrder = Order::query()->create([
            'user_id' => $customerA->id,
            'order_number' => 'LM-2026-COMP03',
            'customer_name' => 'Mariana Vega',
            'customer_email' => 'mariana.vega@lifemedic.com',
            'phone' => '+52 55 5555 6666',
            'delivery_method' => 'Domicilio',
            'delivery_address' => 'Calle Orizaba 18',
            'free_shipping' => false,
            'shipping_cost' => 45.00,
            'subtotal' => 300.00,
            'total' => 345.00,
            'status' => Order::STATUS_COMPLETED,
            'placed_at' => Carbon::parse('2026-11-12 09:00:00'),
            'status_updated_at' => Carbon::parse('2026-11-12 09:00:00'),
        ]);

        $cancelledOrder = Order::query()->create([
            'user_id' => $customerA->id,
            'order_number' => 'LM-2026-CANC04',
            'customer_name' => 'Jorge Navarro',
            'customer_email' => 'jorge.navarro@lifemedic.com',
            'phone' => '+52 55 7777 8888',
            'delivery_method' => 'Sucursal',
            'delivery_address' => null,
            'free_shipping' => true,
            'shipping_cost' => 0,
            'subtotal' => 90.00,
            'total' => 90.00,
            'status' => Order::STATUS_CANCELLED,
            'placed_at' => Carbon::parse('2026-11-13 15:45:00'),
            'status_updated_at' => Carbon::parse('2026-11-13 15:45:00'),
        ]);

        $this->get('/pedidos')
            ->assertOk()
            ->assertSee($pendingOrder->order_number)
            ->assertSee($preparingOrder->order_number)
            ->assertSee($completedOrder->order_number)
            ->assertSee($cancelledOrder->order_number);

        $this->get('/pedidos?estado=pendiente')
            ->assertOk()
            ->assertSee('LM-2026-PEND01')
            ->assertDontSee('LM-2026-PREP02')
            ->assertDontSee('LM-2026-COMP03')
            ->assertDontSee('LM-2026-CANC04');

        $this->get('/pedidos?estado='.urlencode('en preparación'))
            ->assertOk()
            ->assertSee('LM-2026-PREP02')
            ->assertDontSee('LM-2026-PEND01');

        $this->get('/pedidos?estado=completado')
            ->assertOk()
            ->assertSee('LM-2026-COMP03')
            ->assertDontSee('LM-2026-PEND01');

        $this->get('/pedidos?estado=cancelado')
            ->assertOk()
            ->assertSee('LM-2026-CANC04')
            ->assertDontSee('LM-2026-PEND01');

        $this->get('/pedidos?fecha=2026-11-11')
            ->assertOk()
            ->assertSee('LM-2026-PREP02')
            ->assertDontSee('LM-2026-PEND01')
            ->assertDontSee('LM-2026-COMP03');

        $this->get('/pedidos?q=Mariana')
            ->assertOk()
            ->assertSee('LM-2026-COMP03')
            ->assertDontSee('LM-2026-PEND01');
    }

    public function test_admin_can_view_order_detail_and_update_status_with_stock_integrity(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsAdmin();

        $medicine = Medicine::query()->where('codigo', 'MED-00101')->firstOrFail();
        $initialStock = (int) $medicine->stock;

        $customer = app(UserManagementService::class)->createUser([
            'name' => 'Elena Morales',
            'email' => 'elena.morales@lifemedic.com',
            'phone' => '+52 55 8899 0011',
            'address' => 'Calle Colima 88, Roma Norte',
            'password' => 'Secret123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $order = app(OrderService::class)->checkout($customer, [
            'delivery_method' => 'Domicilio',
            'delivery_address' => 'Calle Colima 88, Roma Norte',
            'shipping_cost' => 35.00,
            'items' => [
                ['medicine_id' => $medicine->id, 'quantity' => 2],
            ],
        ]);

        /** @var OrderDetail $detail */
        $detail = $order->details->firstOrFail();
        $this->assertSame($initialStock - 2, (int) $medicine->fresh()->stock);

        $this->get("/pedidos/{$order->id}")
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Elena Morales')
            ->assertSee('elena.morales@lifemedic.com')
            ->assertSee('Calle Colima 88, Roma Norte')
            ->assertSee($medicine->name)
            ->assertSee($medicine->codigo)
            ->assertSee(number_format((float) $detail->line_total, 2))
            ->assertSee(number_format((float) $order->total, 2));

        $this->patch("/pedidos/{$order->id}/estado", [
            'status' => 'en preparación',
            'admin_note' => 'Surtido en área de despacho.',
        ])->assertRedirect("/pedidos/{$order->id}");

        $this->assertSame(Order::STATUS_PREPARING, $order->fresh()->status);
        $this->assertSame('En preparación', $order->fresh()->status_label);
        $this->assertSame('Surtido en área de despacho.', $order->fresh()->admin_note);

        $this->patch("/pedidos/{$order->id}/estado", [
            'status' => 'cancelado',
            'admin_note' => 'Cancelado por solicitud del cliente.',
        ])->assertRedirect("/pedidos/{$order->id}");

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame($initialStock, (int) $medicine->fresh()->stock);
    }

    public function test_admin_can_list_filter_create_edit_assign_role_toggle_and_delete_users(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->actingAsAdmin();
        $usersService = app(UserManagementService::class);

        $this->get('/usuarios')
            ->assertOk()
            ->assertSee($admin->email);

        $this->post('/usuarios', [
            'name' => 'Dra. Sofía Herrera',
            'email' => 'sofia.herrera@lifemedic.com',
            'phone' => '+52 55 1234 5678',
            'address' => 'Av. Insurgentes Sur 900',
            'password' => 'Farmacia2026!',
            'password_confirmation' => 'Farmacia2026!',
            'role' => Role::PHARMACIST,
            'is_active' => '1',
        ])->assertRedirect('/usuarios');

        $created = User::query()->where('email', 'sofia.herrera@lifemedic.com')->firstOrFail();
        $meta = $usersService->metadataForUser($created->id);
        $this->assertSame(Role::PHARMACIST, $meta['role']);
        $this->assertTrue($meta['is_active']);

        $this->get('/usuarios?rol=farmaceutico')
            ->assertOk()
            ->assertSee('sofia.herrera@lifemedic.com')
            ->assertDontSee($admin->email);

        $this->put("/usuarios/{$created->id}", [
            'name' => 'Dra. Sofía Herrera Ruiz',
            'email' => 'sofia.herrera@lifemedic.com',
            'phone' => '+52 55 9999 0000',
            'address' => 'Sucursal Centro',
            'role' => Role::CLIENT,
            'is_active' => '1',
        ])->assertRedirect('/usuarios');

        $this->assertSame('Dra. Sofía Herrera Ruiz', $created->fresh()->name);
        $this->assertSame(Role::CLIENT, $usersService->metadataForUser($created->id)['role']);

        $this->patch("/usuarios/{$created->id}/rol", [
            'role' => Role::ADMIN,
        ])->assertRedirect('/usuarios');

        $this->assertSame(Role::ADMIN, $usersService->metadataForUser($created->id)['role']);

        $token = $created->createToken('mobile-session')->plainTextToken;

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'sofia.herrera@lifemedic.com')
            ->assertJsonPath('data.user.role', Role::ADMIN)
            ->assertJsonPath('data.user.role_label', 'Administrador');

        $this->withoutHeader('Authorization');
        $this->app['auth']->forgetGuards();
        $this->actingAs($admin);

        $this->patch("/usuarios/{$created->id}/estado", [
            'is_active' => '0',
        ])->assertRedirect('/usuarios');

        $this->assertFalse($usersService->metadataForUser($created->id)['is_active']);

        $this->app['auth']->forgetGuards();

        $this->postJson('/api/auth/login', [
            'email' => 'sofia.herrera@lifemedic.com',
            'password' => 'Farmacia2026!',
        ])->assertStatus(403);

        $this->actingAs($admin);

        $this->delete("/usuarios/{$admin->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->delete("/usuarios/{$created->id}")
            ->assertRedirect('/usuarios');
        $this->assertDatabaseMissing('users', ['id' => $created->id]);
    }
}
