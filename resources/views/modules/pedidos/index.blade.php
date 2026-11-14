<x-layouts.app title="Pedidos">
    <div class="space-y-5">
        <x-page-header
            title="Pedidos Móviles"
            subtitle="Consulta los pedidos generados desde la aplicación móvil, filtra por estado o fecha y administra su seguimiento."
        />

        @if (session('status_message'))
            <div class="flex items-center gap-2.5 rounded-[8px] border border-zinc-200 bg-white px-4 py-3 text-xs font-medium text-zinc-800 shadow-xs">
                <span class="h-2 w-2 shrink-0 rounded-full bg-[#095d52]" aria-hidden="true"></span>
                <span>{{ session('status_message') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-[8px] border border-zinc-300 bg-white px-4 py-3 text-xs text-zinc-800" role="alert">
                <p class="font-semibold text-[#dc2626]">No se pudo completar la acción:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-zinc-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <x-card padding="p-4">
                <x-kpi-mini label="Total Pedidos" :value="(string) $summary['total']" icon="orders" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Pendientes" :value="(string) $summary['pending']" icon="orders" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="En preparación" :value="(string) $summary['preparing']" icon="cube" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Completados" :value="(string) $summary['completed']" icon="chart-up" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Cancelados" :value="(string) $summary['cancelled']" icon="stock-alert" :plain="true" />
            </x-card>
        </section>

        <x-card padding="p-5">
            <div class="flex flex-wrap items-center gap-2 pb-4 border-b border-zinc-100">
                <a
                    href="{{ route('pedidos.index', array_filter(['q' => $filters['q'], 'fecha' => $filters['fecha'], 'fecha_desde' => $filters['fecha_desde'], 'fecha_hasta' => $filters['fecha_hasta']])) }}"
                    class="rounded-[7px] px-3 py-1.5 text-xs font-medium transition-colors {{ $filters['estado'] === '' ? 'bg-[#095d52] text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' }}"
                >
                    Todos
                </a>
                @foreach ($statusOptions as $key => $label)
                    <a
                        href="{{ route('pedidos.index', array_filter(['estado' => $key, 'q' => $filters['q'], 'fecha' => $filters['fecha'], 'fecha_desde' => $filters['fecha_desde'], 'fecha_hasta' => $filters['fecha_hasta']])) }}"
                        class="rounded-[7px] px-3 py-1.5 text-xs font-medium transition-colors {{ $filters['estado'] === $key ? 'bg-[#095d52] text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('pedidos.index') }}" class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <div class="lg:col-span-4">
                    <label for="q" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1">Buscar pedido o cliente</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-zinc-400">
                            <x-icon name="search" class="w-4 h-4" />
                        </span>
                        <input
                            id="q"
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Folio, nombre, correo o teléfono..."
                            class="w-full rounded-[7px] border border-zinc-200 bg-white pl-9 pr-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:outline-hidden"
                        />
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <label for="estado" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1">Estado</label>
                    <select
                        id="estado"
                        name="estado"
                        class="w-full rounded-[7px] border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-[#095d52] focus:outline-hidden"
                    >
                        <option value="">Todos los estados</option>
                        @foreach ($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected($filters['estado'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-3">
                    <label for="fecha" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1">Fecha exacta</label>
                    <input
                        id="fecha"
                        type="date"
                        name="fecha"
                        value="{{ $filters['fecha'] }}"
                        class="w-full rounded-[7px] border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-[#095d52] focus:outline-hidden"
                    />
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 rounded-[7px] bg-[#095d52] px-3.5 py-2 text-xs font-medium text-white hover:bg-[#074a41] transition-colors cursor-pointer"
                    >
                        Filtrar
                    </button>
                    @if ($filters['q'] !== '' || $filters['estado'] !== '' || $filters['fecha'] !== '' || $filters['fecha_desde'] !== '' || $filters['fecha_hasta'] !== '')
                        <a
                            href="{{ route('pedidos.index') }}"
                            class="rounded-[7px] border border-zinc-200 bg-white px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 transition-colors"
                        >
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-xs">
                    <thead>
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-500">
                            <th class="py-3 pr-4">Pedido</th>
                            <th class="py-3 px-4">Cliente</th>
                            <th class="py-3 px-4">Entrega</th>
                            <th class="py-3 px-4 text-right">Artículos</th>
                            <th class="py-3 px-4 text-right">Total</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 pl-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-zinc-50/70 transition-colors">
                                <td class="py-3.5 pr-4 whitespace-nowrap">
                                    <a href="{{ route('pedidos.show', $order) }}" class="font-semibold text-[#095d52] hover:underline">
                                        {{ $order->order_number }}
                                    </a>
                                    <div class="text-[11px] text-zinc-500">
                                        {{ ($order->placed_at ?? $order->created_at)?->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-zinc-900">{{ $order->customer_name ?? $order->user?->name ?? 'Cliente' }}</div>
                                    <div class="text-[11px] text-zinc-500">{{ $order->customer_email ?? $order->user?->email ?? 'Sin correo' }}</div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-zinc-700">
                                    <div>{{ $order->delivery_method }}</div>
                                    @if ($order->delivery_address)
                                        <div class="text-[11px] text-zinc-500 truncate max-w-[200px]">{{ $order->delivery_address }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap text-zinc-700 tabular-nums">
                                    {{ (int) ($order->units_count ?? $order->details_count ?? 0) }} uds
                                    <span class="block text-[11px] text-zinc-400">{{ $order->details_count }} líneas</span>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap font-semibold text-zinc-900 tabular-nums">
                                    ${{ number_format((float) $order->total, 2) }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <x-status-badge :status="$order->status_label" />
                                </td>
                                <td class="py-3.5 pl-4 text-right whitespace-nowrap">
                                    <a
                                        href="{{ route('pedidos.show', $order) }}"
                                        class="inline-flex items-center gap-1.5 rounded-[7px] border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 hover:text-[#095d52] transition-colors"
                                    >
                                        <x-icon name="eye" class="w-3.5 h-3.5" />
                                        <span>Ver detalle</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-xs text-zinc-500">
                                    No se encontraron pedidos con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="mt-4 pt-4 border-t border-zinc-100">
                    {{ $orders->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
