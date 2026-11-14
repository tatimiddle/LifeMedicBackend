<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Support\CatalogDataLoader;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (CatalogDataLoader::defaultCategories() as $categoryData) {
            $existing = Category::query()
                ->where('slug', $categoryData['slug'])
                ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($categoryData['name'])])
                ->first();

            if ($existing !== null) {
                $existing->update($categoryData);
            } else {
                Category::query()->create($categoryData);
            }
        }
    }
}
