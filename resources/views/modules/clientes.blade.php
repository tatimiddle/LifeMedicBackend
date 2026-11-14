<x-layouts.app title="Clientes">
    <div
        class="space-y-5"
        x-data="{
            rows: {{ \Illuminate\Support\Js::from($data['items']) }},
            selectedStatus: 'Todos',
            search: '',
            viewModal: false,
            editModal: false,
            createModal: false,
            deleteModal: false,
            activeRow: null,
            editForm: { name: '', email: '', phone: '', address: '', is_active: true },
            createForm: { name: '', email: '', phone: '', address: '', is_active: true },
            get filteredRows() {
                const q = this.search.trim().toLowerCase();
                return this.rows.filter((r) => {
                    const statusLabel = r.is_active ? 'Activo' : 'Inactivo';
                    const matchStatus = this.selectedStatus === 'Todos' || statusLabel === this.selectedStatus;
                    const matchQuery = !q || [r.name, r.email, r.phone, r.address].some((v) => String(v || '').toLowerCase().includes(q));
                    return matchStatus && matchQuery;
                });
            },
            resetFilters() {
                this.selectedStatus = 'Todos';
                this.search = '';
            },
            openView(row) {
                this.activeRow = row;
                this.viewModal = true;
            },
            openEdit(row) {
                this.activeRow = row;
                this.editForm = { ...row };
                this.editModal = true;
            },
            saveEdit() {
                const idx = this.rows.findIndex((r) => r.email === this.activeRow.email && r.name === this.activeRow.name);
                if (idx !== -1) {
                    this.rows[idx] = { ...this.rows[idx], ...this.editForm };
                }
                this.editModal = false;
                this.$dispatch('notify', { message: `Cliente ${this.editForm.name} actualizado en la maqueta.`, type: 'success' });
            },
            openCreate() {
                this.createModal = true;
            },
            saveCreate() {
                if (!this.createForm.name.trim()) return;
                const name = this.createForm.name.trim();
                this.rows.unshift({
                    name,
                    email: this.createForm.email.trim() || 'cliente@correo.com',
                    phone: this.createForm.phone.trim() || '7000-0000',
                    address: this.createForm.address.trim() || 'San Salvador, Centro',
                    orders_count: 1,
                    total_spent: 25.00,
                    is_active: Boolean(this.createForm.is_active),
                    last_order: '27 Feb, 2025',
                });
                this.createForm = { name: '', email: '', phone: '', address: '', is_active: true };
                this.createModal = false;
                this.$dispatch('notify', { message: `Cliente ${name} registrado en la vista previa.`, type: 'success' });
            },
            openDelete(row) {
                this.activeRow = row;
                this.deleteModal = true;
            },
            confirmDelete() {
                const name = this.activeRow.name;
                this.rows = this.rows.filter((r) => r.name !== name);
                this.deleteModal = false;
                this.$dispatch('notify', { message: `Cliente ${name} retirado de la maqueta.`, type: 'warning' });
            },
            money(n) {
                return window.lifemedic.money(n);
            },
            statusClass(s) {
                return window.lifemedic.statusClass(s);
            },
            statusDot(s) {
                return window.lifemedic.statusDot(s);
            }
        }"
    >
        <x-page-header
            title="Clientes"
            subtitle="Pacientes y compradores registrados desde la aplicación móvil LifeMedic"
            actionLabel="Nuevo Cliente"
            actionClick="openCreate()"
        />

        {{-- KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            @foreach ($data['kpis'] as $kpi)
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

        {{-- Tabla de Clientes --}}
        <x-card padding="p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['Todos', 'Activo', 'Inactivo'] as $status)
                        <button
                            type="button"
                            @click="selectedStatus = '{{ $status }}'"
                            :class="selectedStatus === '{{ $status }}' ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-zinc-600 border-zinc-200 hover:bg-zinc-50'"
                            class="min-h-11 sm:min-h-9 rounded-[6px] border px-3.5 py-1.5 text-xs font-medium transition-colors cursor-pointer"
                        >
                            {{ $status }}
                        </button>
                    @endforeach
                </div>

                <div class="relative w-full sm:w-80">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" />
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Buscar nombre, correo, teléfono o zona"
                        class="min-h-11 sm:min-h-9 w-full rounded-[6px] border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:bg-white focus:outline-none"
                    />
                </div>
            </div>

            <div class="overflow-x-auto -mx-2 px-2">
                <table class="w-full min-w-[820px] text-left border-collapse">
                    <thead>
                        <tr class="border-y border-zinc-200 bg-zinc-50/70 text-[12px] font-medium text-zinc-500">
                            <th class="py-2.5 px-3">Cliente</th>
                            <th class="py-2.5 px-3">Teléfono</th>
                            <th class="py-2.5 px-3">Dirección de Entrega</th>
                            <th class="py-2.5 px-3">Pedidos</th>
                            <th class="py-2.5 px-3">Total Gastado</th>
                            <th class="py-2.5 px-3">Último Pedido</th>
                            <th class="py-2.5 px-3">Estado</th>
                            <th class="py-2.5 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-[13px]">
                        <template x-for="row in filteredRows" :key="row.email + row.name">
                            <tr class="transition-colors hover:bg-zinc-50/70">
                                <td class="py-3 px-3">
                                    <p class="font-semibold text-zinc-950" x-text="row.name"></p>
                                    <p class="text-[11.5px] text-zinc-500" x-text="row.email"></p>
                                </td>
                                <td class="py-3 px-3 text-zinc-700 tabular-nums" x-text="row.phone"></td>
                                <td class="py-3 px-3 max-w-[230px] truncate text-zinc-600" :title="row.address" x-text="row.address"></td>
                                <td class="py-3 px-3 font-medium text-zinc-800 tabular-nums" x-text="row.orders_count"></td>
                                <td class="py-3 px-3 font-semibold text-zinc-950 tabular-nums" x-text="money(row.total_spent)"></td>
                                <td class="py-3 px-3 text-zinc-500" x-text="row.last_order"></td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-[5px] border px-2 py-0.5 text-[11.5px] font-medium" :class="statusClass(row.is_active ? 'Activo' : 'Inactivo')">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="statusDot(row.is_active ? 'Activo' : 'Inactivo')" aria-hidden="true"></span>
                                        <span x-text="row.is_active ? 'Activo' : 'Inactivo'"></span>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <x-action-buttons viewClick="openView(row)" editClick="openEdit(row)" deleteClick="openDelete(row)" />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-cloak x-show="filteredRows.length === 0" class="py-10 text-center">
                <p class="text-sm font-medium text-zinc-500">No se encontraron clientes que coincidan con el filtro.</p>
                <button type="button" @click="resetFilters()" class="mt-3 inline-flex items-center rounded-[6px] border border-zinc-200 px-3.5 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">
                    Ver todos los clientes
                </button>
            </div>
        </x-card>

        {{-- Modales --}}
        <x-modal name="viewModal" title="Perfil e Historial del Cliente" maxWidth="max-w-md">
            <template x-if="activeRow">
                <div class="space-y-3 text-sm">
                    <div class="rounded-[6px] bg-zinc-50 p-4 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-semibold text-zinc-950" x-text="activeRow.name"></h4>
                            <span class="inline-flex items-center gap-1.5 rounded-[5px] border px-2 py-0.5 text-[11px] font-medium" :class="statusClass(activeRow.is_active ? 'Activo' : 'Inactivo')">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="statusDot(activeRow.is_active ? 'Activo' : 'Inactivo')" aria-hidden="true"></span>
                                <span x-text="activeRow.is_active ? 'Activo' : 'Inactivo'"></span>
                            </span>
                        </div>
                        <p class="text-xs text-zinc-600" x-text="activeRow.email + ' · ' + activeRow.phone"></p>
                        <p class="text-xs text-zinc-500" x-text="activeRow.address"></p>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-[11px] text-zinc-400">Pedidos App</p>
                            <p class="text-sm font-semibold text-zinc-950 tabular-nums" x-text="activeRow.orders_count"></p>
                        </div>
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-[11px] text-zinc-400">Acumulado</p>
                            <p class="text-sm font-semibold text-[#095d52] tabular-nums" x-text="money(activeRow.total_spent)"></p>
                        </div>
                        <div class="rounded-[6px] border border-zinc-200 p-2.5">
                            <p class="text-[11px] text-zinc-400">Último Pedido</p>
                            <p class="text-xs font-medium text-zinc-800" x-text="activeRow.last_order"></p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="button" @click="viewModal = false" class="rounded-[6px] bg-zinc-900 px-4 py-2 text-xs font-medium text-white cursor-pointer">Cerrar</button>
                    </div>
                </div>
            </template>
        </x-modal>

        <x-modal name="editModal" title="Editar Datos del Cliente" maxWidth="max-w-md">
            <form @submit.prevent="saveEdit()" class="space-y-4 text-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Nombre Completo</label>
                    <input type="text" x-model="editForm.name" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Correo</label>
                        <input type="email" x-model="editForm.email" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Teléfono</label>
                        <input type="text" x-model="editForm.phone" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Dirección de Entrega</label>
                    <input type="text" x-model="editForm.address" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                </div>
                <label class="inline-flex items-center gap-2 text-xs font-medium text-zinc-700 cursor-pointer">
                    <input type="checkbox" x-model="editForm.is_active" class="h-4 w-4 rounded border-zinc-300 text-[#095d52]" />
                    <span>Cuenta activa en aplicación móvil</span>
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Cambios</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="createModal" title="Registrar Nuevo Cliente" maxWidth="max-w-md">
            <form @submit.prevent="saveCreate()" class="space-y-4 text-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Nombre Completo</label>
                    <input type="text" required x-model="createForm.name" placeholder="Ej. Lucía Ramírez" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Correo</label>
                        <input type="email" x-model="createForm.email" placeholder="cliente@correo.com" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Teléfono</label>
                        <input type="text" x-model="createForm.phone" placeholder="7890-0000" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Dirección Principal</label>
                    <input type="text" x-model="createForm.address" placeholder="Colonia, calle y número de casa" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Cliente</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="deleteModal" title="Confirmar Eliminación" maxWidth="max-w-md">
            <div class="space-y-4 text-sm">
                <p class="text-zinc-600">¿Deseas quitar a <strong class="font-semibold text-zinc-950" x-text="activeRow?.name"></strong> de esta vista previa?</p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="deleteModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="button" @click="confirmDelete()" class="rounded-[6px] bg-red-600 px-4 py-2 text-xs font-medium text-white hover:bg-red-700 cursor-pointer">Eliminar</button>
                </div>
            </div>
        </x-modal>
    </div>
</x-layouts.app>
