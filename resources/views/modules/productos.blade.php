<x-layouts.app title="Productos">
    @php
        $categoryNames = array_column($categories, 'name');
        $defaultCategory = $categoryNames[0] ?? 'Dermocosmética';
        $hasCreateErrors = $errors->has('codigo') || $errors->has('name') || $errors->has('brand') || $errors->has('category') || $errors->has('image');
    @endphp

    <div
        class="space-y-5"
        x-data="{
            viewMode: 'table',
            createModal: {{ $hasCreateErrors ? 'true' : 'false' }},
            viewModal: false,
            editModal: false,
            inventoryModal: false,
            deleteModal: false,
            activeProduct: null,
            createPreview: null,
            editPreview: null,
            editForm: {
                codigo: '',
                name: '',
                category: '{{ $defaultCategory }}',
                san_nicolas_category: '',
                brand: '',
                price: 0,
                previous_price: '',
                stock: 0,
                min_stock: 15,
                description: '',
                image_url: null,
                is_active: true
            },
            inventoryForm: { codigo: '', name: '', price: 0, stock: 0, min_stock: 15 },
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
                this.activeProduct = item;
                this.viewModal = true;
            },
            openEdit(item) {
                this.editForm = {
                    codigo: item.codigo,
                    name: item.name,
                    category: item.category,
                    san_nicolas_category: item.san_nicolas_category || '',
                    brand: item.brand,
                    price: Number(item.price || 0),
                    previous_price: item.previous_price !== null && item.previous_price !== undefined ? Number(item.previous_price) : '',
                    stock: Number(item.stock || 0),
                    min_stock: Number(item.min_stock || 15),
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
                    min_stock: Number(item.min_stock || 15)
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
            title="Productos"
            subtitle="Catálogo comercial no medicinal: dermocosmética, vitaminas, cuidado personal, bebé y fragancias"
            actionLabel="Nuevo Producto"
            actionClick="createModal = true"
        >
            <div class="inline-flex rounded-[6px] border border-zinc-200 bg-white p-1">
                <button
                    type="button"
                    @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:text-zinc-900'"
                    class="rounded-[4px] px-3 py-1.5 text-xs font-medium transition-colors cursor-pointer"
                >
                    Tabla
                </button>
                <button
                    type="button"
                    @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:text-zinc-900'"
                    class="rounded-[4px] px-3 py-1.5 text-xs font-medium transition-colors cursor-pointer"
                >
                    Tarjetas
                </button>
            </div>
        </x-page-header>

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

        {{-- KPIs en tiempo real para productos no medicinales --}}
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
            @foreach ($products['kpis'] as $kpi)
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
            {{-- Filtros persistentes por categoría comercial, estado y búsqueda --}}
            <div class="mb-4 flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $categoryTabs = array_merge(['Todos'], array_column($categories, 'name'));
                    @endphp
                    @foreach ($categoryTabs as $catOption)
                        @php
                            $isCurrentCat = ($filters['categoria'] === '' ? 'Todos' : $filters['categoria']) === $catOption;
                            $catQuery = array_filter([
                                'categoria' => $catOption === 'Todos' ? null : $catOption,
                                'estado' => ($filters['estado'] !== '' && $filters['estado'] !== 'Todos') ? $filters['estado'] : null,
                                'buscar' => $filters['buscar'] !== '' ? $filters['buscar'] : null,
                            ], fn ($v) => $v !== null);
                        @endphp
                        <a
                            href="{{ route('productos.index', $catQuery) }}"
                            @class([
                                'min-h-11 sm:min-h-9 inline-flex items-center rounded-[6px] border px-3.5 py-1.5 text-xs font-medium transition-colors',
                                'bg-[#095d52] text-white border-[#095d52]' => $isCurrentCat,
                                'bg-white text-zinc-600 border-zinc-200 hover:bg-zinc-50' => ! $isCurrentCat,
                            ])
                        >
                            {{ $catOption }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('productos.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    @if ($filters['categoria'] !== '' && $filters['categoria'] !== 'Todos')
                        <input type="hidden" name="categoria" value="{{ $filters['categoria'] }}" />
                    @endif

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative w-full sm:w-80">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                                <x-icon name="search" class="w-4 h-4" />
                            </span>
                            <input
                                type="text"
                                name="buscar"
                                value="{{ $filters['buscar'] }}"
                                placeholder="Buscar SKU DER/VIT/PRD, producto o marca..."
                                class="min-h-11 sm:min-h-9 w-full rounded-[6px] border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:bg-white focus:outline-none"
                            />
                        </div>

                        <select
                            name="estado"
                            aria-label="Filtrar por estado"
                            class="min-h-11 sm:min-h-9 rounded-[6px] border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 focus:border-[#095d52] focus:outline-none"
                        >
                            <option value="Todos" @selected($filters['estado'] === 'Todos')>Todos los estados</option>
                            <option value="Disponible" @selected($filters['estado'] === 'Disponible')>Disponible</option>
                            <option value="Stock Bajo" @selected($filters['estado'] === 'Stock Bajo')>Stock Bajo</option>
                            <option value="Sin Stock" @selected($filters['estado'] === 'Sin Stock')>Sin Stock</option>
                            <option value="Dados de Baja" @selected($filters['estado'] === 'Dados de Baja')>Dados de Baja</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-zinc-500 tabular-nums">
                            Mostrando <strong class="font-semibold text-zinc-800">{{ count($products['items']) }}</strong> de {{ $summary['retail_products'] }} productos
                        </span>
                        <button
                            type="submit"
                            class="min-h-11 sm:min-h-9 rounded-[6px] bg-[#095d52] px-4 py-1.5 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer"
                        >
                            Aplicar filtros
                        </button>
                        @if ($filters['buscar'] !== '' || $filters['categoria'] !== 'Todos' || $filters['estado'] !== 'Todos')
                            <a
                                href="{{ route('productos.index') }}"
                                class="min-h-11 sm:min-h-9 inline-flex items-center rounded-[6px] border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                            >
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Vista Tabla --}}
            <div x-show="viewMode === 'table'" class="overflow-x-auto">
                <table class="w-full min-w-[820px] xl:min-w-0 text-left border-collapse">
                    <thead>
                        <tr class="rounded-[6px] bg-zinc-100/80 text-[12px] font-medium text-zinc-500">
                            <th class="py-2.5 pl-2.5 pr-2 font-medium">Producto</th>
                            <th class="py-2.5 px-2 font-medium">Categoría / Línea</th>
                            <th class="py-2.5 px-2 font-medium">Marca</th>
                            <th class="py-2.5 px-2 font-medium text-right">Precio</th>
                            <th class="py-2.5 px-2 font-medium text-center">Existencias</th>
                            <th class="py-2.5 px-2 font-medium">Estado</th>
                            <th class="py-2.5 pl-2 pr-2.5 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-[13px]">
                        @forelse ($products['items'] as $prod)
                            @php
                                $isLowStock = ($prod['is_active'] ?? true) && ((int) $prod['stock'] <= (int) ($prod['min_stock'] ?? 15));
                            @endphp
                            <tr class="transition-colors hover:bg-zinc-50/70">
                                <td class="py-2.5 pl-2.5 pr-2">
                                    <div class="flex items-center gap-3 max-w-[265px]">
                                        @if (! empty($prod['image_url']))
                                            <img
                                                src="{{ $prod['image_url'] }}"
                                                alt="{{ $prod['name'] }}"
                                                loading="lazy"
                                                class="h-11 w-11 shrink-0 rounded-[6px] border border-zinc-200 bg-white object-contain p-0.5"
                                            />
                                        @else
                                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[6px] border border-zinc-200 bg-zinc-50 text-[#095d52]">
                                                <x-icon name="tag" class="w-4 h-4" />
                                            </span>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-zinc-950 truncate" title="{{ $prod['name'] }}">{{ $prod['name'] }}</p>
                                            <p class="text-[11px] text-zinc-500 truncate">
                                                <span class="font-mono">{{ $prod['codigo'] }}</span>
                                                @if (! empty($prod['source_name']) && $prod['source_name'] !== $prod['name'])
                                                    · {{ $prod['source_name'] }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-2 max-w-[160px]">
                                    <p class="font-medium text-zinc-800 truncate">{{ $prod['category'] }}</p>
                                    @if (! empty($prod['san_nicolas_category']))
                                        <p class="text-[11px] text-zinc-500 truncate">{{ $prod['san_nicolas_category'] }}</p>
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 font-medium text-zinc-700 whitespace-nowrap">{{ $prod['brand'] }}</td>
                                <td class="py-2.5 px-2 text-right tabular-nums whitespace-nowrap">
                                    <span class="block font-semibold text-zinc-950">${{ number_format((float) $prod['price'], 2) }}</span>
                                    @if (! empty($prod['previous_price']) && (float) $prod['previous_price'] > (float) $prod['price'])
                                        <span class="block text-[10.5px] text-zinc-400 line-through">${{ number_format((float) $prod['previous_price'], 2) }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openInventory({{ \Illuminate\Support\Js::from($prod) }})"
                                        title="Ajustar precio y existencias"
                                        @class([
                                            'inline-flex items-center gap-1 rounded-[5px] border px-2.5 py-1 text-[11.5px] font-semibold tabular-nums transition-colors cursor-pointer whitespace-nowrap',
                                            'border-amber-300 bg-white text-amber-800 hover:border-[#095d52]' => $isLowStock,
                                            'border-zinc-200 bg-white text-zinc-800 hover:border-[#095d52]' => ! $isLowStock,
                                        ])
                                    >
                                        <span>{{ $prod['stock'] }} uds</span>
                                    </button>
                                </td>
                                <td class="py-2.5 px-2 whitespace-nowrap">
                                    <x-status-badge :status="($prod['is_active'] ?? true) ? 'Activo' : 'Inactivo'" />
                                </td>
                                <td class="py-2.5 pl-2 pr-2.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            @click="openView({{ \Illuminate\Support\Js::from($prod) }})"
                                            aria-label="Ver ficha de {{ $prod['name'] }}"
                                            title="Ver ficha"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-[#095d52] hover:text-white cursor-pointer"
                                        >
                                            <x-icon name="eye" class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openEdit({{ \Illuminate\Support\Js::from($prod) }})"
                                            aria-label="Editar {{ $prod['name'] }}"
                                            title="Editar producto"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-zinc-900 hover:text-white cursor-pointer"
                                        >
                                            <x-icon name="edit" class="w-3.5 h-3.5" />
                                        </button>
                                        <form method="POST" action="{{ route('productos.toggleStatus', $prod['codigo']) }}" class="inline-flex">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex h-7 items-center rounded-[6px] border border-zinc-200 bg-white px-2 text-[11px] font-medium text-zinc-700 hover:border-[#095d52] hover:text-[#095d52] cursor-pointer whitespace-nowrap"
                                            >
                                                {{ ($prod['is_active'] ?? true) ? 'Dar de baja' : 'Reactivar' }}
                                            </button>
                                        </form>
                                        <button
                                            type="button"
                                            @click="openDelete({{ \Illuminate\Support\Js::from($prod) }})"
                                            aria-label="Eliminar {{ $prod['name'] }}"
                                            title="Eliminar producto"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-red-600 hover:text-white cursor-pointer"
                                        >
                                            <x-icon name="trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-sm text-zinc-500">
                                    No se encontraron productos con esos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Vista Tarjetas --}}
            <div x-cloak x-show="viewMode === 'grid'" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($products['items'] as $prod)
                    <div class="flex flex-col justify-between rounded-[6px] border border-zinc-200/80 bg-zinc-50/40 p-4 transition-colors hover:bg-white">
                        <div>
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <span class="rounded-[5px] border border-zinc-200 bg-white px-2 py-0.5 font-mono text-[11px] font-semibold text-[#095d52] tabular-nums">
                                    {{ $prod['codigo'] }}
                                </span>
                                <x-status-badge :status="($prod['is_active'] ?? true) ? 'Activo' : 'Inactivo'" />
                            </div>

                            <div class="mb-3 flex h-40 w-full items-center justify-center rounded-[6px] border border-zinc-200/70 bg-white p-3">
                                @if (! empty($prod['image_url']))
                                    <img
                                        src="{{ $prod['image_url'] }}"
                                        alt="{{ $prod['name'] }}"
                                        loading="lazy"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                @else
                                    <x-icon name="tag" class="w-10 h-10 text-zinc-300" />
                                @endif
                            </div>

                            <h3 class="text-sm font-semibold text-zinc-950 line-clamp-2">{{ $prod['name'] }}</h3>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ $prod['category'] }} · {{ $prod['brand'] }}
                            </p>
                            <p class="mt-2 line-clamp-2 text-xs text-zinc-600">{{ $prod['description'] }}</p>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-zinc-200/70 pt-3">
                            <div>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-base font-semibold text-zinc-950 tabular-nums">${{ number_format((float) $prod['price'], 2) }}</span>
                                    @if (! empty($prod['previous_price']) && (float) $prod['previous_price'] > (float) $prod['price'])
                                        <span class="text-[11px] text-zinc-400 line-through tabular-nums">${{ number_format((float) $prod['previous_price'], 2) }}</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-zinc-500 tabular-nums">{{ $prod['stock'] }} uds en stock</p>
                            </div>

                            <div class="inline-flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="openView({{ \Illuminate\Support\Js::from($prod) }})"
                                    aria-label="Ver ficha de {{ $prod['name'] }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 hover:bg-[#095d52] hover:text-white cursor-pointer"
                                >
                                    <x-icon name="eye" class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="openEdit({{ \Illuminate\Support\Js::from($prod) }})"
                                    aria-label="Editar {{ $prod['name'] }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 hover:bg-zinc-900 hover:text-white cursor-pointer"
                                >
                                    <x-icon name="edit" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-10 text-center text-sm text-zinc-500">
                        No se encontraron productos con esos filtros.
                    </div>
                @endforelse
            </div>

            @if (count($products['items']) > 0)
                <p class="hidden" aria-hidden="true">No se encontraron productos</p>
            @endif
        </x-card>

        {{-- Modal Ver Detalle --}}
        <x-modal name="viewModal" title="Ficha del Producto" maxWidth="max-w-xl">
            <template x-if="activeProduct">
                <div class="space-y-4 text-sm">
                    <div class="flex items-start gap-4">
                        <template x-if="activeProduct.image_url">
                            <img :src="activeProduct.image_url" :alt="activeProduct.name" class="h-24 w-24 rounded-[8px] border border-zinc-200 bg-white object-contain p-1 shrink-0" />
                        </template>
                        <template x-if="!activeProduct.image_url">
                            <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-[8px] border border-zinc-200 bg-zinc-50 text-[#095d52]">
                                <x-icon name="tag" class="w-8 h-8" />
                            </div>
                        </template>
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-[#095d52]" x-text="activeProduct.codigo"></span>
                                <template x-if="activeProduct.san_nicolas_category">
                                    <span class="rounded-[5px] border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-700" x-text="activeProduct.san_nicolas_category"></span>
                                </template>
                            </div>
                            <h3 class="text-base font-semibold text-zinc-950" x-text="activeProduct.name"></h3>
                            <template x-if="activeProduct.source_name && activeProduct.source_name !== activeProduct.name">
                                <p class="text-[11.5px] text-zinc-500" x-text="'Presentación de referencia: ' + activeProduct.source_name"></p>
                            </template>
                            <p class="text-xs text-zinc-600" x-text="`Marca: ${activeProduct.brand} · Categoría: ${activeProduct.category}`"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5 text-xs">
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-zinc-500">Precio</p>
                            <div class="mt-0.5 flex items-baseline gap-1.5">
                                <span class="font-semibold text-[#095d52] tabular-nums" x-text="window.lifemedic.money(activeProduct.price)"></span>
                                <template x-if="activeProduct.previous_price && Number(activeProduct.previous_price) > Number(activeProduct.price)">
                                    <span class="text-[10.5px] text-zinc-400 line-through tabular-nums" x-text="window.lifemedic.money(activeProduct.previous_price)"></span>
                                </template>
                            </div>
                        </div>
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-zinc-500">Existencias</p>
                            <p class="mt-0.5 font-semibold text-zinc-900 tabular-nums" x-text="`${activeProduct.stock} uds`"></p>
                        </div>
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-zinc-500">Estado</p>
                            <p class="mt-0.5 font-semibold text-zinc-900" x-text="activeProduct.is_active ? 'Activo en Catálogo' : 'Dado de Baja'"></p>
                        </div>
                    </div>

                    <template x-if="activeProduct.formula && activeProduct.formula.length > 0">
                        <div class="rounded-[6px] border border-zinc-200 bg-zinc-50/60 p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">Componentes Activos / Fórmula</p>
                            <div class="mt-2 divide-y divide-zinc-200/70 rounded-[5px] border border-zinc-200 bg-white text-xs">
                                <template x-for="(comp, idx) in activeProduct.formula" :key="idx">
                                    <div class="flex items-center justify-between px-3 py-1.5">
                                        <span class="font-medium text-zinc-800" x-text="comp.ingredient"></span>
                                        <span class="font-mono text-zinc-600 tabular-nums" x-text="comp.amount"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="space-y-2 text-xs">
                        <div>
                            <p class="font-semibold text-zinc-800">Descripción y Uso</p>
                            <p class="mt-1 leading-relaxed text-zinc-600" x-text="activeProduct.indications || activeProduct.description || 'Sin descripción adicional.'"></p>
                        </div>

                        <template x-if="activeProduct.contraindications">
                            <div class="rounded-[6px] border border-amber-200 bg-amber-50/50 p-3">
                                <p class="font-semibold text-amber-900">Precauciones</p>
                                <p class="mt-1 leading-relaxed text-zinc-700" x-text="activeProduct.contraindications"></p>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="button" @click="viewModal = false" class="rounded-[6px] bg-zinc-900 px-4 py-2 text-xs font-medium text-white cursor-pointer">Cerrar</button>
                    </div>
                </div>
            </template>
        </x-modal>

        {{-- Modal Crear Producto (persistente en Laravel + subida de imagen) --}}
        <x-modal name="createModal" title="Registrar Nuevo Producto" maxWidth="max-w-lg">
            <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data" class="space-y-4 text-sm">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Código SKU *</label>
                        <input type="text" name="codigo" value="{{ old('codigo', $nextSuggestedCode) }}" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm font-mono focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Nombre Comercial *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej. Serum Facial Hialurónico 30ml" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Categoría Comercial *</label>
                        <select name="category" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['name'] }}" @selected(old('category', $defaultCategory) === $cat['name'])>{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Marca *</label>
                        <input type="text" name="brand" value="{{ old('brand') }}" required placeholder="Ej. Eucerin / Bioderma" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Línea / Subcategoría</label>
                        <input type="text" name="san_nicolas_category" value="{{ old('san_nicolas_category') }}" placeholder="Ej. Protección solar" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Precio ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price', '19.90') }}" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Precio Anterior ($)</label>
                        <input type="number" step="0.01" min="0.01" name="previous_price" value="{{ old('previous_price') }}" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Stock Inicial *</label>
                        <input type="number" min="0" name="stock" value="{{ old('stock', 24) }}" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Stock Mínimo</label>
                        <input type="number" min="1" name="min_stock" value="{{ old('min_stock', 10) }}" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Imagen del Producto (JPG, PNG, WebP)</label>
                    <div class="flex items-center gap-3">
                        <input
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            @change="previewUpload($event, 'create')"
                            class="block w-full text-xs text-zinc-600 file:mr-3 file:rounded-[6px] file:border file:border-zinc-200 file:bg-zinc-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-800 hover:file:bg-zinc-100 cursor-pointer"
                        />
                        <template x-if="createPreview">
                            <img :src="createPreview" alt="Vista previa" class="h-10 w-10 rounded-[6px] border border-zinc-200 object-contain shrink-0" />
                        </template>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Descripción</label>
                    <textarea name="description" rows="2" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">{{ old('description') }}</textarea>
                </div>

                <label class="inline-flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                    <span>Publicar activo en catálogo</span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Producto</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Editar Producto --}}
        <x-modal name="editModal" title="Editar Producto" maxWidth="max-w-lg">
            <form method="POST" :action="`/productos/${editForm.codigo}`" enctype="multipart/form-data" class="space-y-4 text-sm">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Código SKU</label>
                        <input type="text" :value="editForm.codigo" disabled class="w-full rounded-[6px] border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-mono text-zinc-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Nombre Comercial *</label>
                        <input type="text" name="name" x-model="editForm.name" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Categoría *</label>
                        <select name="category" x-model="editForm.category" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['name'] }}">{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Marca *</label>
                        <input type="text" name="brand" x-model="editForm.brand" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Línea / Subcategoría</label>
                        <input type="text" name="san_nicolas_category" x-model="editForm.san_nicolas_category" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Precio ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="price" x-model="editForm.price" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Precio Anterior ($)</label>
                        <input type="number" step="0.01" min="0.01" name="previous_price" x-model="editForm.previous_price" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Stock *</label>
                        <input type="number" min="0" name="stock" x-model="editForm.stock" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Stock Mínimo</label>
                        <input type="number" min="1" name="min_stock" x-model="editForm.min_stock" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Reemplazar Imagen</label>
                    <div class="flex items-center gap-3">
                        <input
                            type="file"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            @change="previewUpload($event, 'edit')"
                            class="block w-full text-xs text-zinc-600 file:mr-3 file:rounded-[6px] file:border file:border-zinc-200 file:bg-zinc-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-800 cursor-pointer"
                        />
                        <template x-if="editPreview || editForm.image_url">
                            <img :src="editPreview || editForm.image_url" alt="Vista previa" class="h-10 w-10 rounded-[6px] border border-zinc-200 object-contain shrink-0" />
                        </template>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Descripción</label>
                    <textarea name="description" rows="2" x-model="editForm.description" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-[#095d52] focus:outline-none"></textarea>
                </div>

                <input type="hidden" name="is_active" :value="editForm.is_active ? '1' : '0'" />
                <label class="inline-flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                    <input type="checkbox" x-model="editForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                    <span>Producto activo en catálogo</span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Cambios</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Control Rápido de Precio y Stock --}}
        <x-modal name="inventoryModal" title="Control Rápido de Precio y Stock" maxWidth="max-w-md">
            <form method="POST" :action="`/productos/${inventoryForm.codigo}/inventario`" class="space-y-4 text-sm">
                @csrf
                @method('PATCH')
                <p class="text-xs text-zinc-500">
                    Ajustando <strong class="text-zinc-900" x-text="inventoryForm.name"></strong>
                    (<span class="font-mono" x-text="inventoryForm.codigo"></span>)
                </p>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Precio ($) *</label>
                        <input type="number" step="0.01" min="0.01" name="price" x-model="inventoryForm.price" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Stock Actual *</label>
                        <input type="number" min="0" name="stock" x-model="inventoryForm.stock" required class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Stock Mín.</label>
                        <input type="number" min="1" name="min_stock" x-model="inventoryForm.min_stock" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="inventoryModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Actualizar Inventario</button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Eliminar Producto --}}
        <x-modal name="deleteModal" title="Confirmar Eliminación" maxWidth="max-w-md">
            <form method="POST" :action="`/productos/${deleteTarget.codigo}`" class="space-y-4 text-sm">
                @csrf
                @method('DELETE')
                <p class="text-zinc-600">
                    ¿Confirmas eliminar <strong class="font-semibold text-zinc-950" x-text="deleteTarget.name"></strong>
                    (<span class="font-mono" x-text="deleteTarget.codigo"></span>) del catálogo comercial?
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="deleteModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-red-600 px-4 py-2 text-xs font-medium text-white hover:bg-red-700 cursor-pointer">Eliminar</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
