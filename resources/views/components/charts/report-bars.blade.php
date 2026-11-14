@props(['data'])

<x-card class="h-full flex flex-col justify-between">
    <div
        class="h-full flex flex-col justify-between"
        x-data="{
            period: 'Este Mes',
            periods: {{ \Illuminate\Support\Js::from($data['periods']) }},
            hoveredIndex: null,
            get bars() {
                return this.periods[this.period] ?? [];
            }
        }"
    >
        <div class="mb-4 flex items-center justify-between gap-2">
            <div>
                <h2 class="text-[15px] font-semibold tracking-tight text-zinc-950">Reporte Gráfico de Hoy</h2>
                <div class="mt-1.5 flex items-center gap-4 text-[11.5px] font-medium text-zinc-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-[2px] bg-[#095d52]"></span>
                        <span>Ventas</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-[2px] bg-[#f97316]"></span>
                        <span>Facturas</span>
                    </span>
                </div>
            </div>

            <select
                x-model="period"
                aria-label="Seleccionar período del reporte gráfico"
                class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 focus:border-[#095d52] focus:outline-none cursor-pointer"
            >
                <option value="Este Mes">Este Mes</option>
                <option value="Último Trimestre">Último Trimestre</option>
                <option value="Este Año">Este Año</option>
            </select>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-12 items-stretch">
            {{-- Gráfica de 12 barras segmentadas (Ventas verde aqua + Facturas naranja) --}}
            <div class="sm:col-span-7 flex flex-col justify-end relative pt-4">
                <div class="relative h-[182px] flex items-end justify-between gap-1.5 px-0.5">
                    <template x-for="(bar, idx) in bars" :key="bar.month">
                        <div
                            class="relative flex-1 h-full flex flex-col items-center justify-end group"
                            @mouseenter="hoveredIndex = idx"
                            @mouseleave="hoveredIndex = null"
                        >
                            {{-- Tooltip flotante --}}
                            <div
                                x-cloak
                                x-show="hoveredIndex === idx"
                                x-transition.opacity.duration.100ms
                                class="pointer-events-none absolute -top-11 left-1/2 -translate-x-1/2 z-20 whitespace-nowrap rounded-[6px] bg-zinc-950 px-2.5 py-1.5 text-[11px] font-medium text-white shadow-md tabular-nums"
                            >
                                <span class="font-semibold" x-text="bar.month"></span>:
                                <span class="text-[#5eead4]">Ventas <span x-text="bar.sales"></span>k</span> ·
                                <span class="text-orange-200">Facturas <span x-text="bar.invoices"></span>k</span>
                            </div>

                            {{-- Barras segmentadas limpias con brecha de 3px --}}
                            <div class="w-full flex flex-col items-center justify-end h-[156px] gap-[3px]">
                                <div
                                    class="w-[8px] sm:w-[9px] rounded-[3px] bg-[#ea580c] transition-opacity duration-150 group-hover:opacity-85"
                                    :style="`height: ${Math.max(12, bar.invoices * 1.45)}px`"
                                ></div>
                                <div
                                    class="w-[8px] sm:w-[9px] rounded-[3px] bg-[#095d52] transition-opacity duration-150 group-hover:opacity-85"
                                    :style="`height: ${Math.max(12, bar.sales * 1.45)}px`"
                                ></div>
                            </div>

                            <span class="mt-2.5 text-[11px] font-medium text-zinc-400" x-text="bar.month"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3 Mini KPIs apiladas a la derecha --}}
            <div class="sm:col-span-5 flex flex-col justify-between gap-2.5">
                @foreach ($data['kpis'] as $kpi)
                    <x-kpi-mini
                        :label="$kpi['label']"
                        :value="$kpi['value']"
                        :trend="$kpi['trend']"
                        :positive="$kpi['positive']"
                        :icon="$kpi['icon']"
                    />
                @endforeach
            </div>
        </div>
    </div>
</x-card>
