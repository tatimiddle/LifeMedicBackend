@props(['data'])

@php
    $ringRadius = 69;
    $cx = 100;
    $cy = 100;
    $cursorDeg = -155.0;
    $arcPaths = [];

    foreach ($data['segments'] as $seg) {
        $sweep = ($seg['pct'] / 100) * 360.0;
        $startDeg = $cursorDeg;
        $endDeg = $cursorDeg + $sweep;

        $startRad = deg2rad($startDeg);
        $endRad = deg2rad($endDeg);

        $x1 = $cx + $ringRadius * sin($startRad);
        $y1 = $cy - $ringRadius * cos($startRad);
        $x2 = $cx + $ringRadius * sin($endRad);
        $y2 = $cy - $ringRadius * cos($endRad);
        $largeArc = $sweep > 180 ? 1 : 0;

        $arcPaths[] = [
            'label' => $seg['label'],
            'color' => $seg['color'],
            'value' => $seg['value'],
            'pct' => $seg['pct'],
            'd' => sprintf('M %.2f %.2f A %d %d 0 %d 1 %.2f %.2f', $x1, $y1, $ringRadius, $ringRadius, $largeArc, $x2, $y2),
            'nodeX' => round($x1, 2),
            'nodeY' => round($y1, 2),
        ];

        $cursorDeg = $endDeg;
    }

    $ticks = [];
    for ($deg = 0; $deg < 360; $deg += 5) {
        $rad = deg2rad($deg);
        $ticks[] = [
            'x1' => round($cx + 83 * sin($rad), 2),
            'y1' => round($cy - 83 * cos($rad), 2),
            'x2' => round($cx + 90 * sin($rad), 2),
            'y2' => round($cy - 90 * cos($rad), 2),
        ];
    }
@endphp

<x-card class="h-full flex flex-col justify-between">
    <div x-data="{ hoveredSegment: null, menuOpen: false }">
        <div class="flex items-center justify-between gap-2 relative">
            <h2 class="text-[15px] font-semibold tracking-tight text-zinc-950">{{ $data['title'] }}</h2>

            <div class="relative" @click.outside="menuOpen = false">
                <button
                    type="button"
                    @click="menuOpen = !menuOpen"
                    aria-label="Opciones del reporte de hoy"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900 cursor-pointer"
                >
                    <x-icon name="dots" class="w-4 h-4" />
                </button>

                <div
                    x-cloak
                    x-show="menuOpen"
                    x-transition.opacity.duration.100ms
                    class="absolute right-0 mt-1.5 w-44 rounded-[6px] border border-zinc-200 bg-white py-1 text-xs shadow-md z-20"
                >
                    <button
                        type="button"
                        @click="menuOpen = false; $dispatch('notify', { message: 'Reporte diario actualizado.', type: 'info' })"
                        class="block w-full text-left px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer"
                    >
                        Actualizar cifras
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-2.5 grid grid-cols-1 sm:grid-cols-12 items-center gap-3">
            {{-- Columna izquierda: Ganancia Total + KPI + Lista de 4 anillos huecos --}}
            <div class="sm:col-span-5">
                <p class="text-xs font-medium text-zinc-500">{{ $data['subtitle'] }}</p>
                <div class="mt-0.5 flex items-baseline gap-2">
                    <span class="text-[22px] font-semibold tracking-tight text-zinc-950 tabular-nums">{{ $data['formatted_earning'] }}</span>
                    <span class="inline-flex items-center gap-0.5 text-xs font-medium text-[#15803d] tabular-nums">
                        <x-icon name="trend-up" class="w-3.5 h-3.5" />
                        {{ $data['trend'] }}
                    </span>
                </div>

                <ul class="mt-4 space-y-2.5">
                    @foreach ($arcPaths as $arc)
                        <li
                            class="flex items-center gap-2.5 text-xs font-medium text-zinc-600 transition-colors cursor-default"
                            @mouseenter="hoveredSegment = '{{ $arc['label'] }}'"
                            @mouseleave="hoveredSegment = null"
                            :class="hoveredSegment === '{{ $arc['label'] }}' ? 'text-zinc-950 font-semibold' : ''"
                        >
                            <span
                                class="h-3 w-3 shrink-0 rounded-full border-[3px] bg-white"
                                style="border-color: {{ $arc['color'] }};"
                            ></span>
                            <span class="truncate">{{ $arc['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Columna derecha: Medidor radial con disco azul sólido plano --}}
            <div class="sm:col-span-7 flex flex-col items-center justify-center relative">
                <div class="relative flex h-[190px] w-[190px] items-center justify-center">
                    <svg viewBox="0 0 200 200" class="w-full h-full">
                        {{-- Anillo exterior de 72 marcas radiales finas --}}
                        @foreach ($ticks as $t)
                            <line
                                x1="{{ $t['x1'] }}"
                                y1="{{ $t['y1'] }}"
                                x2="{{ $t['x2'] }}"
                                y2="{{ $t['y2'] }}"
                                stroke="#e4e4e7"
                                stroke-width="1.35"
                                stroke-linecap="round"
                            />
                        @endforeach

                        {{-- Pista base del anillo --}}
                        <circle cx="100" cy="100" r="{{ $ringRadius }}" fill="none" stroke="#f4f4f5" stroke-width="5.5" />

                        {{-- 4 Arcos de color --}}
                        @foreach ($arcPaths as $arc)
                            <path
                                d="{{ $arc['d'] }}"
                                fill="none"
                                stroke="{{ $arc['color'] }}"
                                stroke-width="5.5"
                                stroke-linecap="round"
                                class="transition-opacity duration-200 cursor-pointer"
                                :style="hoveredSegment && hoveredSegment !== '{{ $arc['label'] }}' ? 'opacity: 0.3' : 'opacity: 1'"
                                @mouseenter="hoveredSegment = '{{ $arc['label'] }}'"
                                @mouseleave="hoveredSegment = null"
                            />
                        @endforeach

                        {{-- 4 Nodos circulares blancos con borde de color en cada unión --}}
                        @foreach ($arcPaths as $arc)
                            <circle
                                cx="{{ $arc['nodeX'] }}"
                                cy="{{ $arc['nodeY'] }}"
                                r="4.3"
                                fill="#ffffff"
                                stroke="{{ $arc['color'] }}"
                                stroke-width="2.6"
                                class="pointer-events-none"
                            />
                        @endforeach
                    </svg>

                    {{-- Disco central azul cobalto plano con % en superíndice --}}
                    <div
                        class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        aria-label="{{ $data['percentage'] }}%"
                    >
                        <div class="flex h-[110px] w-[110px] flex-col items-center justify-center rounded-full bg-[#095d52] text-white">
                            <span class="text-[10.5px] font-medium text-white/85 tracking-tight">{{ $data['subtitle'] }}</span>
                            <div class="mt-0.5 flex items-start justify-center leading-none">
                                <span class="text-[31px] font-semibold tracking-tight tabular-nums">{{ $data['percentage'] }}</span>
                                <span class="ml-0.5 mt-0.5 text-[13px] font-medium leading-none">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tooltip discreto del segmento activo --}}
                <div class="h-4 mt-1 text-[11px] font-medium text-zinc-600 tabular-nums text-center">
                    @foreach ($arcPaths as $arc)
                        <span x-cloak x-show="hoveredSegment === '{{ $arc['label'] }}'">
                            {{ $arc['label'] }}: ${{ number_format($arc['value'], 2) }} ({{ $arc['pct'] }}%)
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-card>
