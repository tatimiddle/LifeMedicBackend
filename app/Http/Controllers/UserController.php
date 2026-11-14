<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagement
    ) {}

    /**
     * Lista usuarios registrados con filtros por rol, estado y búsqueda.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $rawRole = trim((string) $request->query('rol', ''));
        $roleFilter = $rawRole !== '' ? Role::resolveKnownSlug($rawRole) : null;
        $statusFilter = mb_strtolower(trim((string) $request->query('estado', '')));

        $query = User::query()
            ->with('role')
            ->withCount('orders');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $like = '%'.$search.'%';
                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('address', 'like', $like);
            });
        }

        if ($roleFilter !== null) {
            $query->whereHas('role', function (Builder $roleQuery) use ($roleFilter): void {
                $roleQuery->where('slug', $roleFilter);
            });
        }

        if (in_array($statusFilter, ['1', 'activo', 'activos'], true)) {
            $query->where('is_active', true);
            $normalizedStatus = 'activos';
        } elseif (in_array($statusFilter, ['0', 'inactivo', 'inactivos'], true)) {
            $query->where('is_active', false);
            $normalizedStatus = 'inactivos';
        } else {
            $normalizedStatus = '';
        }

        $users = $query
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $roles = $this->userManagement->roles();
        $userMetadata = $users->getCollection()->mapWithKeys(function (User $user): array {
            return [$user->id => $this->userManagement->metadataForUser($user->id)];
        });

        $summary = [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
            'admins' => User::query()->whereHas('role', fn (Builder $q) => $q->where('slug', Role::ADMIN))->count(),
            'pharmacists' => User::query()->whereHas('role', fn (Builder $q) => $q->where('slug', Role::PHARMACIST))->count(),
            'clients' => User::query()->whereHas('role', fn (Builder $q) => $q->where('slug', Role::CLIENT))->count(),
        ];

        return view('modules.usuarios', [
            'users' => $users,
            'roles' => $roles,
            'userMetadata' => $userMetadata,
            'summary' => $summary,
            'filters' => [
                'q' => $search,
                'rol' => $roleFilter ?? '',
                'estado' => $normalizedStatus,
            ],
        ]);
    }

    /**
     * Crea una nueva cuenta de usuario con su rol asignado.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = $this->userManagement->createUser([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('status_message', "Usuario {$user->name} registrado correctamente.");
    }

    /**
     * Actualiza los datos, rol o estado de un usuario existente.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $targetActive = $request->boolean('is_active', true);
        $targetRole = $this->userManagement->normalizeRole($validated['role']);

        if ((int) $request->user()->id === (int) $user->id) {
            if (! $targetActive || $targetRole !== Role::ADMIN) {
                throw ValidationException::withMessages([
                    'user' => ['No puedes desactivar o cambiar el rol de tu propia cuenta administrativa activa.'],
                ]);
            }
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'role' => $targetRole,
            'is_active' => $targetActive,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $updated = $this->userManagement->updateUser($user, $payload);

        return redirect()
            ->route('usuarios.index')
            ->with('status_message', "Usuario {$updated->name} actualizado correctamente.");
    }

    /**
     * Asigna o cambia el rol de un usuario registrado.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'max:50'],
        ]);

        $normalized = $this->userManagement->normalizeRole($validated['role']);

        if ((int) $request->user()->id === (int) $user->id && $normalized !== Role::ADMIN) {
            throw ValidationException::withMessages([
                'user' => ['No puedes retirar tu propio rol de administrador.'],
            ]);
        }

        $meta = $this->userManagement->assignRole($user, $normalized);

        return redirect()
            ->route('usuarios.index')
            ->with('status_message', "Rol de {$user->name} actualizado a {$meta['role_label']}.");
    }

    /**
     * Activa o desactiva la cuenta de un usuario y revoca sus tokens si se desactiva.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $currentMeta = $this->userManagement->metadataForUser($user->id);
        $targetActive = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $currentMeta['is_active'];

        if ((int) $request->user()->id === (int) $user->id && ! $targetActive) {
            throw ValidationException::withMessages([
                'user' => ['No puedes desactivar tu propia cuenta administrativa activa.'],
            ]);
        }

        $this->userManagement->setActiveStatus($user, $targetActive);
        $label = $targetActive ? 'activado' : 'desactivado';

        return redirect()
            ->route('usuarios.index')
            ->with('status_message', "Usuario {$user->name} {$label} correctamente.");
    }

    /**
     * Elimina la cuenta de un usuario registrado (con protección para el admin actual).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return redirect()
                ->back()
                ->withErrors(['user' => 'No puedes eliminar tu propia cuenta administrativa en sesión.']);
        }

        $userName = $user->name;
        $this->userManagement->deleteUser($user);

        return redirect()
            ->route('usuarios.index')
            ->with('status_message', "Usuario {$userName} eliminado del sistema.");
    }
}
