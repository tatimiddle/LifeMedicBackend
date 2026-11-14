<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_clientes_module_renders_app_customers_in_spanish(): void
    {
        $response = $this->get(route('clientes.index'));

        $response->assertOk()
            ->assertSee('Clientes')
            ->assertSee('Nuevo Cliente')
            ->assertSee('Clientes Registrados')
            ->assertSee('Oscar Mendoza')
            ->assertSee('Valeria Castillo')
            ->assertSee('Activo')
            ->assertSee('Inactivo')
            ->assertSee('No se encontraron clientes')
            ->assertDontSee('Customer')
            ->assertDontSee('Orders');
    }

    public function test_medicamentos_module_renders_scraped_clinical_info_and_excludes_retail_products(): void
    {
        $response = $this->get(route('medicamentos.index'));

        $response->assertOk()
            ->assertSee('Medicamentos')
            ->assertSee('Registrar Medicamento')
            ->assertSee('MED-00101')
            ->assertSee('/images/catalog/MED-00101.webp')
            ->assertSee('Tecnoquimicas')
            ->assertSee('Dolor de cuerpo')
            ->assertSee('Vigente')
            ->assertSee('Por Vencer')
            ->assertSee('Con Receta')
            ->assertSee('Venta Libre')
            ->assertSee('No se encontraron medicamentos')
            ->assertDontSee('DER-00205')
            ->assertDontSee('FRA-00608')
            ->assertDontSee('Medicine')
            ->assertDontSee('Out of Stock');
    }

    public function test_facturas_module_renders_list_and_preview_modal(): void
    {
        $response = $this->get(route('facturas.index'));

        $response->assertOk()
            ->assertSee('Facturas')
            ->assertSee('Nueva Factura')
            ->assertSee('#FAC-0985')
            ->assertSee('#FAC-0981')
            ->assertSee('Pagada')
            ->assertSee('Pendiente')
            ->assertSee('Vencida')
            ->assertSee('Vista Previa de Factura')
            ->assertSee('No se encontraron facturas')
            ->assertDontSee('Invoice')
            ->assertDontSee('Paid Amount');
    }
}
