<x-layouts.app title="Categorías">
    <div
        class="space-y-5"
        x-data="{
            createModal: {{ $errors->has('name') || $errors->has('description') ? 'true' : 'false' }},
            editModal: false,
            deleteModal: false,
            editForm: { slug: '', name: '', description: '', is_active: true },
            deleteTarget: { slug: '', name: '', items_count: 0 },
            openEdit(category) {
                this.editForm = {
                    slug: category.slug,
                    name: category.name,
                    description: category.description || '',
                    is_active: Boolean(category.is_active)
                };
                this.editModal = true;
            },
            openDelete(category) {
                this.deleteTarget = {
                    slug: category.slug,
                    name: category.name,
                    items_count: Number(category.items_count || 0)
                };
                this.deleteModal = true;
            }
        }"
    >
        <x-page-header
            title="Categorías"
            subtitle="Clasificación terapéutica y líneas farmacéuticas sincronizadas con el catálogo móvil"
            actionLabel="Nueva Categoría"
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
                <p class="font-semibold text-[#dc2626]">No se pudo completar la acción:</p>
                <ul class="mt-1 list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
            <x-card padding="p-4">
                <x-kpi-mini label="Total Categorías" :value="(string) $summary['total']" icon="tag" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Categorías Activas" :value="(string) $summary['active']" icon="orders" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Artículos Clasificados" :value="(string) $summary['classified_items']" icon="medicine" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Categorías Inactivas" :value="(string) $summary['inactive']" icon="stock-alert" :plain="true" />
            </x-card>
        </div>

        <x-card padding="p-5">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $statusTabs = [
                            'todos' => 'Todas',
                            'activas' => 'Activas',
                            'inactivas' => 'Inactivas',
                        ];
                        $activeStatus = $filters['estado'] === '' ? 'todos' : $filters['estado'];
                    @endphp
                    @foreach ($statusTabs as $key => $label)
                        @php
                            $isCurrent = $activeStatus === $key;
                            $query = array_filter([
                                'estado' => $key === 'todos' ? null : $key,
                                'buscar' => $filters['buscar'] !== '' ? $filters['buscar'] : null,
                            ], fn ($v) => $v !== null);
                        @endphp
                        <a
                            href="{{ route('categorias.index', $query) }}"
                            @class([
                                'min-h-11 sm:min-h-9 inline-flex items-center rounded-[6px] border px-3.5 py-1.5 text-xs font-medium transition-colors',
                                'bg-zinc-900 text-white border-zinc-900' => $isCurrent,
                                'bg-white text-zinc-600 border-zinc-200 hover:bg-zinc-50' => ! $isCurrent,
                            ])
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('categorias.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @if ($filters['estado'] !== '' && $filters['estado'] !== 'todos')
                        <input type="hidden" name="estado" value="{{ $filters['estado'] }}" />
                    @endif
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                            <x-icon name="search" class="w-4 h-4" />
                        </span>
                        <input
                            type="text"
                            name="buscar"
                            value="{{ $filters['buscar'] }}"
                            placeholder="Buscar categoría o descripción..."
                            class="min-h-11 sm:min-h-9 w-full sm:w-64 rounded-[6px] border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:bg-white focus:outline-none"
                        />
                    </div>
                    <button
                        type="submit"
                        class="min-h-11 sm:min-h-9 rounded-[6px] bg-[#095d52] px-3.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-[#064940] cursor-pointer"
                    >
                        Filtrar
                    </button>
                    @if ($filters['buscar'] !== '' || ($filters['estado'] !== '' && $filters['estado'] !== 'todos'))
                        <a
                            href="{{ route('categorias.index') }}"
                            class="min-h-11 sm:min-h-9 inline-flex items-center justify-center rounded-[6px] border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50"
                        >
                            Limpiar
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[740px] text-left border-collapse">
                    <thead>
                        <tr class="rounded-[6px] bg-zinc-100/80 text-[12px] font-medium text-zinc-500">
                            <th class="py-2.5 px-3 font-medium">Categoría</th>
                            <th class="py-2.5 px-3 font-medium">Slug API</th>
                            <th class="py-2.5 px-3 font-medium">Descripción</th>
                            <th class="py-2.5 px-3 font-medium text-center">Artículos</th>
                            <th class="py-2.5 px-3 font-medium">Estado</th>
                            <th class="py-2.5 px-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-[13px]">
                        @forelse ($categories as $category)
                            <tr class="transition-colors hover:bg-zinc-50/70">
                                <td class="py-3 px-3 font-semibold text-zinc-950">{{ $category['name'] }}</td>
                                <td class="py-3 px-3 font-mono text-xs text-zinc-500">{{ $category['slug'] }}</td>
                                <td class="py-3 px-3 text-xs text-zinc-600 max-w-sm truncate" title="{{ $category['description'] ?? '' }}">
                                    {{ ($category['description'] ?? '') !== '' ? $category['description'] : 'Sin descripción registrada' }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="inline-flex items-center rounded-[5px] border border-zinc-200 bg-white px-2.5 py-0.5 text-xs font-semibold text-zinc-800 tabular-nums">
                                        {{ $category['items_count'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <x-status-badge :status="$category['is_active'] ? 'Activo' : 'Inactivo'" />
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEdit({{ \Illuminate\Support\Js::from($category) }})"
                                            class="inline-flex h-8 items-center gap-1 rounded-[6px] border border-zinc-200 bg-white px-2.5 text-[11.5px] font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer"
                                        >
                                            <x-icon name="edit" class="w-3.5 h-3.5" />
                                            <span>Editar</span>
                                        </button>

                                        <form method="POST" action="{{ route('categorias.toggleStatus', $category['slug']) }}" class="inline-flex">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex h-8 items-center rounded-[6px] border border-zinc-200 bg-white px-2.5 text-[11.5px] font-medium text-zinc-700 hover:border-[#095d52] hover:text-[#095d52] cursor-pointer"
                                            >
                                                {{ $category['is_active'] ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            @click="openDelete({{ \Illuminate\Support\Js::from($category) }})"
                                            aria-label="Eliminar categoría {{ $category['name'] }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-red-600 hover:text-white cursor-pointer"
                                        >
                                            <x-icon name="trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-sm text-zinc-400">
                                    No se encontraron categorías con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Modal Crear Categoría --}}
        <x-modal name="createModal" title="Registrar Nueva Categoría">
            <form method="POST" action="{{ route('categorias.store') }}" class="space-y-4 text-sm">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Nombre de la categoría *</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="Ej. Antibióticos Clínicos"
                        class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Descripción</label>
                    <textarea
                        name="description"
                        rows="3"
                        placeholder="Describe brevemente los medicamentos incluidos en esta línea."
                        class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none"
                    >{{ old('description') }}</textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                    <span>Categoría activa y visible para la app móvil</span>
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">
                        Guardar Categoría
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Editar Categoría --}}
        <x-modal name="editModal" title="Editar Categoría">
            <form method="POST" :action="`/categorias/${editForm.slug}`" class="space-y-4 text-sm">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Nombre de la categoría *</label>
                    <input
                        type="text"
                        name="name"
                        x-model="editForm.name"
                        required
                        class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 mb-1">Descripción</label>
                    <textarea
                        name="description"
                        rows="3"
                        x-model="editForm.description"
                        class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none"
                    ></textarea>
                </div>
                <input type="hidden" name="is_active" :value="editForm.is_active ? '1' : '0'" />
                <label class="inline-flex items-center gap-2 text-xs text-zinc-700 cursor-pointer">
                    <input type="checkbox" x-model="editForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                    <span>Categoría activa en catálogo</span>
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- Modal Eliminar Categoría --}}
        <x-modal name="deleteModal" title="Eliminar Categoría" maxWidth="max-w-md">
            <form method="POST" :action="`/categorias/${deleteTarget.slug}`" class="space-y-4 text-sm">
                @csrf
                @method('DELETE')
                <p class="text-zinc-600">
                    ¿Confirmas eliminar la categoría <strong class="font-semibold text-zinc-950" x-text="deleteTarget.name"></strong>?
                </p>
                <p x-show="deleteTarget.items_count > 0" class="rounded-[6px] border border-amber-300 bg-white px-3 py-2 text-xs text-amber-800">
                    Esta categoría tiene <strong x-text="deleteTarget.items_count"></strong> artículo(s) asociado(s). Reasigna o elimina sus medicamentos antes de borrarla.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="deleteModal = false" class="rounded-[6px] border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-[6px] bg-red-600 px-4 py-2 text-xs font-medium text-white hover:bg-red-700 cursor-pointer">
                        Eliminar
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
