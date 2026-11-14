<?php

namespace App\Support;

class PharmacyMockData
{
    public static function navigation(): array
    {
        return [
            [
                'id' => 'menu',
                'title' => 'Menú',
                'items' => [
                    ['label' => 'Panel', 'route' => 'dashboard', 'href' => '/', 'icon' => 'dashboard'],
                    ['label' => 'Pedidos', 'route' => 'pedidos.index', 'href' => '/pedidos', 'icon' => 'orders'],
                    ['label' => 'Ventas', 'route' => 'ventas.index', 'href' => '/ventas', 'icon' => 'chart-up'],
                    ['label' => 'Productos', 'route' => 'productos.index', 'href' => '/productos', 'icon' => 'cube'],
                ],
            ],
            [
                'id' => 'otros',
                'title' => 'Otros',
                'items' => [
                    ['label' => 'Clientes', 'route' => 'clientes.index', 'href' => '/clientes', 'icon' => 'users'],
                    ['label' => 'Usuarios', 'route' => 'usuarios.index', 'href' => '/usuarios', 'icon' => 'users-role'],
                    ['label' => 'Categorías', 'route' => 'categorias.index', 'href' => '/categorias', 'icon' => 'tag'],
                    ['label' => 'Medicamentos', 'route' => 'medicamentos.index', 'href' => '/medicamentos', 'icon' => 'medicine'],
                    ['label' => 'Facturas', 'route' => 'facturas.index', 'href' => '/facturas', 'icon' => 'invoice'],
                ],
            ],
        ];
    }

