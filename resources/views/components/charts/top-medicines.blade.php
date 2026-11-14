@props(['data'])

<x-card class="h-full flex flex-col justify-between">
    <div
        class="h-full flex flex-col justify-between"
        x-data="{
            period: 'Este Mes',
            periods: {{ \Illuminate\Support\Js::from($data['periods']) }},
            hoveredRow: null,
            get rows() {
                return this.periods[this.period] ?? [];
            }
        }"
    >
        <div>
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="text-[15px] font-semibold tracking-tight text-zinc-950">Medicamentos Más Vendidos</h2>

                <select
                    x-model="period"
                    aria-label="Seleccionar período de medicamentos más vendidos"
                    class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 focus:border-[#095d52] focus:outline-none cursor-pointer"
                >
                    <option value="Este Mes">Este Mes</option>
                    <option value="Último Trimestre">Último Trimestre</option>
                    <option value="Este Año">Este Año</option>
                </select>
            </div>

            {{-- Tira horizontal de cápsulas 3D agrupadas por color --}}
            <div class="mb-5 grid grid-cols-[repeat(34,minmax(0,1fr))] gap-[3.5px] sm:gap-[4px] h-8 items-center" aria-hidden="true">
                @foreach ($data['ticks'] as $color)
                    <span
                        class="h-full w-full rounded-full transition-opacity duration-150"
                        style="background: linear-gradient(180deg, rgba(255,255,255,0.44) 0%, rgba(255,255,255,0.08) 26%, rgba(0,0,0,0) 72%, rgba(0,0,0,0.08) 100%), linear-gradient(90deg, rgba(255,255,255,0.20) 0%, rgba(255,255,255,0.46) 28%, rgba(255,255,255,0.06) 54%, rgba(0,0,0,0) 78%, rgba(0,0,0,0.07) 100%), {{ $color }}; box-shadow: inset 0 1px 1px rgba(255,255,255,0.6), inset 0 -1px 1px rgba(0,0,0,0.06), 0 1px 2px rgba(9,9,11,0.06);"
                        :class="hoveredRow && hoveredRow !== '{{ $color }}' ? 'opacity-25' : 'opacity-100'"
                    ></span>
                @endforeach
            </div>
        </div>

        {{-- 4 filas de medicamentos con barra proporcional --}}
        <div class="space-y-3.5">
            <template x-for="med in rows" :key="med.name">
                <div
                    class="flex items-center gap-3 group"
                    @mouseenter="hoveredRow = med.color"
                    @mouseleave="hoveredRow = null"
                >
                    <div class="flex items-center gap-2 w-28 shrink-0">
                        <span
                            class="h-2.5 w-2.5 rounded-full shrink-0"
                            :style="`background: linear-gradient(145deg, rgba(255,255,255,0.44) 0%, rgba(0,0,0,0) 68%, rgba(0,0,0,0.08) 100%), ${med.color}; box-shadow: inset 0 1px 1px rgba(255,255,255,0.45);`"
                        ></span>
                        <span class="truncate text-xs font-medium text-zinc-600 transition-colors group-hover:text-zinc-950" x-text="med.name"></span>
                    </div>

                    <div class="flex-1 flex items-center">
                        <div
                            class="h-[7px] rounded-full transition-opacity duration-150"
                            :style="`width: ${med.bar_pct}%; background: linear-gradient(180deg, rgba(255,255,255,0.42) 0%, rgba(255,255,255,0.10) 42%, rgba(0,0,0,0) 76%, rgba(0,0,0,0.08) 100%), ${med.color}; box-shadow: inset 0 1px 0 rgba(255,255,255,0.5), 0 1px 1px rgba(9,9,11,0.05);`"
                            :title="`${med.name}: ${med.units.toLocaleString()} unidades`"
                        ></div>
                    </div>

                    <span class="w-24 text-right text-xs font-semibold text-zinc-950 tabular-nums" x-text="med.formatted"></span>
                </div>
            </template>
        </div>
    </div>
</x-card>
