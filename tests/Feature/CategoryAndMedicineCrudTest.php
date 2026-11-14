<?php

namespace Tests\Feature;

use App\Contracts\CatalogRepositoryInterface;
use App\Models\Category;
use App\Models\Medicine;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryAndMedicineCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_admin_can_create_edit_toggle_and_delete_categories_and_sync_with_api(): void
    {
        $this->post(route('categorias.store'), [
            'name' => 'Higiene Oral',
            'description' => 'Enjuagues antisépticos y pastas medicadas.',
            'is_active' => '1',
        ])->assertRedirect(route('categorias.index'));

        $this->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Higiene Oral')
            ->assertSee('higiene-oral');

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Higiene Oral', 'slug' => 'higiene-oral']);

        $this->patch(route('categorias.update', 'higiene-oral'), [
            'name' => 'Cuidado Bucal',
            'description' => 'Productos odontológicos de uso clínico.',
            'is_active' => '1',
        ])->assertRedirect(route('categorias.index'));

        $this->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Cuidado Bucal')
            ->assertDontSee('Higiene Oral');

        $this->patch(route('categorias.toggleStatus', 'cuidado-bucal'))
            ->assertRedirect(route('categorias.index'));

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'cuidado-bucal']);

        $this->delete(route('categorias.destroy', 'cuidado-bucal'))
            ->assertRedirect(route('categorias.index'));

        $this->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('eliminada correctamente');

        $this->get(route('categorias.index'))
            ->assertOk()
            ->assertDontSee('Cuidado Bucal');
    }

    public function test_category_with_assigned_medicines_cannot_be_deleted(): void
    {
        $this->delete(route('categorias.destroy', 'medicamentos'))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHasErrors('delete');

        $this->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Medicamentos');
    }

    public function test_admin_can_create_medicine_with_image_update_price_stock_baja_logica_and_delete(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('loratadina.png', 240, 240);

        $this->post(route('medicamentos.store'), [
            'codigo' => 'MED-00999',
            'name' => 'Loratadina 10mg (Caja x 10)',
            'active_ingredient' => 'Loratadina',
            'category' => 'Medicamentos',
            'brand' => 'Bayer Salud',
            'batch' => 'LOT-26L99',
            'expiry' => 'Oct 2027',
            'expiry_status' => 'Vigente',
            'prescription' => 'Venta Libre',
            'price' => '7.25',
            'stock' => '45',
            'min_stock' => '15',
            'description' => 'Antihistamínico oral para alivio de alergias.',
            'image' => $image,
            'is_active' => '1',
        ])->assertRedirect(route('medicamentos.index'));

        $created = app(CatalogRepositoryInterface::class)->find('MED-00999');
        $this->assertNotNull($created);
        $this->assertNotNull($created['image_path']);
        Storage::disk('public')->assertExists($created['image_path']);

        $this->get(route('medicamentos.index'))
            ->assertOk()
            ->assertSee('MED-00999')
            ->assertSee('Loratadina 10mg (Caja x 10)')
            ->assertSee($created['image_url']);

        $this->getJson('/api/medicines/MED-00999')
            ->assertOk()
            ->assertJsonPath('data.price', 7.25)
            ->assertJsonPath('data.stock', 45)
            ->assertJsonPath('data.image_url', $created['image_url']);

        $this->patch(route('medicamentos.updateInventory', 'MED-00999'), [
            'price' => '8.90',
            'stock' => '12',
            'min_stock' => '15',
        ])->assertRedirect(route('medicamentos.index'));

        $this->getJson('/api/medicines/MED-00999')
            ->assertOk()
            ->assertJsonPath('data.price', 8.9)
            ->assertJsonPath('data.stock', 12)
            ->assertJsonPath('data.available', true);

        $replacement = UploadedFile::fake()->image('loratadina-v2.jpg', 240, 240);
        $oldImagePath = $created['image_path'];

        $this->patch(route('medicamentos.update', 'MED-00999'), [
            'name' => 'Loratadina Forte 10mg (Caja x 20)',
            'active_ingredient' => 'Loratadina',
            'category' => 'Medicamentos',
            'brand' => 'Bayer Salud',
            'batch' => 'LOT-26L99',
            'expiry' => 'Oct 2028',
            'expiry_status' => 'Vigente',
            'prescription' => 'Con Receta',
            'price' => '12.40',
            'stock' => '25',
            'min_stock' => '10',
            'description' => 'Presentación doble para tratamiento extendido.',
            'image' => $replacement,
            'is_active' => '1',
        ])->assertRedirect(route('medicamentos.index'));

        $updated = app(CatalogRepositoryInterface::class)->find('MED-00999');
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($updated['image_path']);

        // Baja lógica
        $this->patch(route('medicamentos.toggleStatus', 'MED-00999'))
            ->assertRedirect(route('medicamentos.index'));

        $this->getJson('/api/medicines/MED-00999')
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.available', false);

        // Eliminación definitiva + borrado de imagen
        $imageToRemove = $updated['image_path'];
        $this->delete(route('medicamentos.destroy', 'MED-00999'))
            ->assertRedirect(route('medicamentos.index'));

        Storage::disk('public')->assertMissing($imageToRemove);
        $this->getJson('/api/medicines/MED-00999')->assertStatus(404);
    }

    public function test_admin_can_crud_non_medicine_products_and_preserves_catalog_separation(): void
    {
        Storage::fake('public');

        // Rechazar categoría medicinal desde el módulo de Productos
        $this->post(route('productos.store'), [
            'codigo' => 'PRD-00900',
            'name' => 'Producto Inválido Medicinal',
            'category' => 'Medicamentos',
            'brand' => 'LifeMedic',
            'price' => '9.99',
            'stock' => '10',
        ])->assertSessionHasErrors('category');

        $image = UploadedFile::fake()->image('serum-facial.png', 240, 240);

        $this->post(route('productos.store'), [
            'codigo' => 'PRD-00901',
            'name' => 'Serum Hidratante Hialurónico 30ml',
            'category' => 'Dermocosmética',
            'san_nicolas_category' => 'Cuidado facial',
            'brand' => 'La Roche Posay',
            'price' => '29.50',
            'previous_price' => '34.00',
            'stock' => '18',
            'min_stock' => '5',
            'description' => 'Serum dermatológico de hidratación intensiva.',
            'image' => $image,
            'is_active' => '1',
        ])->assertRedirect(route('productos.index'));

        $created = app(CatalogRepositoryInterface::class)->find('PRD-00901');
        $this->assertNotNull($created);
        $this->assertSame('product', $created['catalog_type']);
        Storage::disk('public')->assertExists($created['image_path']);

        // Presente en Productos y API, ausente en el módulo clínico de Medicamentos
        $this->get(route('productos.index'))
            ->assertOk()
            ->assertSee('PRD-00901')
            ->assertSee('Serum Hidratante Hialurónico 30ml');

        $this->get(route('medicamentos.index'))
            ->assertOk()
            ->assertDontSee('PRD-00901');

        $this->patch(route('productos.updateInventory', 'PRD-00901'), [
            'price' => '27.90',
            'stock' => '12',
            'min_stock' => '5',
        ])->assertRedirect(route('productos.index'));

        $this->getJson('/api/medicines/PRD-00901')
            ->assertOk()
            ->assertJsonPath('data.catalog_type', 'product')
            ->assertJsonPath('data.price', 27.9)
            ->assertJsonPath('data.stock', 12);

        // Editar producto sin reemplazar imagen conserva su URL actual
        $currentUrl = $created['image_url'];
        $this->patch(route('productos.update', 'PRD-00901'), [
            'name' => 'Serum Hidratante Hialurónico B5 30ml',
            'category' => 'Dermocosmética',
            'brand' => 'La Roche Posay',
            'price' => '31.00',
            'stock' => '12',
            'is_active' => '1',
        ])->assertRedirect(route('productos.index'));

        $updated = app(CatalogRepositoryInterface::class)->find('PRD-00901');
        $this->assertSame($currentUrl, $updated['image_url']);

        // Baja lógica y eliminación definitiva
        $this->patch(route('productos.toggleStatus', 'PRD-00901'))
            ->assertRedirect(route('productos.index'));

        $this->assertFalse(app(CatalogRepositoryInterface::class)->find('PRD-00901')['is_active']);

        $imagePathToRemove = $updated['image_path'];
        $this->delete(route('productos.destroy', 'PRD-00901'))
            ->assertRedirect(route('productos.index'));

        Storage::disk('public')->assertMissing($imagePathToRemove);
        $this->assertNull(app(CatalogRepositoryInterface::class)->find('PRD-00901'));
    }

    public function test_updating_scraped_medicine_preserves_static_image_url_when_no_upload(): void
    {
        $this->patch(route('medicamentos.update', 'MED-00101'), [
            'name' => 'Paracetamol 500mg (Caja x 20)',
            'active_ingredient' => 'Acetaminofen',
            'category' => 'Medicamentos',
            'brand' => 'Genericos Mk',
            'laboratory' => 'Tecnoquimicas',
            'expiry_status' => 'Vigente',
            'prescription' => 'Venta Libre',
            'price' => '2.25',
            'stock' => '90',
        ])->assertRedirect(route('medicamentos.index'));

        $updated = app(CatalogRepositoryInterface::class)->find('MED-00101');
        $this->assertSame('/images/catalog/MED-00101.webp', $updated['image_url']);
        $this->assertSame('Tecnoquimicas', $updated['laboratory']);
    }

    public function test_seeded_catalog_contains_at_least_30_unique_medicines_and_30_retail_products_with_images(): void
    {
        $catalog = app(CatalogRepositoryInterface::class);

        $medicines = $catalog->all(['catalog_type' => 'medicine', 'include_inactive' => true]);
        $products = $catalog->all(['catalog_type' => 'product', 'include_inactive' => true]);

        $medicineCount = count($medicines);
        $productCount = count($products);

        $this->assertGreaterThanOrEqual(30, $medicineCount, 'Se esperaban al menos 30 medicamentos reales en el catálogo.');
        $this->assertGreaterThanOrEqual(30, $productCount, 'Se esperaban al menos 30 productos retail reales en el catálogo.');

        $medicineSkus = [];
        foreach ($medicines as $med) {
            $this->assertSame('medicine', $med['catalog_type']);
            $this->assertNotEmpty($med['image_url']);
            $this->assertFileExists(public_path(ltrim($med['image_url'], '/')));
            $this->assertNotEmpty($med['active_ingredient']);
            $this->assertNotContains($med['codigo'], $medicineSkus);
            $medicineSkus[] = $med['codigo'];
        }

        $productSkus = [];
        foreach ($products as $prod) {
            $this->assertSame('product', $prod['catalog_type']);
            $this->assertNotSame('Medicamentos', $prod['category']);
            $this->assertNotEmpty($prod['image_url']);
            $this->assertFileExists(public_path(ltrim($prod['image_url'], '/')));
            $this->assertNotContains($prod['codigo'], $productSkus);
            $productSkus[] = $prod['codigo'];
        }

        $this->get(route('medicamentos.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mostrando', (string) $medicineCount, "de {$medicineCount} medicamentos"])
            ->assertDontSee('Ficha Farmacias San Nicolás');

        $this->get(route('productos.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mostrando', (string) $productCount, "de {$productCount} productos"])
            ->assertDontSee('Ficha Farmacias San Nicolás');
    }

    public function test_numeric_only_sku_does_not_collide_with_primary_key_and_previous_price_can_be_cleared(): void
    {
        $catalog = app(CatalogRepositoryInterface::class);
        $this->get(route('medicamentos.index'))->assertOk();

        $id10Original = Medicine::query()->findOrFail(10)->codigo;
        $this->assertNotSame('010', $id10Original);

        $this->post(route('medicamentos.store'), [
            'codigo' => '010',
            'name' => 'Medicamento Código Numérico',
            'active_ingredient' => 'Loratadina 10mg',
            'category' => 'Medicamentos',
            'brand' => 'LifeMedic',
            'laboratory' => 'LifeMedic Labs',
            'expiry_status' => 'Vigente',
            'prescription' => 'Venta Libre',
            'price' => '15.00',
            'previous_price' => '19.99',
            'stock' => '25',
            'min_stock' => '5',
            'is_active' => '1',
        ])->assertRedirect(route('medicamentos.index'))
            ->assertSessionHasNoErrors();

        $created = $catalog->findByCode('010');
        $this->assertNotNull($created);
        $this->assertSame(19.99, $created['previous_price']);
        $this->assertSame($id10Original, Medicine::query()->findOrFail(10)->codigo);

        $this->patch(route('medicamentos.update', '010'), [
            'name' => 'Medicamento Código Numérico Editado',
            'active_ingredient' => 'Loratadina 10mg',
            'category' => 'Medicamentos',
            'brand' => 'LifeMedic',
            'laboratory' => 'LifeMedic Labs',
            'expiry_status' => 'Vigente',
            'prescription' => 'Venta Libre',
            'price' => '14.50',
            'previous_price' => '',
            'stock' => '25',
            'min_stock' => '5',
            'is_active' => '1',
        ])->assertRedirect(route('medicamentos.index'))
            ->assertSessionHasNoErrors();

        $updated = $catalog->findByCode('010');
        $this->assertSame('Medicamento Código Numérico Editado', $updated['name']);
        $this->assertNull($updated['previous_price']);
        $this->assertSame($id10Original, Medicine::query()->findOrFail(10)->codigo);

        $this->post(route('medicamentos.store'), [
            'codigo' => '999010',
            'name' => 'Medicamento Código Alto',
            'active_ingredient' => 'Cetirizina',
            'category' => 'Medicamentos',
            'brand' => 'LifeMedic',
            'laboratory' => 'LifeMedic Labs',
            'expiry_status' => 'Vigente',
            'prescription' => 'Venta Libre',
            'price' => '11.00',
            'stock' => '15',
            'min_stock' => '5',
            'is_active' => '1',
        ])->assertRedirect(route('medicamentos.index'));

        $this->getJson('/api/v1/medicines/999010')
            ->assertOk()
            ->assertJsonPath('data.codigo', '999010');
    }

    public function test_deleting_medicine_referenced_in_order_fails_gracefully_and_preserves_image(): void
    {
        Storage::fake('public');

        $this->post(route('medicamentos.store'), [
            'codigo' => 'MED-ORD-LOCK',
            'name' => 'Amoxicilina Bloqueada',
            'active_ingredient' => 'Amoxicilina 500mg',
            'category' => 'Medicamentos',
            'brand' => 'MK',
            'laboratory' => 'Tecnoquimicas',
            'expiry_status' => 'Vigente',
            'prescription' => 'Con Receta',
            'price' => '8.50',
            'stock' => '40',
            'min_stock' => '10',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('locked.png', 200, 200),
        ])->assertRedirect(route('medicamentos.index'));

        $medicine = Medicine::query()->where('codigo', 'MED-ORD-LOCK')->firstOrFail();
        $this->assertNotEmpty($medicine->image_path);
        Storage::disk('public')->assertExists($medicine->image_path);

        $customer = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'LM-2026-LOCK01',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'delivery_method' => 'Domicilio',
            'subtotal' => 8.50,
            'shipping_cost' => 0,
            'total' => 8.50,
            'status' => Order::STATUS_PENDING,
            'placed_at' => now(),
        ]);

        OrderDetail::query()->create([
            'order_id' => $order->id,
            'medicine_id' => $medicine->id,
            'codigo' => $medicine->codigo,
            'name' => $medicine->name,
            'quantity' => 1,
            'unit_price' => 8.50,
            'line_total' => 8.50,
        ]);

        $this->delete(route('medicamentos.destroy', 'MED-ORD-LOCK'))
            ->assertRedirect(route('medicamentos.index'))
            ->assertSessionHasErrors('delete');

        $this->assertNotNull(Medicine::query()->where('codigo', 'MED-ORD-LOCK')->first());
        Storage::disk('public')->assertExists($medicine->image_path);
    }

    public function test_create_modals_include_hidden_is_active_fallback_and_create_inactive_items(): void
    {
        foreach ([
            route('categorias.index'),
            route('medicamentos.index'),
            route('productos.index'),
        ] as $route) {
            $html = $this->get($route)->assertOk()->getContent();
            $this->assertMatchesRegularExpression(
                '/<input\s+type="hidden"\s+name="is_active"\s+value="0"\s*\/>\s*<input\s+type="checkbox"\s+name="is_active"\s+value="1"\s+checked/i',
                $html
            );
        }

        $this->post(route('categorias.store'), [
            'name' => 'Categoría Borrador',
            'description' => 'Creada inicialmente inactiva desde el formulario.',
            'is_active' => '0',
        ])->assertRedirect(route('categorias.index'));

        $category = Category::query()->where('slug', 'categoria-borrador')->first();
        $this->assertNotNull($category);
        $this->assertFalse($category->is_active);

        $this->post(route('medicamentos.store'), [
            'codigo' => 'MED-00998',
            'name' => 'Ibuprofeno Borrador 400mg',
            'active_ingredient' => 'Ibuprofeno',
            'category' => 'Medicamentos',
            'brand' => 'LifeMedic',
            'expiry_status' => 'Vigente',
            'prescription' => 'Venta Libre',
            'price' => '4.50',
            'stock' => '20',
            'is_active' => '0',
        ])->assertRedirect(route('medicamentos.index'));

        $medicine = Medicine::query()->where('codigo', 'MED-00998')->first();
        $this->assertNotNull($medicine);
        $this->assertFalse($medicine->is_active);

        $this->post(route('productos.store'), [
            'codigo' => 'PRD-00998',
            'name' => 'Protector Labial Borrador',
            'category' => 'Dermocosmética',
            'brand' => 'LifeMedic',
            'price' => '6.00',
            'stock' => '15',
            'is_active' => '0',
        ])->assertRedirect(route('productos.index'));

        $product = Medicine::query()->where('codigo', 'PRD-00998')->first();
        $this->assertNotNull($product);
        $this->assertFalse($product->is_active);
    }
}