    public static function dashboard(): array
    {
        return [
            'report_bars' => [
                'periods' => [
                    'Este Mes' => [
                        ['month' => 'Ene', 'sales' => 36, 'invoices' => 38],
                        ['month' => 'Feb', 'sales' => 19, 'invoices' => 64],
                        ['month' => 'Mar', 'sales' => 54, 'invoices' => 41],
                        ['month' => 'Abr', 'sales' => 19, 'invoices' => 68],
                        ['month' => 'May', 'sales' => 37, 'invoices' => 23],
                        ['month' => 'Jun', 'sales' => 33, 'invoices' => 49],
                        ['month' => 'Jul', 'sales' => 56, 'invoices' => 42],
                        ['month' => 'Ago', 'sales' => 45, 'invoices' => 50],
                        ['month' => 'Sep', 'sales' => 58, 'invoices' => 39],
                        ['month' => 'Oct', 'sales' => 34, 'invoices' => 58],
                        ['month' => 'Nov', 'sales' => 49, 'invoices' => 47],
                        ['month' => 'Dic', 'sales' => 19, 'invoices' => 32],
                    ],
                    'Último Trimestre' => [
                        ['month' => 'Ene', 'sales' => 42, 'invoices' => 31],
                        ['month' => 'Feb', 'sales' => 28, 'invoices' => 52],
                        ['month' => 'Mar', 'sales' => 61, 'invoices' => 35],
                        ['month' => 'Abr', 'sales' => 31, 'invoices' => 58],
                        ['month' => 'May', 'sales' => 44, 'invoices' => 29],
                        ['month' => 'Jun', 'sales' => 49, 'invoices' => 41],
                        ['month' => 'Jul', 'sales' => 53, 'invoices' => 37],
                        ['month' => 'Ago', 'sales' => 50, 'invoices' => 44],
                        ['month' => 'Sep', 'sales' => 62, 'invoices' => 33],
                        ['month' => 'Oct', 'sales' => 46, 'invoices' => 48],
                        ['month' => 'Nov', 'sales' => 54, 'invoices' => 39],
                        ['month' => 'Dic', 'sales' => 29, 'invoices' => 36],
                    ],
                    'Este Año' => [
                        ['month' => 'Ene', 'sales' => 48, 'invoices' => 34],
                        ['month' => 'Feb', 'sales' => 35, 'invoices' => 56],
                        ['month' => 'Mar', 'sales' => 58, 'invoices' => 39],
                        ['month' => 'Abr', 'sales' => 40, 'invoices' => 50],
                        ['month' => 'May', 'sales' => 49, 'invoices' => 31],
                        ['month' => 'Jun', 'sales' => 55, 'invoices' => 40],
                        ['month' => 'Jul', 'sales' => 64, 'invoices' => 33],
                        ['month' => 'Ago', 'sales' => 59, 'invoices' => 38],
                        ['month' => 'Sep', 'sales' => 66, 'invoices' => 30],
                        ['month' => 'Oct', 'sales' => 52, 'invoices' => 44],
                        ['month' => 'Nov', 'sales' => 60, 'invoices' => 36],
                        ['month' => 'Dic', 'sales' => 41, 'invoices' => 35],
                    ],
                ],
                'kpis' => [
                    ['label' => 'Categorías Activas', 'value' => '6', 'trend' => 'En catálogo', 'positive' => true, 'icon' => 'tag'],
                    ['label' => 'Sin Stock', 'value' => '103', 'trend' => '09%', 'positive' => false, 'icon' => 'stock-alert'],
                    ['label' => 'Total Medicamentos', 'value' => '150', 'trend' => '43%', 'positive' => true, 'icon' => 'medicine'],
                ],
            ],
            'top_medicines' => [
                'ticks' => array_merge(
                    array_fill(0, 10, '#095D52'),
                    array_fill(0, 6, '#0D9488'),
                    array_fill(0, 10, '#EA580C'),
                    array_fill(0, 8, '#52525B')
                ),
                'periods' => [
                    'Este Mes' => [
                        ['name' => 'Paracetamol', 'amount' => 54647.00, 'formatted' => '$54,647.00', 'color' => '#095D52', 'bar_pct' => 76, 'units' => 4120],
                        ['name' => 'Ibuprofeno', 'amount' => 12345.00, 'formatted' => '$12,345.00', 'color' => '#0D9488', 'bar_pct' => 48, 'units' => 2890],
                        ['name' => 'Amoxicilina', 'amount' => 16567.00, 'formatted' => '$16,567.00', 'color' => '#EA580C', 'bar_pct' => 64, 'units' => 1950],
                        ['name' => 'Metformina', 'amount' => 10412.00, 'formatted' => '$10,412.00', 'color' => '#52525B', 'bar_pct' => 22, 'units' => 1640],
                    ],
                    'Último Trimestre' => [
                        ['name' => 'Paracetamol', 'amount' => 148290.00, 'formatted' => '$148,290.00', 'color' => '#095D52', 'bar_pct' => 84, 'units' => 11250],
                        ['name' => 'Ibuprofeno', 'amount' => 41820.00, 'formatted' => '$41,820.00', 'color' => '#0D9488', 'bar_pct' => 52, 'units' => 8420],
                        ['name' => 'Amoxicilina', 'amount' => 53410.00, 'formatted' => '$53,410.00', 'color' => '#EA580C', 'bar_pct' => 68, 'units' => 5870],
                        ['name' => 'Metformina', 'amount' => 31900.00, 'formatted' => '$31,900.00', 'color' => '#52525B', 'bar_pct' => 34, 'units' => 4910],
                    ],
                    'Este Año' => [
                        ['name' => 'Paracetamol', 'amount' => 512400.00, 'formatted' => '$512,400.00', 'color' => '#095D52', 'bar_pct' => 88, 'units' => 39400],
                        ['name' => 'Ibuprofeno', 'amount' => 162100.00, 'formatted' => '$162,100.00', 'color' => '#0D9488', 'bar_pct' => 56, 'units' => 29800],
                        ['name' => 'Amoxicilina', 'amount' => 198350.00, 'formatted' => '$198,350.00', 'color' => '#EA580C', 'bar_pct' => 70, 'units' => 21400],
                        ['name' => 'Metformina', 'amount' => 118900.00, 'formatted' => '$118,900.00', 'color' => '#52525B', 'bar_pct' => 38, 'units' => 17650],
                    ],
                ],
            ],
            'revenue_vs_expense' => [
                'months' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                'y_labels' => ['20K', '15K', '10K', '5K', '0'],
                'periods' => [
                    'Mensual' => self::revenueExpenseSeries(1.0),
                    'Trimestral' => self::revenueExpenseSeries(1.08),
                    'Anual' => self::revenueExpenseSeries(1.16),
                ],
            ],
            'today_report' => [
                'title' => 'Reporte de Hoy',
                'subtitle' => 'Ganancia Total',
                'total_earning' => 536.00,
                'formatted_earning' => '$536.00',
                'trend' => '43%',
                'percentage' => 80,
                'segments' => [
                    ['label' => 'Venta Directa', 'color' => '#095D52', 'value' => 240.00, 'pct' => 45],
                    ['label' => 'Efectivo Recibido', 'color' => '#0D9488', 'value' => 96.00, 'pct' => 18],
                    ['label' => 'Recibido en Banco', 'color' => '#15803D', 'value' => 90.00, 'pct' => 17],
                    ['label' => 'Servicio Total', 'color' => '#EA580C', 'value' => 110.00, 'pct' => 20],
                ],
            ],
        ];
    }

