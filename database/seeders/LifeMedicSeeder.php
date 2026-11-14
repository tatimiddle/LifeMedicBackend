<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LifeMedicSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            CategorySeeder::class,
            MedicineSeeder::class,
        ]);
    }
}
