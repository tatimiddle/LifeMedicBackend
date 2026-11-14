<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PreferenceModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_removed_preference_routes_return_not_found(): void
    {
        $this->get('/reportes/ventas')->assertNotFound();
        $this->get('/ayuda')->assertNotFound();
        $this->get('/configuracion')->assertNotFound();
        $this->get('/compras')->assertNotFound();
        $this->get('/proveedores')->assertNotFound();
    }

    public function test_plan_pro_renders_mensual_and_anual_billing_options(): void
    {
        $response = $this->get(route('plan-pro.index'));

        $response->assertOk()
            ->assertSee('Plan Pro')
            ->assertSee('Mensual')
            ->assertSee('Anual')
            ->assertSee('Plan Básico')
            ->assertSee('Plan Pro LifeMedic');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function allRouteNamesProvider(): array
    {
        return [
            'panel' => ['dashboard'],
            'pedidos' => ['pedidos.index'],
            'ventas' => ['ventas.index'],
            'productos' => ['productos.index'],
            'clientes' => ['clientes.index'],
            'usuarios' => ['usuarios.index'],
            'categorias' => ['categorias.index'],
            'medicamentos' => ['medicamentos.index'],
            'facturas' => ['facturas.index'],
            'plan-pro' => ['plan-pro.index'],
        ];
    }

    #[DataProvider('allRouteNamesProvider')]
    public function test_all_active_routes_return_200_with_no_leaked_english_ui_strings(string $routeName): void
    {
        $response = $this->get(route($routeName));

        $response->assertOk()
            ->assertDontSee('Graph Report Today')
            ->assertDontSee('Top Selling Medicine')
            ->assertDontSee('Purchase List')
            ->assertDontSee('Upgrade Pro')
            ->assertDontSee('Out of Stock')
            ->assertDontSee('View All');
    }
}