    private static function revenueExpenseSeries(float $factor): array
    {
        $base = [
            ['label' => 'Ene S1', 'type' => 'expense', 'low' => 4.2, 'high' => 11.4, 'open' => 6.8, 'close' => 9.8, 'vol' => 22],
            ['label' => 'Ene S2', 'type' => 'revenue', 'low' => 8.1, 'high' => 15.8, 'open' => 9.5, 'close' => 14.2, 'vol' => 38],
            ['label' => 'Ene S3', 'type' => 'revenue', 'low' => 11.2, 'high' => 19.6, 'open' => 13.0, 'close' => 16.5, 'vol' => 46],
            ['label' => 'Feb S1', 'type' => 'revenue', 'low' => 8.5, 'high' => 14.4, 'open' => 13.6, 'close' => 10.2, 'vol' => 29],
            ['label' => 'Feb S2', 'type' => 'expense', 'low' => 7.0, 'high' => 13.2, 'open' => 12.1, 'close' => 9.2, 'vol' => 34],
            ['label' => 'Feb S3', 'type' => 'revenue', 'low' => 8.2, 'high' => 12.9, 'open' => 11.2, 'close' => 12.4, 'vol' => 24],
            ['label' => 'Mar S1', 'type' => 'revenue', 'low' => 1.8, 'high' => 12.5, 'open' => 10.8, 'close' => 4.9, 'vol' => 54],
            ['label' => 'Mar S2', 'type' => 'revenue', 'low' => 3.6, 'high' => 10.4, 'open' => 4.8, 'close' => 8.2, 'vol' => 31],
            ['label' => 'Mar S3', 'type' => 'revenue', 'low' => 2.5, 'high' => 9.2, 'open' => 3.5, 'close' => 6.4, 'vol' => 28],
            ['label' => 'Abr S1', 'type' => 'expense', 'low' => 3.8, 'high' => 7.6, 'open' => 6.8, 'close' => 5.6, 'vol' => 21],
            ['label' => 'Abr S2', 'type' => 'revenue', 'low' => 4.9, 'high' => 8.9, 'open' => 5.8, 'close' => 7.6, 'vol' => 26],
            ['label' => 'Abr S3', 'type' => 'expense', 'low' => 5.5, 'high' => 9.6, 'open' => 7.8, 'close' => 5.9, 'vol' => 33],
            ['label' => 'May S1', 'type' => 'expense', 'low' => 5.1, 'high' => 11.5, 'open' => 5.9, 'close' => 9.4, 'vol' => 41],
            ['label' => 'May S2', 'type' => 'revenue', 'low' => 8.8, 'high' => 15.6, 'open' => 9.8, 'close' => 14.5, 'vol' => 48],
            ['label' => 'May S3', 'type' => 'expense', 'low' => 7.6, 'high' => 16.4, 'open' => 15.2, 'close' => 9.2, 'vol' => 36],
            ['label' => 'Jun S1', 'type' => 'revenue', 'low' => 9.4, 'high' => 19.2, 'open' => 11.0, 'close' => 15.5, 'vol' => 52],
            ['label' => 'Jun S2', 'type' => 'expense', 'low' => 7.8, 'high' => 14.2, 'open' => 13.8, 'close' => 10.6, 'vol' => 29],
            ['label' => 'Jun S3', 'type' => 'revenue', 'low' => 11.8, 'high' => 16.8, 'open' => 15.4, 'close' => 13.2, 'vol' => 38],
            ['label' => 'Jul S1', 'type' => 'revenue', 'low' => 8.9, 'high' => 17.5, 'open' => 15.0, 'close' => 10.1, 'vol' => 34],
            ['label' => 'Jul S2', 'type' => 'revenue', 'low' => 5.2, 'high' => 14.0, 'open' => 10.3, 'close' => 6.8, 'vol' => 25],
            ['label' => 'Jul S3', 'type' => 'expense', 'low' => 3.5, 'high' => 12.6, 'open' => 10.1, 'close' => 5.4, 'vol' => 44],
            ['label' => 'Ago S1', 'type' => 'expense', 'low' => 6.2, 'high' => 15.4, 'open' => 11.0, 'close' => 13.5, 'vol' => 39],
            ['label' => 'Ago S2', 'type' => 'revenue', 'low' => 8.8, 'high' => 16.2, 'open' => 15.2, 'close' => 10.4, 'vol' => 31],
            ['label' => 'Ago S3', 'type' => 'expense', 'low' => 12.2, 'high' => 15.9, 'open' => 13.5, 'close' => 14.8, 'vol' => 22],
            ['label' => 'Sep S1', 'type' => 'revenue', 'low' => 7.5, 'high' => 17.8, 'open' => 15.4, 'close' => 9.1, 'vol' => 43],
            ['label' => 'Sep S2', 'type' => 'expense', 'low' => 8.4, 'high' => 12.1, 'open' => 9.3, 'close' => 11.0, 'vol' => 27],
            ['label' => 'Sep S3', 'type' => 'revenue', 'low' => 9.6, 'high' => 13.8, 'open' => 10.6, 'close' => 12.9, 'vol' => 32],
            ['label' => 'Oct S1', 'type' => 'revenue', 'low' => 10.2, 'high' => 14.5, 'open' => 13.2, 'close' => 11.8, 'vol' => 26],
            ['label' => 'Oct S2', 'type' => 'expense', 'low' => 9.8, 'high' => 13.4, 'open' => 11.6, 'close' => 10.8, 'vol' => 23],
            ['label' => 'Oct S3', 'type' => 'revenue', 'low' => 10.4, 'high' => 15.2, 'open' => 12.6, 'close' => 13.6, 'vol' => 35],
            ['label' => 'Nov S1', 'type' => 'expense', 'low' => 8.9, 'high' => 14.8, 'open' => 12.4, 'close' => 9.6, 'vol' => 30],
            ['label' => 'Nov S2', 'type' => 'revenue', 'low' => 10.5, 'high' => 16.5, 'open' => 14.5, 'close' => 11.8, 'vol' => 37],
            ['label' => 'Nov S3', 'type' => 'revenue', 'low' => 6.4, 'high' => 16.0, 'open' => 14.9, 'close' => 8.3, 'vol' => 45],
            ['label' => 'Dic S1', 'type' => 'expense', 'low' => 4.2, 'high' => 15.4, 'open' => 8.0, 'close' => 13.4, 'vol' => 49],
            ['label' => 'Dic S2', 'type' => 'revenue', 'low' => 5.6, 'high' => 14.1, 'open' => 13.8, 'close' => 8.2, 'vol' => 33],
        ];

        return array_map(static function (array $point) use ($factor): array {
            $cap = static fn (float $v): float => min(19.8, round($v * $factor, 2));

            return [
                'label' => $point['label'],
                'type' => $point['type'],
                'low' => $cap($point['low']),
                'high' => $cap($point['high']),
                'open' => $cap($point['open']),
                'close' => $cap($point['close']),
                'vol' => min(60, (int) round($point['vol'] * $factor)),
            ];
        }, $base);
    }

