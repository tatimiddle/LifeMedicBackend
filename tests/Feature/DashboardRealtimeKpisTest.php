<?php

namespace Tests\Feature;

use App\Contracts\CatalogRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealtimeKpisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_dashboard_metrics_update_immediately_when_inventory_and_catalog_change(): void
    {
        $catalog = app(CatalogRepositoryInterface::class);

        $beforeInv = $catalog->inventorySummary();

        // Registrar medicamento con stock bajo (stock <= min_stock)
        $this->post(route('medicamentos.store'), [
            'codigo' => 'MED-00777',
            'name' => 'Azitromicina 500mg (Caja x 5)',
            'active_ingredient' => 'Azitromicina',
            'category' => 'Medicamentos',
            'brand' => 'Square',
            'batch' => 'LOT-26Z07',
            'expiry' => 'Sep 2027',
            'expiry_status' => 'Vigente',
            'prescription' => 'Con Receta',
            'price' => '15.00',
            'stock' => '5',
            'min_stock' => '20',
            'is_active' => '1',
        ])->assertRedirect(route('medicamentos.index'));

        // Registrar producto retail para verificar incremento en Productos en Catálogo
        $this->post(route('productos.store'), [
            'codigo' => 'DER-00888',
            'name' => 'Sérum Hidratante Ácido Hialurónico 30ml',
            'category' => 'Dermocosmética',
            'brand' => 'La Roche Posay',
            'price' => '34.50',
            'stock' => '18',
            'min_stock' => '10',
            'is_active' => '1',
        ])->assertRedirect(route('productos.index'));

        $afterInv = $catalog->inventorySummary();

        $this->assertSame($beforeInv['total_medicines'] + 2, $afterInv['total_medicines']);
        $this->assertSame($beforeInv['low_stock_alerts'] + 1, $afterInv['low_stock_alerts']);
        $this->assertSame($beforeInv['retail_products'] + 1, $afterInv['retail_products']);

        $this->get('/')
            ->assertOk()
            ->assertSee((string) $afterInv['total_medicines'])
            ->assertSee((string) $afterInv['low_stock_alerts'])
            ->assertSee((string) $afterInv['retail_products']);
    }
}
