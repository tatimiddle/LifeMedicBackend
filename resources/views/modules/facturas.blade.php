<x-layouts.app title="Facturas">
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
            editForm: { status: 'Pagada', shipping: 0 },
            createForm: { customer: '', order_number: 'LM-2026-00502', subtotal: 35.00, shipping: 0.00, status: 'Pagada' },
            get filteredRows() {
                const q = this.search.trim().toLowerCase();
                return this.rows.filter((r) => {
                    const matchStatus = this.selectedStatus === 'Todos' || r.status === this.selectedStatus;
                    const matchQuery = !q || [r.number, r.order_number, r.customer].some((v) => String(v || '').toLowerCase().includes(q));
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
                this.editForm = { status: row.status, shipping: row.shipping };
                this.editModal = true;
            },
            saveEdit() {
                const idx = this.rows.findIndex((r) => r.number === this.activeRow.number);
                if (idx !== -1) {
                    const shipping = Math.max(0, Number(this.editForm.shipping) || 0);
                    this.rows[idx].status = this.editForm.status;
                    this.rows[idx].shipping = shipping;
                    this.rows[idx].total = Number((this.rows[idx].subtotal + shipping + this.rows[idx].tax).toFixed(2));
                }
                this.editModal = false;
                this.$dispatch('notify', { message: `Factura ${this.activeRow.number} actualizada en la maqueta.`, type: 'success' });
            },
            openCreate() {
                this.createModal = true;
            },
            saveCreate() {
                if (!this.createForm.customer.trim()) return;
                const num = '#FAC-0' + (986 + this.rows.length);
                const subtotal = Math.max(0, Number(this.createForm.subtotal) || 0);
                const shipping = Math.max(0, Number(this.createForm.shipping) || 0);
                const tax = Number((subtotal * 0.13).toFixed(2));
                this.rows.unshift({
                    number: num,
                    order_number: this.createForm.order_number.trim() || 'LM-2026-00509',
                    customer: this.createForm.customer.trim(),
                    date: '27 Feb, 2025',
                    subtotal,
                    shipping,
                    tax,
                    total: Number((subtotal + shipping + tax).toFixed(2)),
                    status: this.createForm.status,
                });
                this.createForm.customer = '';
                this.createModal = false;
                this.$dispatch('notify', { message: `Factura ${num} generada en la vista previa.`, type: 'success' });
            },
            openDelete(row) {
                this.activeRow = row;
                this.deleteModal = true;
            },
            confirmDelete() {
                const num = this.activeRow.number;
                this.rows = this.rows.filter((r) => r.number !== num);
                this.deleteModal = false;
                this.$dispatch('notify', { message: `Factura ${num} retirada de la vista previa.`, type: 'warning' });
            },
            simulatePrint(row) {
                this.$dispatch('notify', { message: `Comprobante ${row.number} listo para descarga simulada.`, type: 'success' });
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
            title="Facturas"
            subtitle="Comprobantes fiscales electrónicos asociados a ventas y pedidos de la app"
            actionLabel="Nueva Factura"
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

        {{-- Listado de Facturas --}}
        <x-card padding="p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['Todos', 'Pagada', 'Pendiente', 'Vencida'] as $status)
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

                <div class="relative w-full sm:w-72">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" />
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Buscar #FAC, pedido LM- o cliente"
                        class="min-h-11 sm:min-h-9 w-full rounded-[6px] border border-zinc-200 bg-zinc-50 pl-9 pr-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:bg-white focus:outline-none"
                    />
                </div>
            </div>

            <div class="overflow-x-auto -mx-2 px-2">
                <table class="w-full min-w-[840px] text-left border-collapse">
                    <thead>
                        <tr class="border-y border-zinc-200 bg-zinc-50/70 text-[12px] font-medium text-zinc-500">
                            <th class="py-2.5 px-3">Factura</th>
                            <th class="py-2.5 px-3">Pedido App</th>
                            <th class="py-2.5 px-3">Cliente</th>
                            <th class="py-2.5 px-3">Fecha</th>
                            <th class="py-2.5 px-3">Subtotal</th>
                            <th class="py-2.5 px-3">Envío</th>
                            <th class="py-2.5 px-3">IVA</th>
                            <th class="py-2.5 px-3">Total</th>
                            <th class="py-2.5 px-3">Estado</th>
                            <th class="py-2.5 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-[13px]">
                        <template x-for="row in filteredRows" :key="row.number">
                            <tr class="transition-colors hover:bg-zinc-50/70">
                                <td class="py-3 px-3 font-semibold text-zinc-950 tabular-nums" x-text="row.number"></td>
                                <td class="py-3 px-3 font-semibold text-[#095d52] tabular-nums" x-text="row.order_number"></td>
                                <td class="py-3 px-3 font-medium text-zinc-800" x-text="row.customer"></td>
                                <td class="py-3 px-3 text-zinc-500" x-text="row.date"></td>
                                <td class="py-3 px-3 text-zinc-700 tabular-nums" x-text="money(row.subtotal)"></td>
                                <td class="py-3 px-3 tabular-nums" :class="row.shipping === 0 ? 'text-[#0f766e] font-medium' : 'text-zinc-700'" x-text="row.shipping === 0 ? 'Gratis' : money(row.shipping)"></td>
                                <td class="py-3 px-3 text-zinc-600 tabular-nums" x-text="money(row.tax)"></td>
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
                <p class="text-sm font-medium text-zinc-500">No se encontraron facturas con los filtros seleccionados.</p>
                <button type="button" @click="resetFilters()" class="mt-3 inline-flex items-center rounded-[6px] border border-zinc-200 px-3.5 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">
                    Ver todas las facturas
                </button>
            </div>
        </x-card>

        {{-- Modal Vista Previa de Factura --}}
        <x-modal name="viewModal" title="Vista Previa de Factura" maxWidth="max-w-lg">
            <template x-if="activeRow">
                <div class="space-y-4 text-sm">
                    <div class="rounded-[8px] border border-zinc-200 bg-zinc-50/70 p-5">
                        <div class="flex items-start justify-between border-b border-zinc-200 pb-4">
                            <div>
                                <p class="text-xs font-semibold text-[#095d52]">Farmacia LifeMedic</p>
                                <h4 class="mt-0.5 text-lg font-semibold text-zinc-950 tabular-nums" x-text="activeRow.number"></h4>
                                <p class="text-xs text-zinc-500" x-text="'Pedido vinculado: ' + activeRow.order_number"></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-[5px] border px-2 py-0.5 text-[11px] font-medium" :class="statusClass(activeRow.status)">
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="statusDot(activeRow.status)" aria-hidden="true"></span>
                                    <span x-text="activeRow.status"></span>
                                </span>
                                <p class="mt-1.5 text-xs text-zinc-500" x-text="activeRow.date"></p>
                            </div>
                        </div>

                        <div class="py-3 text-xs">
                            <p class="text-zinc-400">Facturado a</p>
                            <p class="text-sm font-semibold text-zinc-950" x-text="activeRow.customer"></p>
                        </div>

                        <div class="space-y-2 border-t border-zinc-200 pt-3 text-xs">
                            <div class="flex justify-between text-zinc-600">
                                <span>Subtotal gravado</span>
                                <span class="font-medium tabular-nums" x-text="money(activeRow.subtotal)"></span>
                            </div>
                            <div class="flex justify-between text-zinc-600">
                                <span>Envío</span>
                                <span class="font-medium tabular-nums" x-text="activeRow.shipping === 0 ? 'Envío Gratis ($0.00)' : money(activeRow.shipping)"></span>
                            </div>
                            <div class="flex justify-between text-zinc-600">
                                <span>IVA (13%)</span>
                                <span class="font-medium tabular-nums" x-text="money(activeRow.tax)"></span>
                            </div>
                            <div class="flex justify-between border-t border-zinc-200 pt-2 text-sm font-semibold text-zinc-950">
                                <span>Total Facturado</span>
                                <span class="text-[#095d52] tabular-nums" x-text="money(activeRow.total)"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" @click="simulatePrint(activeRow)" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">
                            Simular Impresión / PDF
                        </button>
                        <button type="button" @click="viewModal = false" class="rounded-[6px] bg-zinc-900 px-4 py-2 text-xs font-medium text-white cursor-pointer">
                            Cerrar
                        </button>
                    </div>
                </div>
            </template>
        </x-modal>

        <x-modal name="editModal" title="Editar Estado de Factura" maxWidth="max-w-md">
            <form @submit.prevent="saveEdit()" class="space-y-4 text-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Estado de Cobro</label>
                    <select x-model="editForm.status" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none">
                        <option value="Pagada">Pagada</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Vencida">Vencida</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Costo de Envío ($)</label>
                    <input type="number" step="0.01" min="0" x-model.number="editForm.shipping" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 tabular-nums focus:border-[#095d52] focus:outline-none" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="editModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Guardar Cambios</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="createModal" title="Emitir Nueva Factura" maxWidth="max-w-md">
            <form @submit.prevent="saveCreate()" class="space-y-4 text-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700">Cliente</label>
                    <input type="text" required x-model="createForm.customer" placeholder="Nombre completo del cliente" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Pedido Vinculado</label>
                        <input type="text" x-model="createForm.order_number" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-[#095d52] focus:outline-none" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700">Subtotal ($)</label>
                        <input type="number" step="0.01" min="0" x-model.number="createForm.subtotal" class="w-full rounded-[6px] border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 tabular-nums focus:border-[#095d52] focus:outline-none" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Generar Factura</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="deleteModal" title="Confirmar Eliminación" maxWidth="max-w-md">
            <div class="space-y-4 text-sm">
                <p class="text-zinc-600">¿Deseas quitar la factura <strong class="font-semibold text-zinc-950" x-text="activeRow?.number"></strong> de esta vista previa?</p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="deleteModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-600 cursor-pointer">Cancelar</button>
                    <button type="button" @click="confirmDelete()" class="rounded-[6px] bg-red-600 px-4 py-2 text-xs font-medium text-white hover:bg-red-700 cursor-pointer">Eliminar</button>
                </div>
            </div>
        </x-modal>
    </div>
</x-layouts.app>
