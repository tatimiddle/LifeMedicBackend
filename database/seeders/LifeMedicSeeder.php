<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class LifeMedicSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            ['description' => 'Personal administrador de la farmacia']
        );

        Role::updateOrCreate(
            ['name' => 'customer'],
            ['description' => 'Cliente de LifeMedic']
        );

        $categories = [
            ['name' => 'Medicamentos', 'description' => 'Medicamentos de uso general'],
            ['name' => 'Vitaminas', 'description' => 'Vitaminas y suplementos'],
            ['name' => 'Cuidado facial', 'description' => 'Productos para el cuidado facial'],
            ['name' => 'Cuidado personal', 'description' => 'Higiene y cuidado personal'],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['name' => $categoryData['name']],
                [...$categoryData, 'is_active' => true]
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@lifemedic.test'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Administrador LifeMedic',
                'phone' => '0000-0000',
                'password' => 'CambiarEstaClave123!',
                'is_active' => true,
            ]
        );

        $medicines = [
            [
                'codigo' => 'MED-001',
                'name' => 'Paracetamol 500 mg',
                'brand' => 'Genérico',
                'description' => 'Tabletas de paracetamol de 500 mg.',
                'price' => 0.10,
                'stock' => 100,
                'category' => 'Medicamentos',
            ],
            [
                'codigo' => 'MED-002',
                'name' => 'Ibuprofeno 400 mg',
                'brand' => 'Genérico',
                'description' => 'Tabletas de ibuprofeno de 400 mg.',
                'price' => 0.20,
                'stock' => 80,
                'category' => 'Medicamentos',
            ],
            [
                'codigo' => 'VIT-001',
                'name' => 'Vitamina C 500 mg',
                'brand' => 'Genérico',
                'description' => 'Tabletas de vitamina C de 500 mg.',
                'price' => 0.25,
                'stock' => 60,
                'category' => 'Vitaminas',
            ],
        ];

        foreach ($medicines as $medicine) {
            $category = Category::where('name', $medicine['category'])->firstOrFail();
            unset($medicine['category']);

            Medicine::updateOrCreate(
                ['codigo' => $medicine['codigo']],
                [...$medicine, 'category_id' => $category->id, 'is_active' => true]
            );
        }
    }
}