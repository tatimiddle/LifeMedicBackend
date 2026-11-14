<x-layouts.app title="Ventas">
    <div
        class="space-y-5"
        x-data="{
            rows: {{ \Illuminate\Support\Js::from($data['items']) }},
            selectedMethod: 'Todos',
            selectedStatus: 'Todos',
            search: '',
            viewModal: false,
            editModal: false,
            createModal: false,
            deleteModal: false,
            activeRow: null,
            editForm: { status: 'Completada', method: 'Tarjeta' },
            createForm: { customer: 'Cliente de Mostrador', items_count: 2, method: 'Tarjeta', total: 24.50 },
            get filteredRows() {
                const q = this.search.trim().toLowerCase();
                return this.rows.filter((r) => {
                    const matchMethod = this.selectedMethod === 'Todos' || r.method === this.selectedMethod;
                    const matchStatus = this.selectedStatus === 'Todos' || r.status === this.selectedStatus;
                    const matchQuery = !q || [r.code, r.customer, r.method].some((v) => String(v || '').toLowerCase().includes(q));
                    return matchMethod && matchStatus && matchQuery;
                });
            },
            resetFilters() {
                this.selectedMethod = 'Todos';
                this.selectedStatus = 'Todos';
                this.search = '';
            },
            openView(row) {
                this.activeRow = row;
                this.viewModal = true;
            },
            openEdit(row) {
                this.activeRow = row;
                this.editForm = { status: row.status, method: row.method };
                this.editModal = true;
            },
            saveEdit() {
                const idx = this.rows.findIndex((r) => r.code === this.activeRow.code);
                if (idx !== -1) {
                    this.rows[idx].status = this.editForm.status;
                    this.rows[idx].method = this.editForm.method;
                }
                this.editModal = false;
                this.$dispatch('notify', { message: `Ticket ${this.activeRow.code} actualizado en la maqueta.`, type: 'success' });
            },
            openCreate() {
                this.createModal = true;
            },
            saveCreate() {
                const code = '#V-00' + (419 + this.rows.length);
                this.rows.unshift({
                    code,
                    date: '27 Feb, 2025',
                    customer: this.createForm.customer || 'Mostrador General',
                    items_count: Number(this.createForm.items_count) || 1,
                    method: this.createForm.method,
                    total: Math.max(0, Number(this.createForm.total) || 0),
                    status: 'Completada',
                });
                this.createModal = false;
                this.$dispatch('notify', { message: `Venta ${code} registrada en la vista previa.`, type: 'success' });
            },
            openDelete(row) {
                this.activeRow = row;
                this.deleteModal = true;
            },
            confirmDelete() {
                const code = this.activeRow.code;
                this.rows = this.rows.filter((r) => r.code !== code);
                this.deleteModal = false;
                this.$dispatch('notify', { message: `Ticket ${code} quitado de la vista previa.`, type: 'warning' });
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
            title="Ventas"
            subtitle="Control de tickets emitidos en farmacia y mezcla de pagos"
            actionLabel="Nueva Venta"
            actionClick="openCreate()"
        />

        {{-- KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3.5">
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

        {{-- Distribución por Método de Pago --}}
        <x-card padding="p-5">
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[14px] font-semibold text-zinc-950">Distribución por Método de Pago</h2>
                <span class="text-xs text-zinc-500">Haz clic en un método para filtrar los tickets</span>
            </div>

            <div class="flex h-2.5 w-full overflow-hidden rounded-[3px] bg-zinc-100">
                @foreach ($data['payment_mix'] as $mix)
                    <button
                        type="button"
                        @click="selectedMethod = selectedMethod === '{{ $mix['method'] }}' ? 'Todos' : '{{ $mix['method'] }}'"
                        title="{{ $mix['method'] }}: {{ $mix['pct'] }}%"
                        style="width: {{ $mix['pct'] }}%; background-color: {{ $mix['color'] }};"
                        class="h-full transition-opacity hover:opacity-85 cursor-pointer"
                    ></button>
                @endforeach
            </div>

            <div class="mt-3.5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($data['payment_mix'] as $mix)
                    <button
                        type="button"
                        @click="selectedMethod = selectedMethod === '{{ $mix['method'] }}' ? 'Todos' : '{{ $mix['method'] }}'"
                        :class="selectedMethod === '{{ $mix['method'] }}' ? 'border-[#095d52] bg-zinc-50' : 'border-zinc-200/80 bg-white hover:bg-zinc-50/70'"
                        class="flex items-center justify-between rounded-[6px] border px-3.5 py-2.5 text-left transition-colors cursor-pointer"
                    >
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $mix['color'] }}"></span>
                            <span class="text-xs font-medium text-zinc-800">{{ $mix['method'] }}</span>
                        </div>
                        <div class="text-right tabular-nums">
                            <span class="text-xs font-semibold text-zinc-950">${{ number_format($mix['amount'], 2) }}</span>
                            <span class="ml-1 text-[11px] font-medium text-zinc-500">({{ $mix['pct'] }}%)</span>
                        </div>
                    </button>
                @endforeach
            </div>
        </x-card>

        {{-- Tabla de Tickets --}}
        <x-card padding="p-5">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['Todos', 'Tarjeta', 'Efectivo', 'Transferencia'] as $method)
                        <button
                            type="button"
                            @click="selectedMethod = '{{ $method }}'"
                            :class="selectedMethod === '{{ $method }}' ? 'bg-zinc-900 text-white border-zinc-900' : 'bg-white text-zinc-600 border-zinc-200 hover:bg-zinc-50'"
                            class="min-h-11 sm:min-h-9 rounded-[6px] border px-3.5 py-1.5 text-xs font-medium transition-colors cursor-pointer"
                        >
                            {{ $method }}
                        </button>
                    @endforeach
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" />
                        <input
                            type="search"
                            x-model="search"
                            placeholder="Buscar ticket o cliente"
                            class="min-h-11 sm:min-h-9 w-full sm:w-64 rounded-[6px] border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:bg-white focus:outline-none"
                        />
                    </div>
                    <select x-model="selectedStatus" aria-label="Filtrar por estado" class="min-h-11 sm:min-h-9 rounded-[6px] border border-zinc-200 bg-white px-3 text-xs font-medium text-zinc-700 focus:border-[#095d52] focus:outline-none">
                        <option value="Todos">Todos los estados</option>
                        <option value="Completada">Completada</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Reembolsada">Reembolsada</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto -mx-2 px-2">
                <table class="w-full min-w-[740px] text-left border-collapse">
                    <thead>
                        <tr class="border-y border-zinc-200 bg-zinc-50/70 text-[12px] font-medium text-zinc-500">
                            <th class="py-2.5 px-3">Ticket</th>
                            <th class="py-2.5 px-3">Fecha</th>
                            <th class="py-2.5 px-3">Cliente</th>
                            <th class="py-2.5 px-3">Artículos</th>
                            <th class="py-2.5 px-3">Método</th>
                            <th class="py-2.5 px-3">Total</th>
                            <th class="py-2.5 px-3">Estado</th>
                            <th class="py-2.5 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-[13px]">
                        <template x-for="row in filteredRows" :key="row.code">
                            <tr class="transition-colors hover:bg-zinc-50/70">
                                <td class="py-3 px-3 font-semibold text-zinc-900 tabular-nums" x-text="row.code"></td>
                                <td class="py-3 px-3 text-zinc-500" x-text="row.date"></td>
                                <td class="py-3 px-3 font-medium text-zinc-800" x-text="row.customer"></td>
                                <td class="py-3 px-3 text-zinc-600 tabular-nums" x-text="row.items_count + ' uds.'"></td>
                                <td class="py-3 px-3 font-medium text-zinc-700" x-text="row.method"></td>
                                <td class="py-3 px-3 font-semibold text-zinc-950 tabular-nums" x-text="money(row.total)"></td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-[5px] border px-2 py-0.5 text-[11.5px] font-medium" :class="statusClass(row.status)">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="statusDot(row.status)" aria-hidden="true"></span>
                                        <span x-text="row.status"></span>
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
                <p class="text-sm font-medium text-zinc-500">No se encontraron ventas con los filtros actuales.</p>
                <button type="button" @click="resetFilters()" class="mt-3 inline-flex items-center rounded-[6px] border border-zinc-200 px-3.5 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">
                    Restablecer filtros
                </button>
            </div>
        </x-card>

        {{-- Modales --}}
        <x-modal name="viewModal" title="Detalle del Ticket de Venta" maxWidth="max-w-md">
            <template x-if="activeRow">
                <div class="space-y-3 text-sm">
                    <div class="grid grid-cols-2 gap-3 rounded-[6px] bg-zinc-50 p-4">
                        <div>
                            <p class="text-[11px] font-medium text-zinc-500">Ticket</p>
                            <p class="font-semibold text-zinc-950 tabular-nums" x-text="activeRow.code"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-zinc-500">Fecha</p>
                            <p class="text-zinc-700" x-text="activeRow.date"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-zinc-500">Cliente</p>
                            <p class="font-medium text-zinc-900" x-text="activeRow.customer"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-zinc-500">Método de Pago</p>
                            <p class="font-medium text-zinc-800" x-text="activeRow.method"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-zinc-500">Artículos</p>
                            <p class="font-medium text-zinc-800" x-text="activeRow.items_count"></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-zinc-500">Total Cobrado</p>
                            <p class="font-semibold text-[#095d52] tabular-nums" x-text="money(activeRow.total)"></p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-1">
                        <button type="button" @click="viewModal = false" class="rounded-[6px] bg-zinc-900 px-4 py-2 text-xs font-medium text-white cursor-pointer">Cerrar</button>
                    </div>
                </div>
            </template>
        </x-modal>

        <x-modal name="editModal" title="Actualizar Ticket" maxWidth="max-w-md">
            <form @submit.prevent="saveEdit()" class="space-y-4 text-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Método de Pago</label>
                    <select x-model="editForm.method" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none">
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Estado</label>
                    <select x-model="editForm.status" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none">
                        <option value="Completada">Completada</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Reembolsada">Reembolsada</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Cambios</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="createModal" title="Registrar Nueva Venta" maxWidth="max-w-md">
            <form @submit.prevent="saveCreate()" class="space-y-4 text-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Cliente</label>
                    <input type="text" x-model="createForm.customer" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Artículos</label>
                        <input type="number" min="1" x-model.number="createForm.items_count" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Método</label>
                        <select x-model="createForm.method" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none">
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Transferencia">Transferencia</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Total ($)</label>
                        <input type="number" step="0.01" min="0" x-model.number="createForm.total" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Emitir Ticket</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="deleteModal" title="Confirmar Eliminación" maxWidth="max-w-md">
            <div class="space-y-4 text-sm">
                <p class="text-zinc-600">¿Deseas retirar el ticket <strong class="font-semibold text-zinc-950" x-text="activeRow?.code"></strong> de la maqueta?</p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="deleteModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="button" @click="confirmDelete()" class="rounded-[6px] bg-red-600 px-4 py-2 text-xs font-medium text-white hover:bg-red-700 cursor-pointer">Eliminar</button>
                </div>
            </div>
        </x-modal>
    </div>
</x-layouts.app>
