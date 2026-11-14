<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Medicine;
use App\Support\CatalogDataLoader;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        if (Category::query()->count() === 0) {
            $this->call(CategorySeeder::class);
        }

        $categoriesBySlug = Category::query()->pluck('id', 'slug');
        $fallbackCategoryId = $categoriesBySlug->get('medicamentos') ?? Category::query()->value('id');

        foreach (CatalogDataLoader::loadCatalogEntries() as $entry) {
            $codigo = mb_strtoupper(trim((string) ($entry['codigo'] ?? '')));
            if ($codigo === '') {
                continue;
            }

            $normalized = CatalogDataLoader::normalizeMedicinePayload($codigo, $entry);
            $slug = $normalized['category_slug'] ?: 'medicamentos';
            $categoryId = $categoriesBySlug->get($slug) ?? $fallbackCategoryId;

            if ($categoryId === null) {
                continue;
            }

            Medicine::query()->updateOrCreate(
                ['codigo' => $codigo],
                [
                    'category_id' => $categoryId,
                    'catalog_type' => $normalized['catalog_type'],
                    'name' => $normalized['name'],
                    'source_name' => $normalized['source_name'],
                    'active_ingredient' => $normalized['active_ingredient'],
                    'formula' => $normalized['formula'],
                    'formula_summary' => $normalized['formula_summary'],
                    'san_nicolas_category' => $normalized['san_nicolas_category'],
                    'brand' => $normalized['brand'],
                    'laboratory' => $normalized['laboratory'],
                    'indications' => $normalized['indications'],
                    'contraindications' => $normalized['contraindications'],
                    'description' => $normalized['description'],
                    'batch' => $normalized['batch'],
                    'expiry' => $normalized['expiry'],
                    'expiry_status' => $normalized['expiry_status'],
                    'prescription' => $normalized['prescription'],
                    'prescription_required' => $normalized['prescription_required'],
                    'price' => $normalized['price'],
                    'previous_price' => $normalized['previous_price'],
                    'stock' => $normalized['stock'],
                    'min_stock' => $normalized['min_stock'],
                    'is_active' => $normalized['is_active'],
                    'image_path' => $normalized['image_path'],
                    'image_url' => $normalized['image_url'],
                    'source_url' => $normalized['source_url'],
                ]
            );
        }
    }
}
