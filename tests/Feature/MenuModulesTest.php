<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_removed_compras_and_proveedores_routes_return_not_found(): void
    {
        $this->get('/compras')->assertNotFound();
        $this->get('/proveedores')->assertNotFound();
    }

    public function test_ventas_module_renders_payment_mix_and_tickets(): void
    {
        $response = $this->get(route('ventas.index'));

        $response->assertOk()
            ->assertSee('Ventas')
            ->assertSee('Nueva Venta')
            ->assertSee('Ventas de Hoy')
            ->assertSee('Tarjeta')
            ->assertSee('Efectivo')
            ->assertSee('Transferencia')
            ->assertSee('#V-00418')
            ->assertSee('Oscar Mendoza')
            ->assertSee('No se encontraron ventas')
            ->assertDontSee('Sales Report')
            ->assertDontSee('Paid Amount');
    }

    public function test_productos_module_renders_retail_categories_and_excludes_clinical_medicines(): void
    {
        $response = $this->get(route('productos.index'));

        $response->assertOk()
            ->assertSee('Productos')
            ->assertSee('Nuevo Producto')
            ->assertSee('Dermocosmética')
            ->assertSee('Vitaminas')
            ->assertSee('Cuidado Personal')
            ->assertSee('Bebé')
            ->assertSee('Fragancias')
            ->assertSee('DER-00205')
            ->assertSee('/images/catalog/DER-00205.webp')
            ->assertSee('Eucerin')
            ->assertSee('La Roche Posay')
            ->assertSee('Bioderma')
            ->assertSee('No se encontraron productos')
            ->assertDontSee('MED-00101')
            ->assertDontSee('MED-00102')
            ->assertDontSee('Acetaminofén')
            ->assertDontSee('Out of Stock');
    }
}
