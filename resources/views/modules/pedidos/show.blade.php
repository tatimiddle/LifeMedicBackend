<x-layouts.app :title="'Pedido ' . $order->order_number">
    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <a href="{{ route('pedidos.index') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#095d52] hover:underline mb-1.5">
                    <span>&larr; Volver al listado de pedidos</span>
                </a>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-xl font-semibold text-zinc-900 tracking-tight">Pedido {{ $order->order_number }}</h1>
                    <x-status-badge :status="$order->status_label" />
                </div>
                <p class="mt-1 text-xs text-zinc-500">
                    Registrado el {{ ($order->placed_at ?? $order->created_at)?->format('d/m/Y H:i') ?? 'N/D' }}
                    @if ($order->status_updated_at)
                        &middot; Última actualización: {{ $order->status_updated_at->format('d/m/Y H:i') }}
                    @endif
                </p>
            </div>
        </div>

        @if (session('status_message'))
            <div class="flex items-center gap-2.5 rounded-[8px] border border-zinc-200 bg-white px-4 py-3 text-xs font-medium text-zinc-800 shadow-xs">
                <span class="h-2 w-2 shrink-0 rounded-full bg-[#095d52]" aria-hidden="true"></span>
                <span>{{ session('status_message') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-[8px] border border-zinc-300 bg-white px-4 py-3 text-xs text-zinc-800" role="alert">
                <p class="font-semibold text-[#dc2626]">No se pudo actualizar el pedido:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-zinc-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
            <div class="lg:col-span-2 space-y-5">
                <x-card padding="p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900">Medicamentos solicitados</h2>
                            <p class="text-[11px] text-zinc-500">Detalle de productos, cantidades, precios unitarios y subtotales de la orden.</p>
                        </div>
                        <span class="text-xs font-medium text-zinc-600 tabular-nums">
                            {{ $order->details->sum('quantity') }} unidades
                        </span>
                    </div>

                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-xs">
                            <thead>
                                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-500">
                                    <th class="py-2.5 pr-4">Código</th>
                                    <th class="py-2.5 px-4">Medicamento</th>
                                    <th class="py-2.5 px-4 text-right">Cantidad</th>
                                    <th class="py-2.5 px-4 text-right">Precio Unitario</th>
                                    <th class="py-2.5 pl-4 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @forelse ($order->details as $detail)
                                    @php
                                        $lineTotal = (float) ($detail->line_total ?? ((float) $detail->unit_price * (int) $detail->quantity));
                                    @endphp
                                    <tr>
                                        <td class="py-3 pr-4 whitespace-nowrap font-mono text-[11px] text-zinc-600">
                                            {{ $detail->codigo ?? $detail->medicine?->codigo ?? 'N/D' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-medium text-zinc-900">{{ $detail->name ?? $detail->medicine?->name ?? 'Producto' }}</div>
                                            @if ($detail->medicine?->laboratory)
                                                <div class="text-[11px] text-zinc-500">{{ $detail->medicine->laboratory }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap text-zinc-700 tabular-nums">
                                            {{ (int) $detail->quantity }}
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap text-zinc-700 tabular-nums">
                                            ${{ number_format((float) $detail->unit_price, 2) }}
                                        </td>
                                        <td class="py-3 pl-4 text-right whitespace-nowrap font-semibold text-zinc-900 tabular-nums">
                                            ${{ number_format($lineTotal, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-zinc-500">Sin artículos registrados en esta orden.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 border-t border-zinc-200 pt-4 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Subtotal de productos</span>
                            <span class="font-medium text-zinc-900 tabular-nums">${{ number_format((float) $order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-zinc-600">
                            <span>Costo de envío ({{ $order->delivery_method }})</span>
                            <span class="font-medium text-zinc-900 tabular-nums">
                                @if ($order->free_shipping || (float) $order->shipping_cost === 0.0)
                                    Gratis ($0.00)
                                @else
                                    ${{ number_format((float) $order->shipping_cost, 2) }}
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between border-t border-zinc-100 pt-2.5 text-sm font-semibold text-zinc-900">
                            <span>Total del pedido</span>
                            <span class="text-[#095d52] tabular-nums">${{ number_format((float) $order->total, 2) }}</span>
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="space-y-5">
                <x-card padding="p-5">
                    <h2 class="text-sm font-semibold text-zinc-900 pb-3 border-b border-zinc-100">Datos del cliente</h2>
                    <dl class="mt-3 space-y-3 text-xs">
                        <div>
                            <dt class="text-[11px] uppercase tracking-wider text-zinc-400">Nombre completo</dt>
                            <dd class="mt-0.5 font-medium text-zinc-900">{{ $order->customer_name ?? $order->user?->name ?? 'N/D' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wider text-zinc-400">Correo electrónico</dt>
                            <dd class="mt-0.5 text-zinc-700">{{ $order->customer_email ?? $order->user?->email ?? 'N/D' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wider text-zinc-400">Teléfono de contacto</dt>
                            <dd class="mt-0.5 text-zinc-700">{{ $order->phone ?? $order->user?->phone ?? 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wider text-zinc-400">Modalidad de entrega</dt>
                            <dd class="mt-0.5 font-medium text-zinc-900">{{ $order->deliveryMethodLabel() }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] uppercase tracking-wider text-zinc-400">Dirección de entrega</dt>
                            <dd class="mt-0.5 text-zinc-700">{{ $order->delivery_address ?? $order->user?->address ?? 'Recolección en sucursal' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card padding="p-5">
                    <h2 class="text-sm font-semibold text-zinc-900 pb-3 border-b border-zinc-100">Actualizar estado del pedido</h2>

                    <form method="POST" action="{{ route('pedidos.status', $order) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="order_status" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1.5">Estado actual</label>
                            <select
                                id="order_status"
                                name="status"
                                class="w-full rounded-[7px] border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-[#095d52] focus:outline-hidden"
                            >
                                @foreach ($statusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $order->status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="admin_note" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1.5">Nota interna / observaciones</label>
                            <textarea
                                id="admin_note"
                                name="admin_note"
                                rows="3"
                                placeholder="Detalles de surtido, guía de envío o motivo de cancelación..."
                                class="w-full rounded-[7px] border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:outline-hidden"
                            >{{ old('admin_note', $order->admin_note) }}</textarea>
                        </div>

                        <p class="text-[11px] text-zinc-500 leading-relaxed">
                            Al cambiar el estado a <strong>Cancelado</strong>, el sistema reintegra automáticamente las unidades descontadas al inventario.
                        </p>

                        <button
                            type="submit"
                            class="w-full rounded-[7px] bg-[#095d52] px-4 py-2.5 text-xs font-medium text-white hover:bg-[#074a41] transition-colors cursor-pointer"
                        >
                            Actualizar estado
                        </button>
                    </form>
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.app>
