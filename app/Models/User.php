<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['role_id', 'name', 'email', 'phone', 'address', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class)
            ->where('status', Cart::STATUS_ACTIVE)
            ->latestOfMany();
    }

    public function hasRole(string $role): bool
    {
        $assigned = $this->role;
        if (! $assigned || (isset($assigned->is_active) && ! $assigned->is_active)) {
            return false;
        }

        $targetNormalized = str_replace(['-', ' '], '_', mb_strtolower(trim($role)));
        if ($targetNormalized === '') {
            return false;
        }

        $targetSlug = Role::resolveKnownSlug($role);
        $assignedSlug = Role::resolveKnownSlug($assigned->slug)
            ?? Role::resolveKnownSlug($assigned->name);

        if ($targetSlug !== null) {
            return $assignedSlug === $targetSlug;
        }

        $assignedValues = array_filter([
            $assigned->slug !== null ? str_replace(['-', ' '], '_', mb_strtolower(trim((string) $assigned->slug))) : null,
            $assigned->name !== null ? str_replace(['-', ' '], '_', mb_strtolower(trim((string) $assigned->name))) : null,
        ]);

        return in_array($targetNormalized, $assignedValues, true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
