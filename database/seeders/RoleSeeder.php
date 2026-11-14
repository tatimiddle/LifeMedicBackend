<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'slug' => Role::ADMIN,
                'name' => Role::API_ADMIN,
                'description' => 'Administrador — Control total de usuarios, catálogo, precios y seguimiento de órdenes.',
                'is_active' => true,
            ],
            [
                'slug' => Role::PHARMACIST,
                'name' => Role::API_PHARMACIST,
                'description' => 'Farmacéutico — Gestión de medicamentos, despacho de pedidos y consultas clínicas.',
                'is_active' => true,
            ],
            [
                'slug' => Role::CLIENT,
                'name' => Role::API_CLIENT,
                'description' => 'Cliente — Cuenta de paciente/comprador registrada en la aplicación móvil.',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $data) {
            $existing = Role::query()
                ->where('slug', $data['slug'])
                ->orWhere('name', $data['name'])
                ->first();

            if ($existing !== null) {
                $existing->update($data);
            } else {
                Role::query()->create($data);
            }
        }
    }
}
