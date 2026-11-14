<?php

namespace Tests\Feature;

use App\Contracts\CatalogRepositoryInterface;
use App\Support\PharmacyMockData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_exact_spanish_content_realtime_kpis_and_aqua_branding(): void
    {
        $this->actingAsAdmin();

        $inventory = app(CatalogRepositoryInterface::class)->inventorySummary();
        $sales = PharmacyMockData::sales();

        $response = $this->get('/');

        $response->assertOk();

        $requiredStrings = [
            'Panel Administrativo',
            'data-realtime-kpis="true"',
            'Total de Medicamentos',
            'Alertas de Bajo Stock',
            'Productos en Catálogo',
            'Ventas de Hoy',
            (string) $inventory['total_medicines'],
            (string) $inventory['low_stock_alerts'],
            (string) $inventory['retail_products'],
            $sales['kpis'][0]['value'],
            'lifemedic-logo-dark.png',
            '#095d52',
            'Reporte Gráfico de Hoy',
            'Categorías Activas',
            'Sin Stock',
            'Total Medicamentos',
            'Medicamentos Más Vendidos',
            'Paracetamol',
            '$54,647.00',
            'Ibuprofeno',
            '$12,345.00',
            'Amoxicilina',
            '$16,567.00',
            'Metformina',
            '$10,412.00',
            'Ingresos vs Gastos Totales',
            'Gastos',
            '20K',
            '15K',
            '10K',
            '5K',
            'Reporte de Hoy',
            'Ganancia Total',
            '$536.00',
            '80%',
            'Venta Directa',
            'Efectivo Recibido',
            'Recibido en Banco',
            'Servicio Total',
            'Ene',
            'Feb',
            'Mar',
            'Abr',
            'May',
            'Jun',
            'Jul',
            'Ago',
            'Sep',
            'Oct',
            'Nov',
            'Dic',
            '/ventas',
            '/productos',
            '/clientes',
            '/categorias',
            '/medicamentos',
            '/facturas',
        ];

        foreach ($requiredStrings as $expected) {
            $response->assertSee($expected, false);
        }

        $forbiddenStrings = [
            'Lista de Compras',
            'Preferencias',
            'Reporte de Ventas',
            'Ayuda y Soporte',
            'Configuración',
            'Total Proveedores',
            'Compra Total',
            'Órdenes Pendientes',
            '/compras',
            '/proveedores',
            '/reportes/ventas',
            '/ayuda',
            '/configuracion',
            'Graph Report Today',
            'Top Selling Medicine',
            'Over All Revenue',
            "Today's Report",
            'Purchase List',
            'Upgrade Pro',
            'Out of Stock',
            'View All',
            '#165dff',
            '#256eff',
            '#256EFF',
        ];

        foreach ($forbiddenStrings as $forbidden) {
            $response->assertDontSee($forbidden);
        }
    }
}
