<x-layouts.app title="Medicamentos">
    @php
        $categoryNames = array_column($categories, 'name');
        $defaultCategory = $categoryNames[0] ?? 'Medicamentos';
        $hasCreateErrors = $errors->has('codigo') || $errors->has('name') || $errors->has('active_ingredient') || $errors->has('brand') || $errors->has('image');
    @endphp

    <div
        class="space-y-5"
        x-data="{
            createModal: {{ $hasCreateErrors ? 'true' : 'false' }},
            viewModal: false,
            editModal: false,
            inventoryModal: false,
            deleteModal: false,
            activeMed: null,
            createPreview: null,
            editPreview: null,
            editForm: {
                codigo: '',
                name: '',
                active_ingredient: '',
                category: '{{ $defaultCategory }}',
                san_nicolas_category: '',
                brand: '',
                laboratory: '',
                formula_summary: '',
                batch: '',
                expiry: '',
                expiry_status: 'Vigente',
                prescription: 'Venta Libre',
                price: 0,
                previous_price: '',
                stock: 0,
                min_stock: 20,
                indications: '',
                contraindications: '',
                description: '',
                image_url: null,
                is_active: true
            },
            inventoryForm: { codigo: '', name: '', price: 0, stock: 0, min_stock: 20 },
            deleteTarget: { codigo: '', name: '' },
            previewUpload(event, target) {
                const file = event.target.files?.[0];
                if (!file) {
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (target === 'create') {
                        this.createPreview = e.target.result;
                    } else {
                        this.editPreview = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            },
            openView(item) {
                this.activeMed = item;
                this.viewModal = true;
            },
            openEdit(item) {
                this.editForm = {
                    codigo: item.codigo,
                    name: item.name,
                    active_ingredient: item.active_ingredient || '',
                    category: item.category,
                    san_nicolas_category: item.san_nicolas_category || '',
                    brand: item.brand,
                    laboratory: item.laboratory || item.brand || '',
                    formula_summary: item.formula_summary || '',
                    batch: item.batch || '',
                    expiry: item.expiry || '',
                    expiry_status: item.expiry_status || 'Vigente',
                    prescription: item.prescription || 'Venta Libre',
                    price: Number(item.price || 0),
                    previous_price: item.previous_price !== null && item.previous_price !== undefined ? Number(item.previous_price) : '',
                    stock: Number(item.stock || 0),
                    min_stock: Number(item.min_stock || 20),
                    indications: item.indications || '',
                    contraindications: item.contraindications || '',
                    description: item.description || '',
                    image_url: item.image_url || null,
                    is_active: Boolean(item.is_active)
                };
                this.editPreview = null;
                this.editModal = true;
            },
            openInventory(item) {
                this.inventoryForm = {
                    codigo: item.codigo,
                    name: item.name,
                    price: Number(item.price || 0),
                    stock: Number(item.stock || 0),
                    min_stock: Number(item.min_stock || 20)
                };
                this.inventoryModal = true;
            },
            openDelete(item) {
                this.deleteTarget = { codigo: item.codigo, name: item.name };
                this.deleteModal = true;
            }
        }"
    >
        <x-page-header
            title="Medicamentos"
            subtitle="Control de catálogo clínico, imágenes de producto, precios, existencias y bajas lógicas"
            actionLabel="Registrar Medicamento"
            actionClick="createModal = true"
        />

        @if (session('status_message'))
            <div class="flex items-center gap-2.5 rounded-[8px] border border-zinc-200 bg-white px-4 py-3 text-xs font-medium text-zinc-800 shadow-xs">
                <span class="h-2 w-2 shrink-0 rounded-full bg-[#095d52]" aria-hidden="true"></span>
                <span>{{ session('status_message') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-[8px] border border-zinc-300 bg-white px-4 py-3 text-xs text-zinc-800" role="alert">
                <p class="font-semibold text-[#dc2626]">Revisa los datos ingresados:</p>
                <ul class="mt-1 list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- KPIs del catálogo en tiempo real --}}
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
            @foreach ($medicines['kpis'] as $kpi)
                <x-card padding="p-4">
                    <x-kpi-mini
                        :label="$kpi['label']"
                        :value="$kpi['value']"
                        :trend="$kpi['trend']"
                        :positive="$kpi['positive']"
                        :icon="$kpi['icon']"
                        :plain="true"
                    />
                </x-card>
            @endforeach
        </div>

        <x-card padding="p-5">
            {{-- Filtros persistentes por estado, categoría, receta y búsqueda --}}
            <div class="mb-4 flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['Todos', 'Activos', 'Stock Bajo', 'Por Vencer', 'Sin Stock', 'Dados de Baja'] as $estadoOption)
                        @php
                            $isCurrent = ($filters['estado'] === '' ? 'Todos' : $filters['estado']) === $estadoOption;
                            $estadoQuery = array_filter([
                                'estado' => $estadoOption === 'Todos' ? null : $estadoOption,
                                'categoria' => ($filters['categoria'] !== '' && $filters['categoria'] !== 'Todos') ? $filters['categoria'] : null,
                                'receta' => ($filters['receta'] !== '' && $filters['receta'] !== 'Todos') ? $filters['receta'] : null,
                                'buscar' => $filters['buscar'] !== '' ? $filters['buscar'] : null,
                            ], fn ($v) => $v !== null);
                        @endphp
                        <a
                            href="{{ route('medicamentos.index', $estadoQuery) }}"
                            @class([
                                'min-h-11 sm:min-h-9 inline-flex items-center rounded-[6px] border px-3.5 py-1.5 text-xs font-medium transition-colors',
                                'bg-zinc-900 text-white border-zinc-900' => $isCurrent,
                                'bg-white text-zinc-600 border-zinc-200 hover:bg-zinc-50' => ! $isCurrent,
                            ])
                        >
                            {{ $estadoOption }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('medicamentos.index') }}" class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    @if ($filters['estado'] !== '' && $filters['estado'] !== 'Todos')
                        <input type="hidden" name="estado" value="{{ $filters['estado'] }}" />
                    @endif

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                                <x-icon name="search" class="w-4 h-4" />
                            </span>
                            <input
                                type="text"
                                name="buscar"
                                value="{{ $filters['buscar'] }}"
                                placeholder="Buscar SKU, fármaco, principio activo..."
                                class="min-h-11 sm:min-h-9 w-full sm:w-72 rounded-[6px] border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:bg-white focus:outline-none"
                            />
                        </div>

                        <select
                            name="categoria"
                            aria-label="Filtrar por categoría"
                            class="min-h-11 sm:min-h-9 rounded-[6px] border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 focus:border-[#095d52] focus:outline-none"
                        >
                            <option value="Todos">Todas las categorías</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['name'] }}" @selected($filters['categoria'] === $cat['name'])>{{ $cat['name'] }}</option>
                            @endforeach
                        </select>

                        <select
                            name="receta"
                            aria-label="Filtrar por tipo de receta"
                            class="min-h-11 sm:min-h-9 rounded-[6px] border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 focus:border-[#095d52] focus:outline-none"
                        >
                            <option value="Todos" @selected($filters['receta'] === 'Todos')>Receta: Todos</option>
                            <option value="Con Receta" @selected($filters['receta'] === 'Con Receta')>Con Receta</option>
                            <option value="Venta Libre" @selected($filters['receta'] === 'Venta Libre')>Venta Libre</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-zinc-500 tabular-nums">
                            Mostrando <strong class="font-semibold text-zinc-800">{{ count($medicines['items']) }}</strong> de {{ $summary['clinical_medicines'] }} medicamentos
                        </span>
                        <button
                            type="submit"
                            class="min-h-11 sm:min-h-9 rounded-[6px] bg-[#095d52] px-4 py-1.5 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer"
                        >
                            Aplicar filtros
                        </button>
                        @if ($filters['buscar'] !== '' || $filters['categoria'] !== 'Todos' || $filters['receta'] !== 'Todos' || $filters['estado'] !== 'Todos')
                            <a
                                href="{{ route('medicamentos.index') }}"
                                class="min-h-11 sm:min-h-9 inline-flex items-center rounded-[6px] border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                            >
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] xl:min-w-0 text-left border-collapse">
                    <thead>
                        <tr class="rounded-[6px] bg-zinc-100/80 text-[12px] font-medium text-zinc-500">
                            <th class="py-2.5 pl-2.5 pr-2 font-medium">Medicamento</th>
                            <th class="py-2.5 px-2 font-medium">Categoría / Lab.</th>
                            <th class="py-2.5 px-2 font-medium">Lote / Vencimiento</th>
                            <th class="py-2.5 px-2 font-medium">Prescripción</th>
                            <th class="py-2.5 px-2 font-medium text-right">Precio</th>
                            <th class="py-2.5 px-2 font-medium text-center">Stock</th>
                            <th class="py-2.5 px-2 font-medium">Estado</th>
                            <th class="py-2.5 pl-2 pr-2.5 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-[13px]">
                        @forelse ($medicines['items'] as $med)
                            @php
                                $isLowStock = ($med['is_active'] ?? true) && ((int) $med['stock'] <= (int) ($med['min_stock'] ?? 20));
                            @endphp
                            <tr class="transition-colors hover:bg-zinc-50/70">
                                <td class="py-2.5 pl-2.5 pr-2">
                                    <div class="flex items-center gap-2.5 max-w-[240px]">
                                        @if (! empty($med['image_url']))
                                            <img
                                                src="{{ $med['image_url'] }}"
                                                alt="{{ $med['name'] }}"
                                                loading="lazy"
                                                class="h-10 w-10 shrink-0 rounded-[6px] border border-zinc-200 object-contain p-0.5 bg-white"
                                            />
                                        @else
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[6px] border border-zinc-200 bg-zinc-50 text-[#095d52]">
                                                <x-icon name="medicine" class="w-4 h-4" />
                                            </span>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-zinc-950 truncate" title="{{ $med['name'] }}">{{ $med['name'] }}</p>
                                            <p class="text-[11px] text-zinc-500 truncate" title="{{ $med['formula_summary'] ?: ($med['active_ingredient'] ?? $med['category']) }}">
                                                <span class="font-mono">{{ $med['codigo'] }}</span> · {{ $med['formula_summary'] ?: ($med['active_ingredient'] ?? $med['category']) }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-2 max-w-[165px]">
                                    <p class="font-medium text-zinc-800 truncate" title="{{ $med['category'] }}{{ ! empty($med['san_nicolas_category']) ? ' · ' . $med['san_nicolas_category'] : '' }}">
                                        {{ $med['category'] }}@if(! empty($med['san_nicolas_category'])) · {{ $med['san_nicolas_category'] }}@endif
                                    </p>
                                    <p class="text-[11px] text-zinc-500 truncate">{{ $med['laboratory'] ?? $med['brand'] }}</p>
                                </td>
                                <td class="py-2.5 px-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[11.5px] text-zinc-600 tabular-nums whitespace-nowrap">
                                            {{ $med['batch'] ?? 'N/A' }} · {{ $med['expiry'] ?? 'Sin fecha' }}
                                        </span>
                                        <x-status-badge :status="$med['expiry_status'] ?? 'Vigente'" />
                                    </div>
                                </td>
                                <td class="py-2.5 px-2 whitespace-nowrap">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-[5px] border px-2 py-0.5 text-[11px] font-medium bg-white',
                                        'border-zinc-900/20 text-zinc-900' => ($med['prescription'] ?? 'Venta Libre') === 'Con Receta',
                                        'border-zinc-200 text-zinc-600' => ($med['prescription'] ?? 'Venta Libre') !== 'Con Receta',
                                    ])>
                                        <span @class([
                                            'h-1.5 w-1.5 rounded-full',
                                            'bg-[#095d52]' => ($med['prescription'] ?? 'Venta Libre') === 'Con Receta',
                                            'bg-zinc-400' => ($med['prescription'] ?? 'Venta Libre') !== 'Con Receta',
                                        ]) aria-hidden="true"></span>
                                        {{ $med['prescription'] ?? 'Venta Libre' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-2 text-right tabular-nums whitespace-nowrap">
                                    <span class="block font-semibold text-zinc-950">${{ number_format((float) $med['price'], 2) }}</span>
                                    @if (! empty($med['previous_price']) && (float) $med['previous_price'] > (float) $med['price'])
                                        <span class="block text-[10.5px] text-zinc-400 line-through">${{ number_format((float) $med['previous_price'], 2) }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openInventory({{ \Illuminate\Support\Js::from($med) }})"
                                        title="Ajustar precio y stock"
                                        @class([
                                            'inline-flex items-center gap-1 rounded-[5px] border px-2 py-1 text-[11.5px] font-semibold tabular-nums transition-colors cursor-pointer whitespace-nowrap',
                                            'border-amber-300 bg-white text-amber-800 hover:border-[#095d52]' => $isLowStock,
                                            'border-zinc-200 bg-white text-zinc-800 hover:border-[#095d52]' => ! $isLowStock,
                                        ])
                                    >
                                        <span>{{ $med['stock'] }} uds</span>
                                    </button>
                                </td>
                                <td class="py-2.5 px-2 whitespace-nowrap">
                                    <x-status-badge :status="($med['is_active'] ?? true) ? 'Activo' : 'Inactivo'" />
                                </td>
                                <td class="py-2.5 pl-2 pr-2.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="openView({{ \Illuminate\Support\Js::from($med) }})"
                                            aria-label="Ver detalle de {{ $med['name'] }}"
                                            title="Ver detalle"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-[#095d52] hover:text-white cursor-pointer"
                                        >
                                            <x-icon name="eye" class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEdit({{ \Illuminate\Support\Js::from($med) }})"
                                            aria-label="Editar {{ $med['name'] }}"
                                            title="Editar ficha e imagen"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-zinc-900 hover:text-white cursor-pointer"
                                        >
                                            <x-icon name="edit" class="w-3.5 h-3.5" />
                                        </button>
                                        <form method="POST" action="{{ route('medicamentos.toggleStatus', $med['codigo']) }}" class="inline-flex">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex h-7 items-center rounded-[6px] border border-zinc-200 bg-white px-2 text-[11px] font-medium text-zinc-700 hover:border-[#095d52] hover:text-[#095d52] cursor-pointer whitespace-nowrap"
                                            >
                                                {{ ($med['is_active'] ?? true) ? 'Dar de baja' : 'Reactivar' }}
                                            </button>
                                        </form>
                                        <button
                                            type="button"
                                            @click="openDelete({{ \Illuminate\Support\Js::from($med) }})"
                                            aria-label="Eliminar {{ $med['name'] }}"
                                            title="Eliminar medicamento"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-red-600 hover:text-white cursor-pointer"
                                        >
                                            <x-icon name="trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-sm text-zinc-500">
                                    No se encontraron medicamentos con esos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (count($medicines['items']) > 0)
                <p class="hidden" aria-hidden="true">No se encontraron medicamentos</p>
            @endif
        </x-card>

        {{-- Modal Ver Detalle --}}
        <x-modal name="viewModal" title="Ficha Técnica del Medicamento" maxWidth="max-w-xl">
            <template x-if="activeMed">
                <div class="space-y-4 text-sm">
                    <div class="flex items-start gap-4">
                        <template x-if="activeMed.image_url">
                            <img :src="activeMed.image_url" :alt="activeMed.name" class="h-24 w-24 rounded-[8px] border border-zinc-200 object-contain p-1 bg-white shrink-0" />
                        </template>
                        <template x-if="!activeMed.image_url">
                            <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-[8px] border border-zinc-200 bg-zinc-50 text-[#095d52]">
                                <x-icon name="medicine" class="w-8 h-8" />
                            </div>
                        </template>
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-[#095d52]" x-text="activeMed.codigo"></span>
                                <template x-if="activeMed.san_nicolas_category">
                                    <span class="rounded-[5px] border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-700" x-text="activeMed.san_nicolas_category"></span>
                                </template>
                            </div>
                            <h3 class="text-base font-semibold text-zinc-950" x-text="activeMed.name"></h3>
                            <template x-if="activeMed.source_name && activeMed.source_name !== activeMed.name">
                                <p class="text-[11.5px] text-zinc-500" x-text="'Presentación de referencia: ' + activeMed.source_name"></p>
                            </template>
                            <p class="text-xs text-zinc-600" x-text="`Laboratorio: ${activeMed.laboratory || activeMed.brand} · Marca: ${activeMed.brand}`"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-zinc-500">Precio</p>
                            <div class="mt-0.5 flex items-baseline gap-1.5">
                                <span class="font-semibold text-[#095d52] tabular-nums" x-text="window.lifemedic.money(activeMed.price)"></span>
                                <template x-if="activeMed.previous_price && Number(activeMed.previous_price) > Number(activeMed.price)">
                                    <span class="text-[10.5px] text-zinc-400 line-through tabular-nums" x-text="window.lifemedic.money(activeMed.previous_price)"></span>
                                </template>
                            </div>
                        </div>
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-zinc-500">Existencias</p>
                            <p class="mt-0.5 font-semibold text-zinc-900 tabular-nums" x-text="`${activeMed.stock} uds (mín. ${activeMed.min_stock || 20})`"></p>
                        </div>
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-zinc-500">Vencimiento</p>
                            <p class="mt-0.5 font-semibold text-zinc-900" x-text="`${activeMed.expiry || 'N/A'} (${activeMed.expiry_status})`"></p>
                        </div>
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-zinc-500">Prescripción</p>
                            <p class="mt-0.5 font-semibold text-zinc-900" x-text="activeMed.prescription"></p>
                        </div>
                    </div>

                    <div class="rounded-[6px] border border-zinc-200 bg-zinc-50/60 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">Fórmula / Principios Activos</p>
                        <template x-if="activeMed.formula && activeMed.formula.length > 0">
                            <div class="mt-2 divide-y divide-zinc-200/70 rounded-[5px] border border-zinc-200 bg-white text-xs">
                                <template x-for="(comp, idx) in activeMed.formula" :key="idx">
                                    <div class="flex items-center justify-between px-3 py-1.5">
                                        <span class="font-medium text-zinc-800" x-text="comp.ingredient"></span>
                                        <span class="font-mono text-zinc-600 tabular-nums" x-text="comp.amount"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!activeMed.formula || activeMed.formula.length === 0">
                            <p class="mt-1 text-xs font-medium text-zinc-800" x-text="activeMed.formula_summary || activeMed.active_ingredient || 'No especificado'"></p>
                        </template>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div>
                            <p class="font-semibold text-zinc-800">Indicaciones Clínicas</p>
                            <p class="mt-1 leading-relaxed text-zinc-600" x-text="activeMed.indications || activeMed.description || 'Sin indicaciones clínicas registradas.'"></p>
                        </div>

                        <template x-if="activeMed.contraindications">
                            <div class="rounded-[6px] border border-amber-200 bg-amber-50/50 p-3">
                                <p class="font-semibold text-amber-900">Contraindicaciones y Advertencias</p>
                                <p class="mt-1 leading-relaxed text-zinc-700" x-text="activeMed.contraindications"></p>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="button" @click="viewModal = false" class="rounded-[6px] bg-zinc-900 px-4 py-2 text-xs font-medium text-white cursor-pointer">
                            Cerrar
                        </button>
                    </div>
                </div>
            </template>
        </x-modal>

        {{-- Modal Registrar Medicamento (con subida de imagen) --}}
        <x-modal name="createModal" title="Registrar Nuevo Medicamento" maxWidth="max-w-xl">
            <form method="POST" action="{{ route('medicamentos.store') }}" enctype="multipart/form-data" class="space-y-4 text-sm">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Código SKU *</label>
                        <input type="text" name="codigo" value="{{ old('codigo', $nextSuggestedCode) }}" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm font-mono focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Nombre Comercial *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej. Loratadina 10mg (Caja x 10)" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Principio Activo *</label>
                        <input type="text" name="active_ingredient" value="{{ old('active_ingredient') }}" required placeholder="Ej. Loratadina" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Marca *</label>
                        <input type="text" name="brand" value="{{ old('brand') }}" required placeholder="Ej. Genericos Mk" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Laboratorio</label>
                        <input type="text" name="laboratory" value="{{ old('laboratory') }}" placeholder="Ej. Tecnoquimicas" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Categoría *</label>
                        <select name="category" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['name'] }}" @selected(old('category', $defaultCategory) === $cat['name'])>{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Subcategoría Clínica</label>
                        <input type="text" name="san_nicolas_category" value="{{ old('san_nicolas_category') }}" placeholder="Ej. Analgésicos" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Prescripción *</label>
                        <select name="prescription" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            <option value="Venta Libre" @selected(old('prescription') === 'Venta Libre')>Venta Libre</option>
                            <option value="Con Receta" @selected(old('prescription') === 'Con Receta')>Con Receta</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Estado Vencimiento *</label>
                        <select name="expiry_status" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            <option value="Vigente" @selected(old('expiry_status', 'Vigente') === 'Vigente')>Vigente</option>
                            <option value="Por Vencer" @selected(old('expiry_status') === 'Por Vencer')>Por Vencer</option>
                            <option value="Vencido" @selected(old('expiry_status') === 'Vencido')>Vencido</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Precio ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price', '5.50') }}" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Stock Inicial *</label>
                        <input type="number" min="0" name="stock" value="{{ old('stock', 30) }}" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Stock Mínimo</label>
                        <input type="number" min="1" name="min_stock" value="{{ old('min_stock', 20) }}" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Lote</label>
                        <input type="text" name="batch" value="{{ old('batch', 'LOT-26A01') }}" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Fecha Vencimiento</label>
                        <input type="text" name="expiry" value="{{ old('expiry', 'Dic 2027') }}" placeholder="Ej. Dic 2027" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Imagen del producto (JPG, PNG, WebP)</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png,image/webp"
                                @change="previewUpload($event, 'create')"
                                class="block w-full text-xs text-zinc-600 file:mr-3 file:rounded-[6px] file:border file:border-zinc-200 file:bg-zinc-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-800 hover:file:bg-zinc-100 cursor-pointer"
                            />
                            <template x-if="createPreview">
                                <img :src="createPreview" alt="Vista previa" class="h-10 w-10 rounded-[6px] border border-zinc-200 object-cover shrink-0" />
                            </template>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Indicaciones / Descripción</label>
                        <textarea name="description" rows="2" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Contraindicaciones</label>
                        <textarea name="contraindications" rows="2" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">{{ old('contraindications') }}</textarea>
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                    <span>Publicar activo en catálogo</span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Medicamento</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Editar Medicamento (incluye imagen, precio, stock y categoría) --}}
        <x-modal name="editModal" title="Editar Medicamento" maxWidth="max-w-xl">
            <form method="POST" :action="`/medicamentos/${editForm.codigo}`" enctype="multipart/form-data" class="space-y-4 text-sm">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Código SKU</label>
                        <input type="text" :value="editForm.codigo" disabled class="w-full rounded-[6px] border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-mono text-zinc-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Nombre Comercial *</label>
                        <input type="text" name="name" x-model="editForm.name" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Principio Activo *</label>
                        <input type="text" name="active_ingredient" x-model="editForm.active_ingredient" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Marca *</label>
                        <input type="text" name="brand" x-model="editForm.brand" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Laboratorio</label>
                        <input type="text" name="laboratory" x-model="editForm.laboratory" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Categoría *</label>
                        <select name="category" x-model="editForm.category" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['name'] }}">{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Subcategoría Clínica</label>
                        <input type="text" name="san_nicolas_category" x-model="editForm.san_nicolas_category" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Prescripción *</label>
                        <select name="prescription" x-model="editForm.prescription" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            <option value="Venta Libre">Venta Libre</option>
                            <option value="Con Receta">Con Receta</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Estado Vencimiento *</label>
                        <select name="expiry_status" x-model="editForm.expiry_status" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            <option value="Vigente">Vigente</option>
                            <option value="Por Vencer">Por Vencer</option>
                            <option value="Vencido">Vencido</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Precio ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="price" x-model="editForm.price" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Stock *</label>
                        <input type="number" min="0" name="stock" x-model="editForm.stock" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Stock Mínimo</label>
                        <input type="number" min="1" name="min_stock" x-model="editForm.min_stock" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Lote</label>
                        <input type="text" name="batch" x-model="editForm.batch" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Fecha Vencimiento</label>
                        <input type="text" name="expiry" x-model="editForm.expiry" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Reemplazar imagen</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png,image/webp"
                                @change="previewUpload($event, 'edit')"
                                class="block w-full text-xs text-zinc-600 file:mr-3 file:rounded-[6px] file:border file:border-zinc-200 file:bg-zinc-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-800 cursor-pointer"
                            />
                            <template x-if="editPreview || editForm.image_url">
                                <img :src="editPreview || editForm.image_url" alt="Vista previa" class="h-10 w-10 rounded-[6px] border border-zinc-200 object-cover shrink-0" />
                            </template>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Descripción</label>
                        <textarea name="description" rows="2" x-model="editForm.description" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Contraindicaciones</label>
                        <textarea name="contraindications" rows="2" x-model="editForm.contraindications" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none"></textarea>
                    </div>
                </div>

                <input type="hidden" name="is_active" :value="editForm.is_active ? '1' : '0'" />
                <label class="inline-flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                    <input type="checkbox" x-model="editForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                    <span>Medicamento activo en inventario</span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Cambios</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Control Rápido de Precio y Stock --}}
        <x-modal name="inventoryModal" title="Control Rápido de Precio y Stock" maxWidth="max-w-md">
            <form method="POST" :action="`/medicamentos/${inventoryForm.codigo}/inventario`" class="space-y-4 text-sm">
                @csrf
                @method('PATCH')
                <p class="text-xs text-zinc-500">
                    Ajustando <strong class="text-zinc-900" x-text="inventoryForm.name"></strong>
                    (<span class="font-mono" x-text="inventoryForm.codigo"></span>)
                </p>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Precio ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="price" x-model="inventoryForm.price" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Stock Actual *</label>
                        <input type="number" min="0" name="stock" x-model="inventoryForm.stock" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 mb-1">Stock Mín.</label>
                        <input type="number" min="1" name="min_stock" x-model="inventoryForm.min_stock" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="inventoryModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Actualizar Inventario</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Eliminar Medicamento --}}
        <x-modal name="deleteModal" title="Confirmar Eliminación" maxWidth="max-w-md">
            <form method="POST" :action="`/medicamentos/${deleteTarget.codigo}`" class="space-y-4 text-sm">
                @csrf
                @method('DELETE')
                <p class="text-zinc-600">
                    ¿Confirmas eliminar <strong class="font-semibold text-zinc-950" x-text="deleteTarget.name"></strong>
                    (<span class="font-mono" x-text="deleteTarget.codigo"></span>) del catálogo? Esta acción también eliminará su imagen asociada.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="deleteModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-red-600 px-4 py-2 text-xs font-medium text-white hover:bg-red-700 cursor-pointer">Eliminar</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
