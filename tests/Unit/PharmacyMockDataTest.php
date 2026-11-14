<?php

namespace Tests\Unit;

use App\Support\PharmacyMockData;
use PHPUnit\Framework\TestCase;

class PharmacyMockDataTest extends TestCase
{
    public function test_navigation_has_two_sections_and_nine_spanish_items(): void
    {
        $sections = PharmacyMockData::navigation();

        $this->assertCount(2, $sections);
        $this->assertSame(['Menú', 'Otros'], array_column($sections, 'title'));

        $items = array_merge(...array_column($sections, 'items'));
        $this->assertCount(9, $items);

        $labels = array_column($items, 'label');
        $this->assertSame([
            'Panel',
            'Pedidos',
            'Ventas',
            'Productos',
            'Clientes',
            'Usuarios',
            'Categorías',
            'Medicamentos',
            'Facturas',
        ], $labels);
    }

    public function test_dashboard_omits_purchases_suppliers_and_orders_data(): void
    {
        $this->assertFalse(method_exists(PharmacyMockData::class, 'purchases'));
        $this->assertFalse(method_exists(PharmacyMockData::class, 'suppliers'));
        $this->assertFalse(method_exists(PharmacyMockData::class, 'suppliersForCatalog'));
        $this->assertFalse(method_exists(PharmacyMockData::class, 'orders'));

        $dashboard = PharmacyMockData::dashboard();

        $this->assertArrayNotHasKey('purchases', $dashboard);
        $this->assertArrayHasKey('invoices', $dashboard['report_bars']['periods']['Este Mes'][0]);

        $json = json_encode($dashboard);
        $this->assertIsString($json);
        $this->assertStringNotContainsString('Total Proveedores', $json);
        $this->assertStringNotContainsString('Compra Total', $json);
        $this->assertStringNotContainsString('Pedidos Móviles', $json);
    }

    public function test_products_categories_match_lifemedic_spec(): void
    {
        $products = PharmacyMockData::products();

        $this->assertSame([
            'Todos',
            'Medicamentos',
            'Dermocosmética',
            'Vitaminas',
            'Cuidado Personal',
            'Bebé',
            'Fragancias',
        ], $products['categories']);
    }

    public function test_chart_palettes_avoid_ai_purple_and_pastel_lime(): void
    {
        $json = json_encode([
            PharmacyMockData::dashboard(),
        ]);

        $this->assertIsString($json);
        $this->assertStringNotContainsString('#8B5CF6', strtoupper($json));
        $this->assertStringNotContainsString('#A3E635', strtoupper($json));
    }
}
