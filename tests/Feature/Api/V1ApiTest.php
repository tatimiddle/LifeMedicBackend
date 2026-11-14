<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class V1ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_register_login_me_and_logout_preserve_v1_contract(): void
    {
        $register = $this->postJson('/api/v1/register', [
            'name' => 'Sofía Mendoza',
            'email' => 'sofia.mendoza@lifemedic.com',
            'phone' => '7123-4567',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ]);

        $register->assertCreated()
            ->assertJsonPath('data.user.email', 'sofia.mendoza@lifemedic.com')
            ->assertJsonPath('data.user.role.name', Role::API_CLIENT)
            ->assertJsonPath('data.user.role.slug', Role::CLIENT);

        $this->assertNotEmpty($register->json('data.token'));

        $login = $this->postJson('/api/v1/login', [
            'email' => 'sofia.mendoza@lifemedic.com',
            'password' => 'Secreta123!',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'sofia.mendoza@lifemedic.com')
            ->assertJsonPath('data.role.name', 'customer');

        $this->withToken($token)
            ->putJson('/api/v1/me', [
                'name' => 'Sofía A. Mendoza',
                'address' => 'Santa Tecla, La Libertad',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Sofía A. Mendoza')
            ->assertJsonPath('data.address', 'Santa Tecla, La Libertad');

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada.');

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }

    public function test_v1_catalog_endpoints_return_seeded_categories_medicines_products_and_clean_image_urls(): void
    {
        $this->seed(DatabaseSeeder::class);

        $categories = $this->getJson('/api/v1/categories')->assertOk();
        $this->assertCount(6, $categories->json('data'));

        $medicines = $this->getJson('/api/v1/medicines?search=Paracetamol&per_page=10')->assertOk();
        $rows = $medicines->json('data.data');
        $this->assertNotEmpty($rows);
        $this->assertSame('MED-00101', $rows[0]['codigo']);
        $this->assertStringStartsWith('/images/catalog/', $rows[0]['image_url']);
        $this->assertStringNotContainsString('/storage//images/', $rows[0]['image_url']);

        $this->getJson('/api/v1/medicines/MED-00101')
            ->assertOk()
            ->assertJsonPath('data.codigo', 'MED-00101');

        $firstId = $rows[0]['id'];
        $this->getJson("/api/v1/medicines/{$firstId}")
            ->assertOk()
            ->assertJsonPath('data.id', $firstId);

        $products = $this->getJson('/api/v1/products')->assertOk();
        foreach ($products->json('data.data') as $product) {
            $this->assertSame('product', $product['catalog_type']);
        }
    }

    public function test_v1_cart_and_order_checkout_flow_updates_stock_and_protects_ownership(): void
    {
        $this->seed(DatabaseSeeder::class);

        $medicine = Medicine::query()->where('codigo', 'MED-00101')->firstOrFail();
        $initialStock = (int) $medicine->stock;

        $buyerToken = $this->postJson('/api/v1/register', [
            'name' => 'Diego Alvarado',
            'email' => 'diego.alvarado@lifemedic.com',
            'phone' => '7000-1122',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()->json('data.token');

        $otherToken = $this->postJson('/api/v1/register', [
            'name' => 'Lucía Chávez',
            'email' => 'lucia.chavez@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()->json('data.token');

        $this->withToken($buyerToken)
            ->postJson('/api/v1/orders', [])
            ->assertStatus(422);

        $addRes = $this->withToken($buyerToken)
            ->postJson('/api/v1/cart/items', [
                'medicine_id' => $medicine->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.items_count', 2);

        $cartItemId = $addRes->json('data.items.0.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($otherToken)
            ->patchJson("/api/v1/cart/items/{$cartItemId}", ['quantity' => 5])
            ->assertStatus(404);

        $this->app['auth']->forgetGuards();

        $this->withToken($buyerToken)
            ->patchJson("/api/v1/cart/items/{$cartItemId}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.items_count', 3);

        $this->withToken($buyerToken)
            ->postJson('/api/v1/cart/items', [
                'medicine_id' => $medicine->id,
                'quantity' => $initialStock + 50,
            ])
            ->assertStatus(422);

        $orderRes = $this->withToken($buyerToken)
            ->postJson('/api/v1/orders', [
                'delivery_method' => 'Domicilio',
                'delivery_address' => 'Colonia Médica #24, San Salvador',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.details.0.quantity', 3);

        $orderId = $orderRes->json('data.id');

        $this->assertSame($initialStock - 3, (int) $medicine->fresh()->stock);
        $this->assertEquals(round((float) $medicine->price * 3, 2), (float) $orderRes->json('data.total'));

        $this->withToken($buyerToken)
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items_count', 0);

        $this->withToken($buyerToken)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($buyerToken)
            ->getJson("/api/v1/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.id', $orderId);

        $this->app['auth']->forgetGuards();

        $this->withToken($otherToken)
            ->getJson("/api/v1/orders/{$orderId}")
            ->assertStatus(403);
    }

    public function test_v1_admin_medicine_endpoints_enforce_admin_role_and_crud(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $category = Category::query()->where('slug', 'medicamentos')->firstOrFail();

        $customer = app(UserManagementService::class)->createUser([
            'name' => 'Cliente Estándar',
            'email' => 'cliente.std@lifemedic.com',
            'password' => 'Secreta123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);
        $customerToken = $customer->createToken('test')->plainTextToken;

        $this->withToken($customerToken)
            ->postJson('/api/v1/admin/medicines', [
                'category_id' => $category->id,
                'codigo' => 'MED-BLK-1',
                'name' => 'No Autorizado',
                'price' => 5.00,
                'stock' => 10,
            ])
            ->assertStatus(403);

        $admin = User::query()->where('email', 'admin@lifemedic.com')->firstOrFail();
        $adminToken = $admin->createToken('admin-api')->plainTextToken;

        $this->app['auth']->forgetGuards();

        $created = $this->withToken($adminToken)
            ->post('/api/v1/admin/medicines', [
                'category_id' => $category->id,
                'codigo' => 'MED-V1-777',
                'name' => 'Vitamina C Efervescente',
                'brand' => 'Bayer',
                'price' => 11.50,
                'stock' => 25,
                'image' => UploadedFile::fake()->image('vitc.png', 200, 200),
            ]);

        $created->assertCreated()
            ->assertJsonPath('codigo', 'MED-V1-777');

        $medicineId = $created->json('id');
        $imageUrl = $created->json('image_url');
        $this->assertStringStartsWith('/storage/medicines/', $imageUrl);

        $this->withToken($adminToken)
            ->patchJson("/api/v1/admin/medicines/{$medicineId}", [
                'price' => 13.25,
                'stock' => 40,
            ])
            ->assertOk()
            ->assertJsonPath('stock', 40);

        $this->withToken($adminToken)
            ->deleteJson("/api/v1/admin/medicines/{$medicineId}")
            ->assertOk();

        $this->assertFalse((bool) Medicine::query()->findOrFail($medicineId)->is_active);
    }

    public function test_v1_edge_cases_cart_delete_codigo_checkout_with_items_and_inactive_login(): void
    {
        $this->seed(DatabaseSeeder::class);

        $med = Medicine::query()->where('codigo', 'MED-00101')->firstOrFail();
        $outOfStock = Medicine::query()->where('stock', 0)->first();

        $inactiveUser = app(UserManagementService::class)->createUser([
            'name' => 'Inactivo Test',
            'email' => 'inactivo.v1@lifemedic.com',
            'password' => 'Secreta123!',
            'role' => Role::CLIENT,
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'inactivo.v1@lifemedic.com',
            'password' => 'Secreta123!',
        ])->assertStatus(403);

        $this->postJson('/api/v1/login', [
            'email' => 'inactivo.v1@lifemedic.com',
            'password' => 'WrongPassword!',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $buyerToken = $this->postJson('/api/v1/register', [
            'name' => 'Marcos Edge',
            'email' => 'marcos.edge@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()->json('data.token');

        $otherToken = $this->postJson('/api/v1/register', [
            'name' => 'Otro Edge',
            'email' => 'otro.edge@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()->json('data.token');

        $added = $this->withToken($buyerToken)
            ->postJson('/api/v1/cart/items', [
                'codigo' => $med->codigo,
                'quantity' => 2,
            ])->assertCreated();

        $itemId = $added->json('data.items.0.id');

        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)
            ->deleteJson("/api/v1/cart/items/{$itemId}")
            ->assertStatus(404);

        $this->app['auth']->forgetGuards();
        $this->withToken($buyerToken)
            ->deleteJson("/api/v1/cart/items/{$itemId}")
            ->assertOk()
            ->assertJsonPath('data.items_count', 0);

        $readded = $this->withToken($buyerToken)
            ->postJson('/api/v1/cart/items', [
                'medicine_id' => $med->id,
                'quantity' => 1,
            ])->assertCreated();

        $readdedId = $readded->json('data.items.0.id');
        $this->withToken($buyerToken)
            ->patchJson("/api/v1/cart/items/{$readdedId}", ['quantity' => 0])
            ->assertOk()
            ->assertJsonPath('data.items_count', 0);

        if ($outOfStock !== null) {
            $this->withToken($buyerToken)
                ->postJson('/api/v1/orders', [
                    'items' => [
                        ['medicine_id' => $outOfStock->id, 'quantity' => 1],
                    ],
                ])->assertStatus(422);
        }

        $stockBefore = (int) $med->stock;
        $directOrder = $this->withToken($buyerToken)
            ->postJson('/api/v1/orders', [
                'delivery_method' => 'Sucursal',
                'items' => [
                    ['medicine_id' => $med->id, 'quantity' => 2],
                ],
            ])->assertCreated()
            ->assertJsonPath('data.delivery_method', 'Sucursal')
            ->assertJsonPath('data.details.0.quantity', 2);

        $this->assertSame($stockBefore - 2, (int) $med->fresh()->stock);
        $this->assertEquals(round((float) $med->price * 2, 2), (float) $directOrder->json('data.total'));
    }

    public function test_unknown_role_names_never_match_cliente_and_assign_role_refreshes_user(): void
    {
        $service = app(UserManagementService::class);
        $customer = $service->createUser([
            'name' => 'Cliente Estricto',
            'email' => 'cliente.estricto@lifemedic.com',
            'password' => 'Secreta123!',
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $this->assertTrue($customer->hasRole('customer'));
        $this->assertTrue($customer->hasRole('cliente'));
        $this->assertFalse($customer->hasRole('admin'));
        $this->assertFalse($customer->hasRole('superadmin'));
        $this->assertFalse($customer->hasRole('editor'));
        $this->assertFalse($customer->hasRole(''));

        $service->assignRole($customer, 'admin');
        $this->assertTrue($customer->hasRole('admin'));
        $this->assertTrue($customer->hasRole('administrador'));
        $this->assertFalse($customer->hasRole('superadmin'));
    }

    public function test_v1_uppercase_duplicate_email_returns_422_and_products_filter_cannot_be_bypassed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->postJson('/api/v1/register', [
            'name' => 'Carlos Primero',
            'email' => 'carlos.dup@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated();

        $this->postJson('/api/v1/register', [
            'name' => 'Carlos Mayuscula',
            'email' => 'CARLOS.DUP@LIFEMEDIC.COM',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $secondToken = $this->postJson('/api/v1/register', [
            'name' => 'Segundo Usuario',
            'email' => 'segundo.dup@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()->json('data.token');

        $this->withToken($secondToken)
            ->patchJson('/api/v1/me', [
                'email' => 'CARLOS.DUP@LIFEMEDIC.COM',
            ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $products = $this->getJson('/api/v1/products?catalog_type=medicine&per_page=100')
            ->assertOk()
            ->json('data.data');

        $this->assertNotEmpty($products);
        foreach ($products as $item) {
            $this->assertSame('product', $item['catalog_type']);
        }
    }

    public function test_v1_admin_medicine_handles_nullable_metadata_case_insensitive_sku_and_live_cart_pricing(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@lifemedic.com')->firstOrFail();
        $adminToken = $admin->createToken('admin-v1-meta')->plainTextToken;
        $category = Category::query()->firstOrFail();

        $created = $this->withToken($adminToken)
            ->postJson('/api/v1/admin/medicines', [
                'category_id' => $category->id,
                'codigo' => 'med-mix-01',
                'catalog_type' => null,
                'name' => 'Ibuprofeno Compuesto Test',
                'formula' => [
                    ['ingredient' => 'Ibuprofeno', 'amount' => '400 mg'],
                    ['ingredient' => 'Cafeína', 'amount' => '50 mg'],
                ],
                'expiry_status' => null,
                'prescription' => null,
                'min_stock' => null,
                'price' => 14.00,
                'stock' => 30,
            ]);

        $created->assertCreated()
            ->assertJsonPath('codigo', 'MED-MIX-01')
            ->assertJsonPath('formula_summary', 'Ibuprofeno (400 mg), Cafeína (50 mg)')
            ->assertJsonPath('expiry_status', 'Vigente')
            ->assertJsonPath('prescription', 'Venta Libre')
            ->assertJsonPath('min_stock', 20);

        $medicineId = $created->json('id');

        $this->withToken($adminToken)
            ->postJson('/api/v1/admin/medicines', [
                'category_id' => $category->id,
                'codigo' => 'MED-MIX-01',
                'name' => 'Duplicado',
                'price' => 10.00,
                'stock' => 5,
            ])->assertStatus(422)
            ->assertJsonValidationErrors('codigo');

        $this->withToken($adminToken)
            ->patchJson("/api/v1/admin/medicines/{$medicineId}", [
                'expiry_status' => null,
                'prescription' => null,
                'min_stock' => null,
                'price' => 19.50,
            ])->assertOk()
            ->assertJsonPath('expiry_status', 'Vigente')
            ->assertJsonPath('min_stock', 20)
            ->assertJsonPath('price', '19.50');

        $this->app['auth']->forgetGuards();
        $buyerToken = $this->withoutHeader('Authorization')
            ->postJson('/api/v1/register', [
                'name' => 'Cliente Precio Vivo',
                'email' => 'preciovivo@lifemedic.com',
                'password' => 'Secreta123!',
                'password_confirmation' => 'Secreta123!',
            ])->assertCreated()->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->withToken($buyerToken)
            ->postJson('/api/v1/cart/items', [
                'medicine_id' => $medicineId,
                'quantity' => 2,
            ])->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->withToken($adminToken)
            ->patchJson("/api/v1/admin/medicines/{$medicineId}", [
                'price' => 22.00,
            ])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($buyerToken)
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price', 22)
            ->assertJsonPath('data.subtotal', 44);
    }

    public function test_v1_catalog_rejects_inverted_price_range_and_updates_profile_phone_and_address(): void
    {
        $this->getJson('/api/v1/medicines?min_price=10&max_price=2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price']);

        $this->getJson('/api/v1/medicines?min_price=10.50&max_price=10.49')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price']);

        $token = $this->postJson('/api/v1/register', [
            'name' => 'Carlos Ramos',
            'email' => 'carlos.ramos@lifemedic.com',
            'phone' => '7011-2233',
            'address' => 'Colonia Escalón #12',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)
            ->putJson('/api/v1/me', [
                'phone' => '7999-8877',
                'address' => 'Residencial Palma #44',
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '7999-8877')
            ->assertJsonPath('data.address', 'Residencial Palma #44');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)
            ->putJson('/api/v1/me', [
                'address' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '7999-8877')
            ->assertJsonPath('data.address', null);

        $user = User::query()->where('email', 'carlos.ramos@lifemedic.com')->firstOrFail();
        $this->assertSame('7999-8877', $user->phone);
        $this->assertNull($user->address);
    }
}
