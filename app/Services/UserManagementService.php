<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserManagementService
{
    private const META_CACHE_KEY = 'lifemedic.users.role_metadata';

    /**
     * Catálogo de roles administrativos y de clientes disponibles para asignar.
     *
     * @return array<string, array<string, string>>
     */
    public function roles(): array
    {
        return [
            Role::ADMIN => [
                'key' => Role::ADMIN,
                'api_name' => Role::API_ADMIN,
                'label' => 'Administrador',
                'description' => 'Control total de usuarios, catálogo, precios y seguimiento de órdenes.',
            ],
            Role::PHARMACIST => [
                'key' => Role::PHARMACIST,
                'api_name' => Role::API_PHARMACIST,
                'label' => 'Farmacéutico',
                'description' => 'Gestión de medicamentos, despacho de pedidos y consultas clínicas.',
            ],
            Role::CLIENT => [
                'key' => Role::CLIENT,
                'api_name' => Role::API_CLIENT,
                'label' => 'Cliente',
                'description' => 'Cuenta de paciente/comprador registrada en la aplicación móvil.',
            ],
        ];
    }

    /**
     * Valida y normaliza una clave o alias de rol al slug canónico.
     */
    public function normalizeRole(?string $role): string
    {
        $slug = str_replace(['-', ' '], '_', mb_strtolower(Str::ascii(trim((string) $role))));
        $mapped = Role::SLUG_ALIASES[$slug] ?? $slug;

        return array_key_exists($mapped, $this->roles()) ? $mapped : Role::CLIENT;
    }

    /**
     * Devuelve metadatos operativos (rol y estado activo) asociados a un usuario.
     *
     * @return array{role: string, role_label: string, is_active: bool}
     */
    public function metadataForUser(int $userId): array
    {
        $roles = $this->roles();

        if (Schema::hasTable('users') && Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id')) {
            $user = User::query()->with('role')->find($userId);

            if ($user !== null) {
                $rawRole = $user->role?->slug ?: $user->role?->name;
                $roleKey = $this->normalizeRole($rawRole);

                return [
                    'role' => $roleKey,
                    'role_label' => $roles[$roleKey]['label'],
                    'is_active' => (bool) ($user->is_active ?? true),
                ];
            }
        }

        $all = $this->allMetadata();
        $entry = $all[$userId] ?? null;

        if (is_array($entry)) {
            $roleKey = $this->normalizeRole($entry['role'] ?? Role::CLIENT);

            return [
                'role' => $roleKey,
                'role_label' => $roles[$roleKey]['label'],
                'is_active' => array_key_exists('is_active', $entry) ? (bool) $entry['is_active'] : true,
            ];
        }

        return [
            'role' => Role::CLIENT,
            'role_label' => $roles[Role::CLIENT]['label'],
            'is_active' => true,
        ];
    }

    /**
     * Crea una nueva cuenta de usuario real en tabla `users` y guarda su rol inicial.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createUser(array $attributes): User
    {
        $roleSlug = $this->normalizeRole($attributes['role'] ?? Role::CLIENT);
        $isActive = array_key_exists('is_active', $attributes) ? (bool) $attributes['is_active'] : true;
        $role = $this->resolveOrCreateRole($roleSlug);

        $user = User::query()->create([
            'role_id' => $role->id,
            'name' => $attributes['name'],
            'email' => mb_strtolower(trim((string) $attributes['email'])),
            'phone' => $attributes['phone'] ?? null,
            'address' => $attributes['address'] ?? null,
            'password' => $attributes['password'],
            'is_active' => $isActive,
        ]);

        $this->saveMetadata($user->id, [
            'role' => $roleSlug,
            'is_active' => $isActive,
        ]);

        return $user->load('role');
    }

    /**
     * Actualiza datos de cuenta, rol o estado activo de un usuario existente.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateUser(User $user, array $attributes): User
    {
        $updates = [
            'name' => $attributes['name'] ?? $user->name,
            'email' => array_key_exists('email', $attributes)
                ? mb_strtolower(trim((string) $attributes['email']))
                : $user->email,
        ];

        if (array_key_exists('phone', $attributes)) {
            $updates['phone'] = $attributes['phone'];
        }

        if (array_key_exists('address', $attributes)) {
            $updates['address'] = $attributes['address'];
        }

        if (! empty($attributes['password'])) {
            $updates['password'] = $attributes['password'];
        }

        $user->update($updates);

        $current = $this->metadataForUser($user->id);
        $isActive = array_key_exists('is_active', $attributes) ? (bool) $attributes['is_active'] : $current['is_active'];

        $this->saveMetadata($user->id, [
            'role' => array_key_exists('role', $attributes)
                ? $this->normalizeRole((string) $attributes['role'])
                : $current['role'],
            'is_active' => $isActive,
        ]);

        if (! $isActive && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return $user->fresh('role');
    }

    /**
     * Asigna únicamente el rol a un usuario registrado.
     *
     * @return array{role: string, role_label: string, is_active: bool}
     */
    public function assignRole(User $user, string $role): array
    {
        $current = $this->metadataForUser($user->id);
        $normalized = $this->normalizeRole($role);

        $this->saveMetadata($user->id, [
            'role' => $normalized,
            'is_active' => $current['is_active'],
        ]);

        $user->refresh();

        return $this->metadataForUser($user->id);
    }

    /**
     * Activa o desactiva la cuenta de usuario y revoca tokens si se desactiva.
     *
     * @return array{role: string, role_label: string, is_active: bool}
     */
    public function setActiveStatus(User $user, bool $active): array
    {
        $current = $this->metadataForUser($user->id);

        $this->saveMetadata($user->id, [
            'role' => $current['role'],
            'is_active' => $active,
        ]);

        $user->refresh();

        if (! $active && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return $this->metadataForUser($user->id);
    }

    /**
     * Elimina la cuenta de usuario, sus tokens Sanctum y su metadata asociada.
     */
    public function deleteUser(User $user): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $all = $this->allMetadata();
        unset($all[$user->id]);
        Cache::forever(self::META_CACHE_KEY, $all);

        $user->delete();
    }

    public function resolveOrCreateRole(string $role): Role
    {
        $slug = $this->normalizeRole($role);
        $apiName = Role::apiNameFor($slug);
        $definition = $this->roles()[$slug] ?? $this->roles()[Role::CLIENT];

        $existing = Role::query()
            ->where('slug', $slug)
            ->orWhere('name', $apiName)
            ->first();

        if ($existing !== null) {
            if ($existing->slug !== $slug) {
                $existing->update(['slug' => $slug, 'is_active' => true]);
            }

            return $existing;
        }

        return Role::query()->create([
            'slug' => $slug,
            'name' => $apiName,
            'description' => $definition['label'].' — '.$definition['description'],
            'is_active' => true,
        ]);
    }

    /**
     * @param  array{role: string, is_active: bool}  $meta
     */
    private function saveMetadata(int $userId, array $meta): void
    {
        $all = $this->allMetadata();
        $all[$userId] = $meta;
        Cache::forever(self::META_CACHE_KEY, $all);

        if (! Schema::hasTable('users')) {
            return;
        }

        $updates = [];
        if (Schema::hasColumn('users', 'is_active')) {
            $updates['is_active'] = (bool) $meta['is_active'];
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id')) {
            $role = $this->resolveOrCreateRole($meta['role']);
            $updates['role_id'] = $role->id;
        }

        if ($updates !== []) {
            User::query()->whereKey($userId)->update($updates);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allMetadata(): array
    {
        $raw = Cache::get(self::META_CACHE_KEY, []);

        return is_array($raw) ? $raw : [];
    }
}
