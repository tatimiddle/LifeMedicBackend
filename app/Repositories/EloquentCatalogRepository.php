<?php

namespace App\Repositories;

use App\Contracts\CatalogRepositoryInterface;
use App\Models\Category;
use App\Models\Medicine;
use App\Support\CatalogDataLoader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EloquentCatalogRepository implements CatalogRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function categories(): array
    {
        $this->ensureSeeded();

        $categories = Category::query()
            ->where('is_active', true)
            ->withCount('medicines')
            ->orderBy('id')
            ->get();

        return $categories->map(fn (Category $category): array => [
            'id' => (int) $category->id,
            'slug' => (string) ($category->slug ?: Str::slug($category->name)),
            'name' => (string) $category->name,
            'description' => (string) ($category->description ?? ''),
            'is_active' => true,
            'items_count' => (int) $category->medicines_count,
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function allCategories(array $filters = []): array
    {
        $this->ensureSeeded();

        $rows = Category::query()
            ->withCount('medicines')
            ->orderBy('id')
            ->get()
            ->map(function (Category $category): array {
                $isActive = (bool) $category->is_active;

                return [
                    'id' => (int) $category->id,
                    'slug' => (string) ($category->slug ?: Str::slug($category->name)),
                    'name' => (string) $category->name,
                    'description' => (string) ($category->description ?? ''),
                    'is_active' => $isActive,
                    'items_count' => (int) $category->medicines_count,
                    'status_label' => $isActive ? 'Activa' : 'Inactiva',
                ];
            })
            ->all();

        $status = CatalogDataLoader::normalize((string) ($filters['estado'] ?? $filters['status'] ?? ''));
        if (in_array($status, ['activas', 'activa', 'activos', 'activo'], true)) {
            $rows = array_filter($rows, fn (array $c): bool => (bool) $c['is_active']);
        } elseif (in_array($status, ['inactivas', 'inactiva', 'inactivos', 'inactivo'], true)) {
            $rows = array_filter($rows, fn (array $c): bool => ! (bool) $c['is_active']);
        }

        $search = CatalogDataLoader::normalize((string) ($filters['buscar'] ?? $filters['search'] ?? $filters['q'] ?? ''));
        if ($search !== '') {
            $rows = array_filter($rows, function (array $c) use ($search): bool {
                return str_contains(CatalogDataLoader::normalize($c['name'].' '.($c['description'] ?? '')), $search);
            });
        }

        return array_values($rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findCategory(string $slug): ?array
    {
        $needle = CatalogDataLoader::normalize($slug);
        if ($needle === '') {
            return null;
        }

        foreach ($this->allCategories() as $category) {
            if (
                CatalogDataLoader::normalize($category['slug']) === $needle
                || CatalogDataLoader::normalize($category['name']) === $needle
            ) {
                return $category;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createCategory(array $data): array
    {
        $this->ensureSeeded();

        $name = trim((string) ($data['name'] ?? ''));
        $slug = Str::slug($name);

        $category = Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ]);

        return $this->findCategory((string) $category->slug) ?? [
            'id' => (int) $category->id,
            'slug' => (string) $category->slug,
            'name' => (string) $category->name,
            'description' => (string) ($category->description ?? ''),
            'is_active' => (bool) $category->is_active,
            'items_count' => 0,
            'status_label' => $category->is_active ? 'Activa' : 'Inactiva',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function updateCategory(string $slug, array $data): ?array
    {
        $this->ensureSeeded();

        $category = $this->findCategoryModel($slug);
        if ($category === null) {
            return null;
        }

        $newName = trim((string) ($data['name'] ?? $category->name));
        $newSlug = Str::slug($newName);

        $category->update([
            'name' => $newName,
            'slug' => $newSlug,
            'description' => array_key_exists('description', $data)
                ? trim((string) $data['description'])
                : ($category->description ?? ''),
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : (bool) $category->is_active,
        ]);

        return $this->findCategory($newSlug);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toggleCategoryStatus(string $slug): ?array
    {
        $this->ensureSeeded();

        $category = $this->findCategoryModel($slug);
        if ($category === null) {
            return null;
        }

        $category->update([
            'is_active' => ! (bool) $category->is_active,
        ]);

        return $this->findCategory((string) ($category->slug ?: $category->name));
    }

    /**
     * @return array{deleted: bool, message: string}
     */
    public function deleteCategory(string $slug): array
    {
        $this->ensureSeeded();

        $category = $this->findCategoryModel($slug);
        if ($category === null) {
            return ['deleted' => false, 'message' => 'Categoría no encontrada.'];
        }

        $assigned = $category->medicines()->count();
        if ($assigned > 0) {
            return [
                'deleted' => false,
                'message' => "No se puede eliminar la categoría \"{$category->name}\" porque tiene {$assigned} medicamento(s) asignado(s).",
            ];
        }

        $name = $category->name;
        $category->delete();

        return [
            'deleted' => true,
            'message' => "Categoría \"{$name}\" eliminada correctamente.",
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array
    {
        $items = $this->catalog();

        $rawType = CatalogDataLoader::normalize((string) ($filters['catalog_type'] ?? $filters['type'] ?? ''));
        if ($rawType !== '' && $rawType !== 'todos' && $rawType !== 'all') {
            $items = array_filter($items, function (array $item) use ($rawType): bool {
                $itemType = CatalogDataLoader::normalize((string) ($item['catalog_type'] ?? 'medicine'));

                return match ($rawType) {
                    'medicine', 'medicamento', 'medicamentos' => $itemType === 'medicine',
                    'product', 'producto', 'productos' => $itemType === 'product',
                    default => $itemType === $rawType,
                };
            });
        }

        $rawCategory = trim((string) ($filters['category'] ?? $filters['categoria'] ?? ''));
        if ($rawCategory !== '' && CatalogDataLoader::normalize($rawCategory) !== 'todos') {
            $needle = CatalogDataLoader::normalize($rawCategory);
            $items = array_filter($items, function (array $item) use ($needle): bool {
                return CatalogDataLoader::normalize($item['category']) === $needle
                    || CatalogDataLoader::normalize($item['category_slug']) === $needle
                    || CatalogDataLoader::normalize((string) ($item['san_nicolas_category'] ?? '')) === $needle;
            });
        }

        $rawSearch = trim((string) ($filters['search'] ?? $filters['nombre'] ?? $filters['q'] ?? $filters['buscar'] ?? ''));
        if ($rawSearch !== '') {
            $needle = CatalogDataLoader::normalize($rawSearch);
            $items = array_filter($items, function (array $item) use ($needle): bool {
                $haystack = implode(' ', [
                    $item['name'],
                    $item['source_name'] ?? '',
                    $item['active_ingredient'] ?? '',
                    $item['formula_summary'] ?? '',
                    $item['codigo'],
                    $item['brand'],
                    $item['laboratory'] ?? '',
                    $item['batch'] ?? '',
                    $item['category'],
                    $item['san_nicolas_category'] ?? '',
                    $item['indications'] ?? '',
                    $item['description'] ?? '',
                ]);

                return str_contains(CatalogDataLoader::normalize($haystack), $needle);
            });
        }

        $rawPrescription = trim((string) ($filters['receta'] ?? $filters['prescription'] ?? ''));
        if ($rawPrescription !== '' && CatalogDataLoader::normalize($rawPrescription) !== 'todos') {
            $needle = CatalogDataLoader::normalize($rawPrescription);
            $items = array_filter($items, fn (array $item): bool => CatalogDataLoader::normalize($item['prescription'] ?? '') === $needle);
        }

        $rawStatus = CatalogDataLoader::normalize((string) ($filters['estado'] ?? $filters['status'] ?? ''));
        if ($rawStatus !== '' && $rawStatus !== 'todos') {
            $items = array_filter($items, function (array $item) use ($rawStatus): bool {
                $minStock = (int) ($item['min_stock'] ?? 20);

                return match ($rawStatus) {
                    'activos', 'activo' => (bool) $item['is_active'],
                    'disponible', 'disponibles' => (bool) $item['is_active'] && (int) $item['stock'] > $minStock,
                    'dados de baja', 'inactivos', 'inactivo' => ! (bool) $item['is_active'],
                    'stock bajo', 'bajo stock' => (bool) $item['is_active'] && (int) $item['stock'] > 0 && (int) $item['stock'] <= $minStock,
                    'sin stock', 'agotado' => (int) $item['stock'] === 0,
                    'por vencer' => CatalogDataLoader::normalize((string) ($item['expiry_status'] ?? '')) === 'por vencer',
                    default => true,
                };
            });
        }

        if (array_key_exists('only_active', $filters) || array_key_exists('disponible', $filters)) {
            $flag = filter_var(
                $filters['only_active'] ?? $filters['disponible'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($flag !== null) {
                $items = array_filter($items, fn (array $item): bool => $item['available'] === $flag);
            }
        }

        $items = array_values($items);

        $sort = trim((string) ($filters['sort'] ?? ''));
        if ($sort === 'price_asc') {
            usort($items, fn (array $a, array $b): int => $a['price'] <=> $b['price']);
        } elseif ($sort === 'price_desc') {
            usort($items, fn (array $a, array $b): int => $b['price'] <=> $a['price']);
        } elseif ($sort === 'name_asc') {
            usort($items, fn (array $a, array $b): int => strcmp(CatalogDataLoader::normalize($a['name']), CatalogDataLoader::normalize($b['name'])));
        } elseif ($sort === 'name_desc') {
            usort($items, fn (array $a, array $b): int => strcmp(CatalogDataLoader::normalize($b['name']), CatalogDataLoader::normalize($a['name'])));
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $identifier): ?array
    {
        $this->ensureSeeded();

        $needle = trim((string) $identifier);
        if ($needle === '') {
            return null;
        }

        $query = Medicine::query()->with('category');

        if (ctype_digit($needle)) {
            $medicine = (clone $query)->whereKey((int) $needle)->first()
                ?? (clone $query)->whereRaw('LOWER(codigo) = ?', [mb_strtolower($needle)])->first();
        } else {
            $medicine = $query->whereRaw('LOWER(codigo) = ?', [mb_strtolower($needle)])->first();
        }

        return $medicine ? $this->mapMedicine($medicine) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByCode(string $codigo): ?array
    {
        $this->ensureSeeded();

        $medicine = $this->findMedicineModelByCode($codigo);
        if ($medicine === null) {
            return null;
        }

        $medicine->load('category');

        return $this->mapMedicine($medicine);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createMedicine(array $data): array
    {
        $this->ensureSeeded();

        $codigo = mb_strtoupper(trim((string) $data['codigo']));
        $normalized = CatalogDataLoader::normalizeMedicinePayload($codigo, $data);
        $category = $this->resolveOrCreateCategory($normalized['category']);

        $medicine = Medicine::query()->create($this->persistableAttributes($category->id, $normalized));
        $medicine->load('category');

        return $this->mapMedicine($medicine);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function updateMedicine(string $codigo, array $data): ?array
    {
        $this->ensureSeeded();

        $medicine = $this->findMedicineModelByCode($codigo);
        if ($medicine === null) {
            return null;
        }

        $medicine->load('category');
        $current = $this->mapMedicine($medicine);

        $oldImagePath = (
            array_key_exists('image_path', $data)
            && ! empty($current['image_path'])
            && $data['image_path'] !== $current['image_path']
        ) ? $current['image_path'] : null;

        $normalized = CatalogDataLoader::normalizeMedicinePayload($current['codigo'], $data, $current);
        $category = $this->resolveOrCreateCategory($normalized['category']);

        $medicine->update($this->persistableAttributes($category->id, $normalized));
        $medicine->load('category');

        if ($oldImagePath && ! str_starts_with($oldImagePath, '/') && ! str_starts_with($oldImagePath, 'images/')) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return $this->mapMedicine($medicine);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function updateInventory(string $codigo, float $price, int $stock, ?int $minStock = null): ?array
    {
        return $this->updateMedicine($codigo, array_filter([
            'price' => $price,
            'stock' => $stock,
            'min_stock' => $minStock,
        ], fn (mixed $v): bool => $v !== null));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toggleMedicineStatus(string $codigo): ?array
    {
        $item = $this->findByCode($codigo);
        if ($item === null) {
            return null;
        }

        return $this->updateMedicine($item['codigo'], [
            'is_active' => ! (bool) $item['is_active'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function deleteMedicine(string $codigo): ?array
    {
        $this->ensureSeeded();

        $medicine = $this->findMedicineModelByCode($codigo);
        if ($medicine === null) {
            return null;
        }

        if ($medicine->orderDetails()->exists()) {
            throw new \DomainException("No se puede eliminar {$medicine->name} ({$medicine->codigo}) porque está asociado a pedidos históricos.");
        }

        $medicine->load('category');
        $mapped = $this->mapMedicine($medicine);

        $medicine->delete();

        if (! empty($mapped['image_path']) && ! str_starts_with($mapped['image_path'], '/') && ! str_starts_with($mapped['image_path'], 'images/')) {
            Storage::disk('public')->delete($mapped['image_path']);
        }

        return $mapped;
    }

    /**
     * @return array<string, int>
     */
    public function inventorySummary(): array
    {
        $this->ensureSeeded();

        $items = $this->catalog();
        $categories = Category::query()->get();

        $lowStock = fn (array $item): bool => (bool) ($item['is_active'] ?? true)
            && (int) $item['stock'] <= (int) ($item['min_stock'] ?? 20);

        $clinical = array_filter($items, fn (array $item): bool => ($item['catalog_type'] ?? 'medicine') === 'medicine');
        $retail = array_filter($items, fn (array $item): bool => ($item['catalog_type'] ?? 'medicine') === 'product');

        return [
            'total_medicines' => count($items),
            'active_medicines' => count(array_filter($items, fn (array $item): bool => (bool) ($item['is_active'] ?? true))),
            'inactive_medicines' => count(array_filter($items, fn (array $item): bool => ! (bool) ($item['is_active'] ?? true))),
            'low_stock_alerts' => count(array_filter($items, $lowStock)),
            'out_of_stock' => count(array_filter($items, fn (array $item): bool => (int) $item['stock'] === 0)),
            'expiring_soon' => count(array_filter($items, fn (array $item): bool => CatalogDataLoader::normalize((string) ($item['expiry_status'] ?? '')) === 'por vencer')),
            'clinical_medicines' => count($clinical),
            'active_clinical_medicines' => count(array_filter($clinical, fn (array $item): bool => (bool) ($item['is_active'] ?? true))),
            'clinical_low_stock_alerts' => count(array_filter($clinical, $lowStock)),
            'clinical_expiring_soon' => count(array_filter($clinical, fn (array $item): bool => CatalogDataLoader::normalize((string) ($item['expiry_status'] ?? '')) === 'por vencer')),
            'retail_products' => count($retail),
            'active_retail_products' => count(array_filter($retail, fn (array $item): bool => (bool) ($item['is_active'] ?? true))),
            'retail_low_stock_alerts' => count(array_filter($retail, $lowStock)),
            'retail_out_of_stock' => count(array_filter($retail, fn (array $item): bool => (int) $item['stock'] === 0)),
            'categories_count' => $categories->count(),
            'active_categories_count' => $categories->where('is_active', true)->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        $this->ensureSeeded();

        return Medicine::query()
            ->with('category')
            ->orderBy('id')
            ->get()
            ->map(fn (Medicine $medicine): array => $this->mapMedicine($medicine))
            ->all();
    }

    private function ensureSeeded(): void
    {
        if (Category::query()->exists() || Medicine::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            if (Category::query()->exists() || Medicine::query()->exists()) {
                return;
            }

            $categoryIds = [];
            foreach (CatalogDataLoader::defaultCategories() as $categoryData) {
                $category = Category::query()->updateOrCreate(
                    ['slug' => $categoryData['slug']],
                    [
                        'name' => $categoryData['name'],
                        'description' => $categoryData['description'],
                        'is_active' => $categoryData['is_active'],
                    ]
                );
                $categoryIds[$categoryData['slug']] = $category->id;
            }

            foreach (CatalogDataLoader::loadCatalogEntries() as $entry) {
                $codigo = mb_strtoupper(trim((string) ($entry['codigo'] ?? '')));
                if ($codigo === '') {
                    continue;
                }

                $normalized = CatalogDataLoader::normalizeMedicinePayload($codigo, $entry);
                $slug = $normalized['category_slug'] ?: 'medicamentos';

                if (! isset($categoryIds[$slug])) {
                    $category = Category::query()->firstOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $normalized['category'],
                            'description' => 'Categoría farmacéutica registrada en LifeMedic.',
                            'is_active' => true,
                        ]
                    );
                    $categoryIds[$slug] = $category->id;
                }

                Medicine::query()->updateOrCreate(
                    ['codigo' => $codigo],
                    $this->persistableAttributes($categoryIds[$slug], $normalized)
                );
            }
        });
    }

    private function findCategoryModel(string $slug): ?Category
    {
        $needle = CatalogDataLoader::normalize($slug);
        if ($needle === '') {
            return null;
        }

        return Category::query()->get()->first(function (Category $category) use ($needle): bool {
            $catSlug = (string) ($category->slug ?: Str::slug($category->name));

            return CatalogDataLoader::normalize($catSlug) === $needle
                || CatalogDataLoader::normalize($category->name) === $needle;
        });
    }

    private function findMedicineModelByCode(string $codigo): ?Medicine
    {
        $needle = trim($codigo);
        if ($needle === '') {
            return null;
        }

        return Medicine::query()
            ->whereRaw('LOWER(codigo) = ?', [mb_strtolower($needle)])
            ->first();
    }

    private function resolveOrCreateCategory(string $name): Category
    {
        $trimmed = trim($name) ?: 'Medicamentos';
        $slug = Str::slug($trimmed);

        $existing = $this->findCategoryModel($slug ?: $trimmed);
        if ($existing !== null) {
            return $existing;
        }

        return Category::query()->create([
            'name' => $trimmed,
            'slug' => $slug,
            'description' => 'Categoría farmacéutica registrada en LifeMedic.',
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    private function persistableAttributes(int $categoryId, array $normalized): array
    {
        return [
            'category_id' => $categoryId,
            'codigo' => $normalized['codigo'],
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMedicine(Medicine $medicine): array
    {
        $category = $medicine->category;
        $categoryName = (string) ($category?->name ?? 'Medicamentos');
        $categorySlug = (string) ($category?->slug ?: Str::slug($categoryName));
        $formula = is_array($medicine->formula) ? $medicine->formula : [];
        $formulaSummary = trim((string) ($medicine->formula_summary ?? CatalogDataLoader::formatFormulaSummary($formula)));
        $prescription = trim((string) ($medicine->prescription ?: ($medicine->prescription_required ? 'Con Receta' : 'Venta Libre')));
        $imagePath = $medicine->image_path ? trim((string) $medicine->image_path) : null;
        $imageUrl = CatalogDataLoader::formatImageUrl($medicine->image_url ?: $imagePath);
        $isActive = (bool) $medicine->is_active;
        $stock = max(0, (int) $medicine->stock);

        return [
            'id' => (int) $medicine->id,
            'category_id' => (int) $medicine->category_id,
            'codigo' => (string) $medicine->codigo,
            'catalog_type' => (string) ($medicine->catalog_type ?: 'medicine'),
            'name' => (string) $medicine->name,
            'source_name' => $medicine->source_name,
            'active_ingredient' => (string) ($medicine->active_ingredient ?: $categoryName),
            'formula' => $formula,
            'formula_summary' => $formulaSummary,
            'category' => $categoryName,
            'category_slug' => $categorySlug,
            'san_nicolas_category' => $medicine->san_nicolas_category,
            'brand' => (string) ($medicine->brand ?: 'Genérico'),
            'laboratory' => (string) ($medicine->laboratory ?: ($medicine->brand ?: 'Genérico')),
            'batch' => $medicine->batch,
            'expiry' => $medicine->expiry,
            'expiry_status' => (string) ($medicine->expiry_status ?: 'Vigente'),
            'prescription' => $prescription,
            'prescription_required' => (bool) ($medicine->prescription_required || mb_strtolower($prescription) === 'con receta'),
            'price' => round((float) $medicine->price, 2),
            'previous_price' => $medicine->previous_price !== null ? round((float) $medicine->previous_price, 2) : null,
            'stock' => $stock,
            'min_stock' => max(1, (int) ($medicine->min_stock ?: 20)),
            'is_active' => $isActive,
            'available' => $isActive && $stock > 0,
            'indications' => (string) ($medicine->indications ?? ''),
            'contraindications' => (string) ($medicine->contraindications ?? ''),
            'description' => (string) ($medicine->description ?? ''),
            'source_url' => $medicine->source_url,
            'image_path' => $imagePath,
            'image_url' => $imageUrl,
        ];
    }
}