    public static function sales(): array
    {
        return [
            'kpis' => [
                ['label' => 'Ventas de Hoy', 'value' => '$2,845.50', 'trend' => '21%', 'positive' => true, 'icon' => 'chart-up'],
                ['label' => 'Tickets Emitidos', 'value' => '86', 'trend' => '14%', 'positive' => true, 'icon' => 'invoice'],
                ['label' => 'Ticket Promedio', 'value' => '$33.09', 'trend' => '06%', 'positive' => true, 'icon' => 'orders'],
                ['label' => 'Devoluciones', 'value' => '2', 'trend' => '03%', 'positive' => false, 'icon' => 'stock-alert'],
            ],
            'payment_mix' => [
                ['method' => 'Tarjeta', 'amount' => 1320.00, 'pct' => 46, 'color' => '#095D52'],
                ['method' => 'Efectivo', 'amount' => 980.50, 'pct' => 35, 'color' => '#15803D'],
                ['method' => 'Transferencia', 'amount' => 545.00, 'pct' => 19, 'color' => '#EA580C'],
            ],
            'items' => [
                ['code' => '#V-00418', 'date' => '26 Feb, 2025', 'customer' => 'Oscar Mendoza', 'items_count' => 3, 'method' => 'Tarjeta', 'total' => 29.50, 'status' => 'Completada'],
                ['code' => '#V-00417', 'date' => '26 Feb, 2025', 'customer' => 'Valeria Castillo', 'items_count' => 5, 'method' => 'Efectivo', 'total' => 64.20, 'status' => 'Completada'],
                ['code' => '#V-00416', 'date' => '25 Feb, 2025', 'customer' => 'Carlos Mejía', 'items_count' => 2, 'method' => 'Transferencia', 'total' => 48.00, 'status' => 'Pendiente'],
                ['code' => '#V-00415', 'date' => '25 Feb, 2025', 'customer' => 'Sofía Hernández', 'items_count' => 4, 'method' => 'Tarjeta', 'total' => 83.75, 'status' => 'Completada'],
                ['code' => '#V-00414', 'date' => '24 Feb, 2025', 'customer' => 'Mostrador General', 'items_count' => 1, 'method' => 'Efectivo', 'total' => 12.50, 'status' => 'Completada'],
                ['code' => '#V-00413', 'date' => '24 Feb, 2025', 'customer' => 'Andrés Morales', 'items_count' => 2, 'method' => 'Tarjeta', 'total' => 19.90, 'status' => 'Reembolsada'],
            ],
        ];
    }

