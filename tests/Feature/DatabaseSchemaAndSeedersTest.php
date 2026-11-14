<?php

namespace Tests\Feature;

use App\Contracts\CatalogRepositoryInterface;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaAndSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_lifemedic_tables_and_columns_exist(): void
    {
        $expectedTables = [
            'roles' => ['id', 'slug', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
            'users' => ['id', 'role_id', 'name', 'email', 'phone', 'address', 'password', 'is_active'],
            'categories' => ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at'],
            'medicines' => ['id', 'category_id', 'codigo', 'catalog_type', 'name', 'formula', 'price', 'stock', 'min_stock', 'is_active'],
            'carts' => ['id', 'user_id', 'status', 'created_at', 'updated_at'],
            'cart_items' => ['id', 'cart_id', 'medicine_id', 'quantity', 'unit_price', 'created_at', 'updated_at'],
            'orders' => ['id', 'user_id', 'order_number', 'customer_name', 'subtotal', 'total', 'status', 'placed_at'],
            'order_details' => ['id', 'order_id', 'medicine_id', 'codigo', 'name', 'quantity', 'unit_price', 'line_total'],
        ];

        foreach ($expectedTables as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Falta la tabla [$table].");
            $this->assertTrue(
                Schema::hasColumns($table, $columns),
                "Faltan columnas esperadas en la tabla [$table]."
            );
        }
    }

    public function test_database_seeder_populates_admin_roles_categories_and_medicines_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseHas('roles', ['slug' => Role::ADMIN, 'name' => Role::API_ADMIN]);
        $this->assertDatabaseHas('roles', ['slug' => Role::PHARMACIST, 'name' => Role::API_PHARMACIST]);
        $this->assertDatabaseHas('roles', ['slug' => Role::CLIENT, 'name' => Role::API_CLIENT]);

        $admin = User::query()->with('role')->where('email', 'admin@lifemedic.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasRole(Role::ADMIN));
        $this->assertTrue($admin->hasRole(Role::API_ADMIN));

        Cache::flush();
        $meta = app(UserManagementService::class)->metadataForUser($admin->id);
        $this->assertSame(Role::ADMIN, $meta['role']);
        $this->assertTrue($meta['is_active']);

        $this->assertDatabaseCount('categories', 6);
        $expectedCatalogCount = count(app(CatalogRepositoryInterface::class)->all());
        $this->assertGreaterThan(0, $expectedCatalogCount);
        $this->assertDatabaseCount('medicines', $expectedCatalogCount);
        $this->assertDatabaseHas('medicines', ['codigo' => 'MED-00101']);
    }

    public function test_eloquent_relationships_and_foreign_keys_work_across_all_models(): void
    {
        $this->seed(DatabaseSeeder::class);

        $clientRole = Role::query()->where('slug', Role::CLIENT)->firstOrFail();
        $client = User::factory()->create([
            'role_id' => $clientRole->id,
            'phone' => '7777-8888',
            'address' => 'Colonia Escalón, San Salvador',
            'is_active' => true,
        ]);

        $this->assertSame($clientRole->id, $client->role->id);
        $this->assertTrue($clientRole->users->contains('id', $client->id));

        $category = Category::query()->where('slug', 'medicamentos')->firstOrFail();
        $medicine = $category->medicines()->firstOrFail();
        $this->assertSame($category->id, $medicine->category->id);
        $this->assertIsArray($medicine->formula);

        $cart = Cart::query()->create([
            'user_id' => $client->id,
            'status' => Cart::STATUS_ACTIVE,
        ]);

        $cartItem = CartItem::query()->create([
            'cart_id' => $cart->id,
            'medicine_id' => $medicine->id,
            'quantity' => 2,
            'unit_price' => $medicine->price,
        ]);

        $this->assertSame($cart->id, $client->cart->id);
        $this->assertCount(1, $client->carts);
        $this->assertCount(1, $cart->items);
        $this->assertSame($medicine->id, $cartItem->medicine->id);
        $this->assertTrue($medicine->cartItems->contains('id', $cartItem->id));

        $order = Order::query()->create([
            'user_id' => $client->id,
            'order_number' => 'LM-2026-99001',
            'customer_name' => $client->name,
            'customer_email' => $client->email,
            'phone' => $client->phone,
            'delivery_method' => 'Domicilio',
            'delivery_address' => $client->address,
            'free_shipping' => false,
            'shipping_cost' => 2.50,
            'subtotal' => 4.02,
            'total' => 6.52,
            'status' => Order::STATUS_PENDING,
            'placed_at' => now(),
            'status_updated_at' => now(),
        ]);

        $orderDetail = OrderDetail::query()->create([
            'order_id' => $order->id,
            'medicine_id' => $medicine->id,
            'codigo' => $medicine->codigo,
            'name' => $medicine->name,
            'quantity' => 2,
            'unit_price' => $medicine->price,
            'line_total' => round((float) $medicine->price * 2, 2),
        ]);

        $this->assertSame($client->id, $order->user->id);
        $this->assertTrue($client->orders->contains('id', $order->id));
        $this->assertCount(1, $order->details);
        $this->assertSame($order->id, $orderDetail->order->id);
        $this->assertSame($medicine->id, $orderDetail->medicine->id);
        $this->assertTrue($medicine->orderDetails->contains('id', $orderDetail->id));

        try {
            $category->delete();
            $this->fail('Se esperaba que la clave foránea impidiera eliminar una categoría con medicamentos.');
        } catch (QueryException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        try {
            $client->delete();
            $this->fail('Se esperaba que la clave foránea impidiera eliminar un cliente con pedidos.');
        } catch (QueryException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $order->delete();
        $this->assertDatabaseMissing('order_details', ['id' => $orderDetail->id]);

        $client->delete();
        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }
}
