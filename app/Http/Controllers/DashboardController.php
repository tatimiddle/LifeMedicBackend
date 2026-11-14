<?php

namespace App\Http\Controllers;

use App\Contracts\CatalogRepositoryInterface;
use App\Support\PharmacyMockData;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
    ) {}

    public function __invoke(): View
    {
        $dashboard = PharmacyMockData::dashboard();
        $inventory = $this->catalog->inventorySummary();
        $sales = PharmacyMockData::sales();

        // Sincroniza métricas laterales de inventario en tiempo real
        $dashboard['report_bars']['kpis'] = [
            ['label' => 'Categorías Activas', 'value' => (string) $inventory['active_categories_count'], 'trend' => 'En catálogo', 'positive' => true, 'icon' => 'tag'],
            ['label' => 'Sin Stock', 'value' => (string) $inventory['out_of_stock'], 'trend' => $inventory['low_stock_alerts'].' alertas', 'positive' => $inventory['out_of_stock'] === 0, 'icon' => 'stock-alert'],
            ['label' => 'Total Medicamentos', 'value' => (string) $inventory['total_medicines'], 'trend' => $inventory['active_medicines'].' activos', 'positive' => true, 'icon' => 'medicine'],
        ];

        $realtimeKpis = [
            [
                'label' => 'Total de Medicamentos',
                'value' => (string) $inventory['total_medicines'],
                'subtitle' => "{$inventory['active_medicines']} activos · {$inventory['active_categories_count']} categorías",
                'trend' => "{$inventory['active_medicines']} disponibles",
                'positive' => true,
                'icon' => 'medicine',
                'href' => route('medicamentos.index'),
            ],
            [
                'label' => 'Alertas de Bajo Stock',
                'value' => (string) $inventory['low_stock_alerts'],
                'subtitle' => "{$inventory['out_of_stock']} agotados · {$inventory['expiring_soon']} por vencer",
                'trend' => $inventory['low_stock_alerts'] > 0 ? 'Requiere reposición' : 'Stock óptimo',
                'positive' => $inventory['low_stock_alerts'] === 0,
                'icon' => 'stock-alert',
                'href' => route('medicamentos.index', ['estado' => 'Stock Bajo']),
            ],
            [
                'label' => 'Productos en Catálogo',
                'value' => (string) $inventory['retail_products'],
                'subtitle' => "{$inventory['active_retail_products']} activos · {$inventory['retail_low_stock_alerts']} stock bajo",
                'trend' => "{$inventory['active_retail_products']} disponibles",
                'positive' => $inventory['retail_low_stock_alerts'] === 0,
                'icon' => 'cube',
                'href' => route('productos.index'),
            ],
            [
                'label' => 'Ventas de Hoy',
                'value' => $sales['kpis'][0]['value'],
                'subtitle' => "{$sales['kpis'][1]['value']} tickets · promedio {$sales['kpis'][2]['value']}",
                'trend' => $sales['kpis'][0]['trend'],
                'positive' => true,
                'icon' => 'chart-up',
                'href' => route('ventas.index'),
            ],
        ];

        return view('dashboard.index', [
            'dashboard' => $dashboard,
            'realtimeKpis' => $realtimeKpis,
            'inventory' => $inventory,
            'sales' => $sales,
        ]);
    }
}
