<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('slug', Role::ADMIN)
            ->orWhere('name', Role::API_ADMIN)
            ->first();

        if ($adminRole === null) {
            $adminRole = Role::query()->create([
                'slug' => Role::ADMIN,
                'name' => Role::API_ADMIN,
                'description' => 'Administrador — Control total de usuarios, catálogo, precios y seguimiento de órdenes.',
                'is_active' => true,
            ]);
        }

        $email = 'admin@lifemedic.com';
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->forceFill([
                'role_id' => $adminRole->id,
                'name' => $existing->name ?: 'Administrador LifeMedic',
                'phone' => $existing->phone ?: '2222-0000',
                'address' => $existing->address ?: 'Casa Matriz LifeMedic, San Salvador',
                'email_verified_at' => $existing->email_verified_at ?? now(),
                'is_active' => true,
            ])->save();

            return;
        }

        User::query()->create([
            'role_id' => $adminRole->id,
            'name' => 'Administrador LifeMedic',
            'email' => $email,
            'phone' => '2222-0000',
            'address' => 'Casa Matriz LifeMedic, San Salvador',
            'email_verified_at' => now(),
            'password' => Hash::make((string) env('LIFEMEDIC_ADMIN_PASSWORD', 'LifeMedic2026!')),
            'is_active' => true,
        ]);
    }
}
