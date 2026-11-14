<x-layouts.app title="Panel" :hideDesktopHeader="true">
    <div class="space-y-4">
        {{-- Encabezado operativo con acciones rápidas Verde Aqua --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-[22px] font-semibold tracking-tight text-zinc-950">Panel Administrativo</h1>
                <p class="mt-0.5 text-xs text-zinc-500">Indicadores en tiempo real de inventario, alertas de stock, categorías y ventas.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('categorias.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-[6px] border border-zinc-200 bg-white px-3.5 py-2 text-xs font-medium text-zinc-800 transition-colors hover:bg-zinc-50"
                >
                    <x-icon name="tag" class="w-3.5 h-3.5 text-[#095d52]" />
                    <span>Categorías</span>
                </a>
                <a
                    href="{{ route('medicamentos.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-[6px] bg-[#095d52] px-3.5 py-2 text-xs font-medium text-white transition-colors hover:bg-[#064940]"
                >
                    <x-icon name="plus" class="w-3.5 h-3.5" />
                    <span>Gestionar Medicamentos</span>
                </a>
            </div>
        </div>

        {{-- Fila KPIs en Tiempo Real --}}
        <div data-realtime-kpis="true" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($realtimeKpis as $kpi)
                <a href="{{ $kpi['href'] }}" class="group block focus:outline-none">
                    <x-card padding="p-4" class="h-full transition-colors group-hover:border-[#095d52]/40">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-500">{{ $kpi['label'] }}</p>
                                <p class="mt-1 text-[24px] font-semibold tracking-tight text-zinc-950 tabular-nums leading-tight">{{ $kpi['value'] }}</p>
                            </div>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] border border-zinc-200 bg-zinc-50 text-[#095d52] transition-colors group-hover:bg-[#095d52] group-hover:border-[#095d52] group-hover:text-white">
                                <x-icon :name="$kpi['icon']" class="w-4 h-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2 border-t border-zinc-100 pt-2.5 text-[11px]">
                            <span class="truncate text-zinc-500">{{ $kpi['subtitle'] }}</span>
                            <span @class([
                                'shrink-0 font-semibold',
                                'text-[#095d52]' => $kpi['positive'],
                                'text-[#c2410c]' => ! $kpi['positive'],
                            ])>
                                {{ $kpi['trend'] }}
                            </span>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>

        {{-- Fila Superior: Reporte Gráfico de Hoy (7 cols) + Medicamentos Más Vendidos (5 cols) --}}
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <div class="xl:col-span-7">
                <x-charts.report-bars :data="$dashboard['report_bars']" />
            </div>

            <div class="xl:col-span-5">
                <x-charts.top-medicines :data="$dashboard['top_medicines']" />
            </div>
        </div>

        {{-- Fila Media: Ingresos vs Gastos Totales (8 cols) + Reporte de Hoy (4 cols) --}}
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <div class="xl:col-span-8">
                <x-charts.revenue-expense :data="$dashboard['revenue_vs_expense']" />
            </div>

            <div class="xl:col-span-4">
                <x-charts.today-donut :data="$dashboard['today_report']" />
            </div>
        </div>
    </div>
</x-layouts.app>