    public static function products(): array
    {
        return [
            'categories' => ['Todos', 'Medicamentos', 'Dermocosmética', 'Vitaminas', 'Cuidado Personal', 'Bebé', 'Fragancias'],
            'items' => [
                ['codigo' => 'MED-00101', 'name' => 'Acetaminofén 500mg (Caja x 20 tabletas)', 'category' => 'Medicamentos', 'brand' => 'Laboratorios MK', 'price' => 3.50, 'stock' => 85, 'is_active' => true, 'description' => 'Alivio sintomático del dolor leve a moderado y estados febriles.'],
                ['codigo' => 'DER-00205', 'name' => 'Protector Solar Facial FPS 50+ Oil Control', 'category' => 'Dermocosmética', 'brand' => 'Eucerin', 'price' => 22.50, 'stock' => 35, 'is_active' => true, 'description' => 'Protector solar toque seco para piel grasa, presentación 50ml.'],
                ['codigo' => 'VIT-00310', 'name' => 'Vitamina C + Zinc 1000mg Efervescente', 'category' => 'Vitaminas', 'brand' => 'Bayer Salud', 'price' => 11.75, 'stock' => 62, 'is_active' => true, 'description' => 'Suplemento antioxidante diario sabor naranja (tubo x 15).'],
                ['codigo' => 'PER-00412', 'name' => 'Gel Limpiador Facial Purificante 200ml', 'category' => 'Cuidado Personal', 'brand' => 'La Roche-Posay', 'price' => 18.90, 'stock' => 14, 'is_active' => true, 'description' => 'Limpieza suave sin jabón para piel sensible y mixta.'],
                ['codigo' => 'BEB-00504', 'name' => 'Crema Protectora Pañalitis 110g', 'category' => 'Bebé', 'brand' => 'Mustela', 'price' => 13.40, 'stock' => 0, 'is_active' => false, 'description' => 'Prevención diaria de irritaciones con óxido de zinc.'],
                ['codigo' => 'FRA-00608', 'name' => 'Agua Micelar Termal Suave 400ml', 'category' => 'Fragancias', 'brand' => 'Bioderma', 'price' => 21.00, 'stock' => 27, 'is_active' => true, 'description' => 'Loción dermolimpiadora refrescante de alta tolerancia.'],
            ],
        ];
    }

