<x-layouts.app title="Usuarios y Roles">
    <div
        class="space-y-5"
        x-data="{
            createModal: false,
            editModal: false,
            deleteModal: false,
            form: {
                id: null,
                name: '',
                email: '',
                phone: '',
                address: '',
                role: 'cliente',
                is_active: true,
            },
            deleteTarget: { id: null, name: '' },
            openCreate() {
                this.form = {
                    id: null,
                    name: '',
                    email: '',
                    phone: '',
                    address: '',
                    role: 'cliente',
                    is_active: true,
                };
                this.createModal = true;
            },
            openEdit(user) {
                this.form = {
                    id: user.id,
                    name: user.name ?? '',
                    email: user.email ?? '',
                    phone: user.phone ?? '',
                    address: user.address ?? '',
                    role: user.role ?? 'cliente',
                    is_active: Boolean(user.is_active),
                };
                this.editModal = true;
            },
            openDelete(user) {
                this.deleteTarget = { id: user.id, name: user.name };
                this.deleteModal = true;
            },
        }"
    >
        <x-page-header
            title="Usuarios y Roles"
            subtitle="Lista y administra las cuentas registradas, controla accesos activos y asigna roles de sistema."
            actionLabel="+ Nuevo usuario"
            actionClick="openCreate()"
        />

        @if (session('status_message'))
            <div class="flex items-center gap-2.5 rounded-[8px] border border-zinc-200 bg-white px-4 py-3 text-xs font-medium text-zinc-800 shadow-xs">
                <span class="h-2 w-2 shrink-0 rounded-full bg-[#095d52]" aria-hidden="true"></span>
                <span>{{ session('status_message') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-[8px] border border-zinc-300 bg-white px-4 py-3 text-xs text-zinc-800" role="alert">
                <p class="font-semibold text-[#dc2626]">No se pudo completar la operación:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-zinc-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-card padding="p-4">
                <x-kpi-mini label="Total Usuarios" :value="(string) $summary['total']" icon="users-role" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Cuentas Activas" :value="(string) $summary['active']" icon="users" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Administradores" :value="(string) $summary['admins']" icon="users-role" :plain="true" />
            </x-card>
            <x-card padding="p-4">
                <x-kpi-mini label="Clientes App" :value="(string) $summary['clients']" icon="users" :plain="true" />
            </x-card>
        </section>

        <x-card padding="p-5">
            <form method="GET" action="{{ route('usuarios.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-5">
                    <label for="q" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1">Buscar usuario</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-zinc-400">
                            <x-icon name="search" class="w-4 h-4" />
                        </span>
                        <input
                            id="q"
                            type="text"
                            name="q"
                            value="{{ $filters['q'] }}"
                            placeholder="Nombre, correo, teléfono o dirección..."
                            class="w-full rounded-[7px] border border-zinc-200 bg-white pl-9 pr-3 py-2 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-[#095d52] focus:outline-hidden"
                        />
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label for="rol" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1">Rol</label>
                    <select
                        id="rol"
                        name="rol"
                        class="w-full rounded-[7px] border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-[#095d52] focus:outline-hidden"
                    >
                        <option value="">Todos los roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role['key'] }}" @selected($filters['rol'] === $role['key'])>{{ $role['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="estado" class="block text-[11px] font-medium uppercase tracking-wider text-zinc-500 mb-1">Estado</label>
                    <select
                        id="estado"
                        name="estado"
                        class="w-full rounded-[7px] border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-[#095d52] focus:outline-hidden"
                    >
                        <option value="">Todos</option>
                        <option value="activos" @selected($filters['estado'] === 'activos')>Activos</option>
                        <option value="inactivos" @selected($filters['estado'] === 'inactivos')>Inactivos</option>
                    </select>
                </div>

                <div class="md:col-span-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 rounded-[7px] bg-[#095d52] px-3.5 py-2 text-xs font-medium text-white hover:bg-[#074a41] transition-colors cursor-pointer"
                    >
                        Filtrar
                    </button>
                    @if ($filters['q'] !== '' || $filters['rol'] !== '' || $filters['estado'] !== '')
                        <a
                            href="{{ route('usuarios.index') }}"
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
                            <th class="py-3 pr-4">Usuario</th>
                            <th class="py-3 px-4">Contacto</th>
                            <th class="py-3 px-4 text-right">Pedidos</th>
                            <th class="py-3 px-4">Asignar Rol</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 pl-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($users as $account)
                            @php
                                $meta = $userMetadata->get($account->id, ['role' => 'cliente', 'role_label' => 'Cliente', 'is_active' => true]);
                                $isCurrentUser = (int) auth()->id() === (int) $account->id;
                                $editPayload = [
                                    'id' => $account->id,
                                    'name' => $account->name,
                                    'email' => $account->email,
                                    'phone' => $account->phone,
                                    'address' => $account->address,
                                    'role' => $meta['role'],
                                    'is_active' => $meta['is_active'],
                                ];
                            @endphp
                            <tr class="hover:bg-zinc-50/70 transition-colors">
                                <td class="py-3.5 pr-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-zinc-900">{{ $account->name }}</span>
                                        @if ($isCurrentUser)
                                            <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-600">Tú</span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-zinc-500">{{ $account->email }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-zinc-700">{{ $account->phone ?: 'Sin teléfono' }}</div>
                                    @if ($account->address)
                                        <div class="text-[11px] text-zinc-500 truncate max-w-[220px]">{{ $account->address }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap text-zinc-700 tabular-nums">
                                    {{ (int) $account->orders_count }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <form method="POST" action="{{ route('usuarios.role', $account) }}" class="inline-flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select
                                            name="role"
                                            aria-label="Rol de {{ $account->name }}"
                                            @disabled($isCurrentUser)
                                            class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-xs text-zinc-800 focus:border-[#095d52] focus:outline-hidden disabled:bg-zinc-50 disabled:text-zinc-500"
                                        >
                                            @foreach ($roles as $roleOption)
                                                <option value="{{ $roleOption['key'] }}" @selected($meta['role'] === $roleOption['key'])>
                                                    {{ $roleOption['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @unless ($isCurrentUser)
                                            <button
                                                type="submit"
                                                class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 hover:bg-zinc-50 hover:text-[#095d52] transition-colors cursor-pointer"
                                            >
                                                Asignar
                                            </button>
                                        @endunless
                                    </form>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <x-status-badge :status="$meta['is_active'] ? 'Activo' : 'Inactivo'" />
                                </td>
                                <td class="py-3.5 pl-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEdit({{ \Illuminate\Support\Js::from($editPayload) }})"
                                            class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 hover:bg-zinc-50 hover:text-[#095d52] transition-colors cursor-pointer"
                                        >
                                            Editar
                                        </button>

                                        @unless ($isCurrentUser)
                                            <form method="POST" action="{{ route('usuarios.status', $account) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $meta['is_active'] ? '0' : '1' }}" />
                                                <button
                                                    type="submit"
                                                    class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 hover:bg-zinc-50 transition-colors cursor-pointer"
                                                >
                                                    {{ $meta['is_active'] ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>

                                            <button
                                                type="button"
                                                @click="openDelete({{ \Illuminate\Support\Js::from(['id' => $account->id, 'name' => $account->name]) }})"
                                                class="rounded-[6px] border border-zinc-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-[#dc2626] hover:bg-red-50 transition-colors cursor-pointer"
                                            >
                                                Eliminar
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-xs text-zinc-500">
                                    No se encontraron usuarios con los criterios aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="mt-4 pt-4 border-t border-zinc-100">
                    {{ $users->links() }}
                </div>
            @endif
        </x-card>

        <x-modal name="createModal" title="Registrar Nuevo Usuario">
            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label for="create_name" class="block font-medium text-zinc-700 mb-1">Nombre completo</label>
                    <input id="create_name" type="text" name="name" x-model="form.name" required class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="create_email" class="block font-medium text-zinc-700 mb-1">Correo electrónico</label>
                        <input id="create_email" type="email" name="email" x-model="form.email" required class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                    <div>
                        <label for="create_phone" class="block font-medium text-zinc-700 mb-1">Teléfono</label>
                        <input id="create_phone" type="text" name="phone" x-model="form.phone" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                </div>
                <div>
                    <label for="create_address" class="block font-medium text-zinc-700 mb-1">Dirección</label>
                    <input id="create_address" type="text" name="address" x-model="form.address" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="create_role" class="block font-medium text-zinc-700 mb-1">Rol asignado</label>
                        <select id="create_role" name="role" x-model="form.role" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden">
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption['key'] }}">{{ $roleOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-zinc-700 cursor-pointer">
                            <input type="hidden" name="is_active" value="0" />
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                            <span>Cuenta activa</span>
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="create_password" class="block font-medium text-zinc-700 mb-1">Contraseña</label>
                        <input id="create_password" type="password" name="password" required class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                    <div>
                        <label for="create_password_confirmation" class="block font-medium text-zinc-700 mb-1">Confirmar contraseña</label>
                        <input id="create_password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-zinc-100">
                    <button type="button" @click="createModal = false" class="rounded-[7px] border border-zinc-200 px-4 py-2 font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[7px] bg-[#095d52] px-4 py-2 font-medium text-white hover:bg-[#074a41] cursor-pointer">Crear usuario</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="editModal" title="Editar Usuario">
            <form method="POST" :action="`/usuarios/${form.id}`" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_name" class="block font-medium text-zinc-700 mb-1">Nombre completo</label>
                    <input id="edit_name" type="text" name="name" x-model="form.name" required class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="edit_email" class="block font-medium text-zinc-700 mb-1">Correo electrónico</label>
                        <input id="edit_email" type="email" name="email" x-model="form.email" required class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                    <div>
                        <label for="edit_phone" class="block font-medium text-zinc-700 mb-1">Teléfono</label>
                        <input id="edit_phone" type="text" name="phone" x-model="form.phone" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                </div>
                <div>
                    <label for="edit_address" class="block font-medium text-zinc-700 mb-1">Dirección</label>
                    <input id="edit_address" type="text" name="address" x-model="form.address" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="edit_role" class="block font-medium text-zinc-700 mb-1">Rol</label>
                        <select id="edit_role" name="role" x-model="form.role" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden">
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption['key'] }}">{{ $roleOption['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-zinc-700 cursor-pointer">
                            <input type="hidden" name="is_active" value="0" />
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]" />
                            <span>Cuenta activa</span>
                        </label>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="edit_password" class="block font-medium text-zinc-700 mb-1">Nueva contraseña (opcional)</label>
                        <input id="edit_password" type="password" name="password" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                    <div>
                        <label for="edit_password_confirmation" class="block font-medium text-zinc-700 mb-1">Confirmar contraseña</label>
                        <input id="edit_password_confirmation" type="password" name="password_confirmation" class="w-full rounded-[7px] border border-zinc-200 px-3 py-2 text-zinc-900 focus:border-[#095d52] focus:outline-hidden" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-zinc-100">
                    <button type="button" @click="editModal = false" class="rounded-[7px] border border-zinc-200 px-4 py-2 font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[7px] bg-[#095d52] px-4 py-2 font-medium text-white hover:bg-[#074a41] cursor-pointer">Guardar cambios</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="deleteModal" title="Eliminar Usuario" maxWidth="max-w-md">
            <form method="POST" :action="`/usuarios/${deleteTarget.id}`" class="space-y-4 text-xs">
                @csrf
                @method('DELETE')
                <p class="text-zinc-600 leading-relaxed">
                    ¿Confirmas que deseas eliminar permanentemente la cuenta de <strong class="text-zinc-900" x-text="deleteTarget.name"></strong>? Esta acción revocará cualquier sesión activa.
                </p>
                <div class="flex justify-end gap-2 pt-3 border-t border-zinc-100">
                    <button type="button" @click="deleteModal = false" class="rounded-[7px] border border-zinc-200 px-4 py-2 font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">Cancelar</button>
                    <button type="submit" class="rounded-[7px] bg-[#dc2626] px-4 py-2 font-medium text-white hover:bg-red-700 cursor-pointer">Eliminar</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
