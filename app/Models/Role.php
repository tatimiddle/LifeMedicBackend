<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'administrador';

    public const PHARMACIST = 'farmaceutico';

    public const CLIENT = 'cliente';

    public const API_ADMIN = 'admin';

    public const API_PHARMACIST = 'pharmacist';

    public const API_CLIENT = 'customer';

    /**
     * @var array<string, string>
     */
    public const SLUG_ALIASES = [
        'admin' => self::ADMIN,
        'administrador' => self::ADMIN,
        'pharmacist' => self::PHARMACIST,
        'farmaceutico' => self::PHARMACIST,
        'customer' => self::CLIENT,
        'cliente' => self::CLIENT,
    ];

    /**
     * @var array<string, string>
     */
    public const SLUG_TO_NAME = [
        self::ADMIN => self::API_ADMIN,
        self::PHARMACIST => self::API_PHARMACIST,
        self::CLIENT => self::API_CLIENT,
    ];

    protected $fillable = ['slug', 'name', 'description', 'is_active'];

    protected static function booted(): void
    {
        static::saving(function (Role $role): void {
            if (empty($role->slug) && ! empty($role->name)) {
                $key = str_replace(['-', ' '], '_', mb_strtolower(Str::ascii(trim((string) $role->name))));
                $role->slug = self::SLUG_ALIASES[$key] ?? Str::slug((string) $role->name);
            }
        });
    }

    public static function resolveKnownSlug(?string $role): ?string
    {
        $key = str_replace(['-', ' '], '_', mb_strtolower(Str::ascii(trim((string) $role))));

        return $key !== '' ? (self::SLUG_ALIASES[$key] ?? null) : null;
    }

    public static function normalizeSlug(?string $role): string
    {
        return self::resolveKnownSlug($role) ?? self::CLIENT;
    }

    public static function apiNameFor(?string $role): string
    {
        $slug = self::normalizeSlug($role);

        return self::SLUG_TO_NAME[$slug] ?? self::API_CLIENT;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