    public static function customers(): array
    {
        return [
            'kpis' => [
                ['label' => 'Clientes Registrados', 'value' => '1,482', 'trend' => '28%', 'positive' => true, 'icon' => 'users'],
                ['label' => 'Activos App Móvil', 'value' => '1,340', 'trend' => '19%', 'positive' => true, 'icon' => 'orders'],
                ['label' => 'Clientes Frecuentes', 'value' => '312', 'trend' => '11%', 'positive' => true, 'icon' => 'chart-up'],
            ],
            'items' => [
                ['name' => 'Oscar Mendoza', 'email' => 'oscar.mendoza@correo.com', 'phone' => '7890-1234', 'address' => 'Colonia Escalón, Av. Olímpica #45, San Salvador', 'orders_count' => 12, 'total_spent' => 418.50, 'is_active' => true, 'last_order' => '26 Feb, 2025'],
                ['name' => 'Valeria Castillo', 'email' => 'valeria.castillo@correo.com', 'phone' => '7452-8891', 'address' => 'Residencial Santa Elena, Calle Conchagua #18', 'orders_count' => 19, 'total_spent' => 792.40, 'is_active' => true, 'last_order' => '26 Feb, 2025'],
                ['name' => 'Carlos Mejía', 'email' => 'carlos.mejia@correo.com', 'phone' => '7120-3345', 'address' => 'Colonia San Benito, Blvd. del Hipódromo #112', 'orders_count' => 5, 'total_spent' => 164.00, 'is_active' => true, 'last_order' => '25 Feb, 2025'],
                ['name' => 'Sofía Hernández', 'email' => 'sofia.hernandez@correo.com', 'phone' => '7684-9902', 'address' => 'Antiguo Cuscatlán, Jardines de Guadalupe #9', 'orders_count' => 8, 'total_spent' => 305.75, 'is_active' => true, 'last_order' => '25 Feb, 2025'],
                ['name' => 'Andrés Morales', 'email' => 'andres.morales@correo.com', 'phone' => '7901-5520', 'address' => 'Soyapango, Urbanización Prados de Venecia #32', 'orders_count' => 2, 'total_spent' => 49.90, 'is_active' => false, 'last_order' => '14 Feb, 2025'],
            ],
        ];
    }

    public static function medicines(): array
    {
        return [
            'kpis' => [
                ['label' => 'Total Medicamentos', 'value' => '150', 'trend' => '43%', 'positive' => true, 'icon' => 'medicine'],
                ['label' => 'Sin Stock', 'value' => '103', 'trend' => '09%', 'positive' => false, 'icon' => 'stock-alert'],
                ['label' => 'Por Vencer (30d)', 'value' => '14', 'trend' => '04%', 'positive' => false, 'icon' => 'invoice'],
            ],
            'items' => [
                ['codigo' => 'MED-00101', 'name' => 'Paracetamol 500mg (Caja x 20)', 'active' => 'Paracetamol', 'category' => 'Medicamentos', 'brand' => 'Laboratorios MK', 'batch' => 'LOT-25A09', 'expiry' => 'Nov 2026', 'expiry_status' => 'Vigente', 'prescription' => 'Venta Libre', 'price' => 3.50, 'stock' => 85, 'is_active' => true],
                ['codigo' => 'MED-00102', 'name' => 'Ibuprofeno 400mg (Blíster x 10)', 'active' => 'Ibuprofeno', 'category' => 'Medicamentos', 'brand' => 'Square', 'batch' => 'LOT-24K18', 'expiry' => 'Mar 2025', 'expiry_status' => 'Por Vencer', 'prescription' => 'Venta Libre', 'price' => 4.25, 'stock' => 42, 'is_active' => true],
                ['codigo' => 'MED-00103', 'name' => 'Amoxicilina 500mg (Cápsulas x 12)', 'active' => 'Amoxicilina', 'category' => 'Medicamentos', 'brand' => 'Beximco', 'batch' => 'LOT-25B02', 'expiry' => 'Ago 2026', 'expiry_status' => 'Vigente', 'prescription' => 'Con Receta', 'price' => 9.80, 'stock' => 28, 'is_active' => true],
                ['codigo' => 'MED-00104', 'name' => 'Metformina 850mg (Tabletas x 30)', 'active' => 'Metformina', 'category' => 'Medicamentos', 'brand' => 'Renata', 'batch' => 'LOT-24H14', 'expiry' => 'Ene 2026', 'expiry_status' => 'Vigente', 'prescription' => 'Con Receta', 'price' => 14.50, 'stock' => 0, 'is_active' => false],
                ['codigo' => 'MED-00105', 'name' => 'Acetaminofén 500mg Pediátrico Jarabe', 'active' => 'Paracetamol', 'category' => 'Medicamentos', 'brand' => 'Laboratorios MK', 'batch' => 'LOT-24M03', 'expiry' => 'Abr 2025', 'expiry_status' => 'Por Vencer', 'prescription' => 'Venta Libre', 'price' => 6.10, 'stock' => 19, 'is_active' => true],
            ],
        ];
    }

