@props(['data'])

<x-card class="h-full flex flex-col justify-between">
    <div
        class="h-full flex flex-col justify-between"
        x-data="{
            period: 'Mensual',
            periods: {{ \Illuminate\Support\Js::from($data['periods']) }},
            months: {{ \Illuminate\Support\Js::from($data['months']) }},
            hoveredIdx: null,
            get points() {
                return this.periods[this.period] ?? [];
            },
            clampPct(val) {
                return Math.min(100, Math.max(0, (val / 20) * 100));
            }
        }"
    >
        <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
            <div>
                <h2 class="text-[15px] font-semibold tracking-tight text-zinc-950">Ingresos vs Gastos Totales</h2>

                <div class="mt-2 flex items-center gap-5 text-xs font-medium text-zinc-600">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full border-[2.5px] border-[#095d52] bg-white"></span>
                        <span>Ingresos</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full border-[2.5px] border-[#f97316] bg-white"></span>
                        <span>Gastos</span>
                    </span>
                </div>
            </div>

            <select
                x-model="period"
                aria-label="Seleccionar frecuencia de ingresos vs gastos"
                class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 focus:border-[#095d52] focus:outline-none cursor-pointer"
            >
                <option value="Mensual">Mensual</option>
                <option value="Trimestral">Trimestral</option>
                <option value="Anual">Anual</option>
            </select>
        </div>

        <div class="relative w-full">
            {{-- Tooltip flotante --}}
            <div
                x-cloak
                x-show="hoveredIdx !== null"
                class="pointer-events-none absolute right-1 top-0 z-20 rounded-[6px] border border-zinc-200 bg-white px-3 py-1.5 text-xs shadow-sm"
            >
                <template x-if="hoveredIdx !== null && points[hoveredIdx]">
                    <div class="flex items-center gap-2">
                        <span
                            class="h-2 w-2 rounded-[2px]"
                            :class="points[hoveredIdx].type === 'revenue' ? 'bg-[#095d52]' : 'bg-[#ea580c]'"
                        ></span>
                        <span class="font-semibold text-zinc-950" x-text="points[hoveredIdx].label"></span>
                        <span class="text-zinc-600 tabular-nums">
                            <span x-text="points[hoveredIdx].type === 'revenue' ? 'Ingresos' : 'Gastos'"></span>:
                            $<span x-text="points[hoveredIdx].high"></span>K
                            (Mín $<span x-text="points[hoveredIdx].low"></span>K)
                        </span>
                    </div>
                </template>
            </div>

            {{-- Contenedor flex: Eje Y pegado a la izquierda de la card + Área gráfica a todo lo ancho --}}
            <div class="flex items-stretch gap-2.5 w-full">
                {{-- Eje Y pegado a la izquierda (20K, 15K, 10K, 5K, 0) --}}
                <div class="w-8 shrink-0 h-[168px] flex flex-col justify-between text-left text-[11px] font-medium text-zinc-400 tabular-nums leading-none">
                    @foreach ($data['y_labels'] as $yLabel)
                        <span>{{ $yLabel }}</span>
                    @endforeach
                </div>

                {{-- Área de trazado + Eje X Ene-Dic --}}
                <div class="flex-1 min-w-0">
                    <div class="relative h-[168px] w-full">
                        {{-- 5 Líneas guía horizontales de extremo a extremo --}}
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                            @foreach ($data['y_labels'] as $lineIdx => $unused)
                                <div class="w-full border-b border-zinc-200/70"></div>
                            @endforeach
                        </div>

                        {{-- Barras tenues de volumen + Velas/tallos OHLC de Ingresos/Gastos --}}
                        <div class="relative z-10 flex h-full w-full items-end justify-between gap-[2px] px-0.5">
                            <template x-for="(pt, idx) in points" :key="`${period}-${idx}`">
                                <div
                                    class="relative h-full flex-1 flex items-end justify-center cursor-pointer"
                                    @mouseenter="hoveredIdx = idx"
                                    @mouseleave="hoveredIdx = null"
                                >
                                    {{-- Histograma tenue en la base --}}
                                    <div
                                        class="w-[6px] sm:w-[7px] rounded-t-[2px] bg-zinc-100 transition-colors"
                                        :class="hoveredIdx === idx ? 'bg-zinc-200' : ''"
                                        :style="`height: ${Math.min(48, Math.max(8, (pt.vol / 42) * 100))}%`"
                                    ></div>

                                    {{-- Tallo vertical principal --}}
                                    <div
                                        class="absolute left-1/2 -translate-x-1/2 rounded-[2px] transition-opacity duration-150"
                                        :style="`bottom: ${clampPct(pt.low)}%; height: ${Math.max(4, clampPct(pt.high) - clampPct(pt.low))}%; width: ${hoveredIdx === idx ? 2.5 : 1.75}px; background-color: ${pt.type === 'revenue' ? '#095d52' : '#ea580c'};`"
                                    ></div>

                                    {{-- Marca horizontal izquierda (apertura) --}}
                                    <div
                                        class="absolute rounded-[1px]"
                                        :style="`bottom: ${clampPct(pt.open)}%; right: 50%; width: 5px; height: ${hoveredIdx === idx ? 2.2 : 1.6}px; background-color: ${pt.type === 'revenue' ? '#095d52' : '#ea580c'};`"
                                    ></div>

                                    {{-- Marca horizontal derecha (cierre) --}}
                                    <div
                                        class="absolute rounded-[1px]"
                                        :style="`bottom: ${clampPct(pt.close)}%; left: 50%; width: 5px; height: ${hoveredIdx === idx ? 2.2 : 1.6}px; background-color: ${pt.type === 'revenue' ? '#095d52' : '#ea580c'};`"
                                    ></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Meses Ene–Dic alineados a todo el ancho --}}
                    <div class="mt-2.5 flex items-center justify-between text-[11px] font-medium text-zinc-400">
                        @foreach ($data['months'] as $month)
                            <span>{{ $month }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-card>