    public static function invoices(): array
    {
        return [
            'kpis' => [
                ['label' => 'Facturado del Mes', 'value' => '$28,490.00', 'trend' => '19%', 'positive' => true, 'icon' => 'invoice'],
                ['label' => 'Facturas Pagadas', 'value' => '142', 'trend' => '15%', 'positive' => true, 'icon' => 'chart-up'],
                ['label' => 'Por Cobrar', 'value' => '$1,840.25', 'trend' => '05%', 'positive' => false, 'icon' => 'stock-alert'],
            ],
            'items' => [
                ['number' => '#FAC-0985', 'order_number' => 'LM-2026-00501', 'customer' => 'Oscar Mendoza', 'date' => '26 Feb, 2025', 'subtotal' => 26.11, 'shipping' => 0.00, 'tax' => 3.39, 'total' => 29.50, 'status' => 'Pagada'],
                ['number' => '#FAC-0984', 'order_number' => 'LM-2026-00500', 'customer' => 'Valeria Castillo', 'date' => '26 Feb, 2025', 'subtotal' => 56.81, 'shipping' => 0.00, 'tax' => 7.39, 'total' => 64.20, 'status' => 'Pagada'],
                ['number' => '#FAC-0983', 'order_number' => 'LM-2026-00499', 'customer' => 'Carlos Mejía', 'date' => '25 Feb, 2025', 'subtotal' => 42.48, 'shipping' => 2.50, 'tax' => 5.52, 'total' => 50.50, 'status' => 'Pendiente'],
                ['number' => '#FAC-0982', 'order_number' => 'LM-2026-00498', 'customer' => 'Sofía Hernández', 'date' => '24 Feb, 2025', 'subtotal' => 74.12, 'shipping' => 0.00, 'tax' => 9.63, 'total' => 83.75, 'status' => 'Pagada'],
                ['number' => '#FAC-0981', 'order_number' => 'LM-2026-00496', 'customer' => 'Andrés Morales', 'date' => '15 Feb, 2025', 'subtotal' => 17.61, 'shipping' => 2.50, 'tax' => 2.29, 'total' => 22.40, 'status' => 'Vencida'],
            ],
        ];
    }

    public static function plans(): array
    {
        return [
            [
                'name' => 'Plan Básico',
                'monthly' => 29,
                'annual' => 24,
                'description' => 'Control esencial de inventario, ventas y facturación básica.',
                'featured' => false,
                'features' => [
                    'Hasta 500 medicamentos en catálogo',
                    'Control de categorías e inventario',
                    'Gestión de ventas y clientes',
                    'Reportes mensuales estándar',
                ],
            ],
            [
                'name' => 'Plan Pro LifeMedic',
                'monthly' => 59,
                'annual' => 49,
                'description' => 'Domina tu farmacia con analíticas detalladas y gráficos claros en tiempo real.',
                'featured' => true,
                'features' => [
                    'Medicamentos y sucursales ilimitados',
                    'Alertas automáticas de vencimiento y stock crítico',
                    'Analítica avanzada de ingresos, gastos y rentabilidad',
                    'Exportación de facturas y soporte farmacéutico prioritario',
                ],
            ],
        ];
    }
}
