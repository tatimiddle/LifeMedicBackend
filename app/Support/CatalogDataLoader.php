<?php

namespace App\Support;

use Illuminate\Support\Str;

class CatalogDataLoader
{
    /**
     * Categorías que por defecto pertenecen al catálogo de farmacia retail (Productos).
     *
     * @var array<int, string>
     */
    public const RETAIL_CATEGORY_SLUGS = [
        'dermocosmetica',
        'vitaminas',
        'cuidado-personal',
        'bebe',
        'fragancias',
    ];

    /**
     * @return array<int, array{name: string, slug: string, description: string, is_active: bool}>
     */
    public static function defaultCategories(): array
    {
        $descriptions = [
            'Medicamentos' => 'Fármacos de uso clínico con y sin prescripción médica.',
            'Dermocosmética' => 'Cuidado dermatológico especializado y fotoprotección.',
            'Vitaminas' => 'Suplementos nutricionales, minerales y refuerzo inmunitario.',
            'Cuidado Personal' => 'Higiene clínica diaria y cuidado corporal.',
            'Bebé' => 'Productos pediátricos e hidratación infantil.',
            'Fragancias' => 'Aguas micelares, lociones suaves y perfumería.',
        ];

        $categories = [];
        foreach (array_filter(PharmacyMockData::products()['categories'], fn (string $c): bool => $c !== 'Todos') as $name) {
            $categories[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $descriptions[$name] ?? 'Categoría farmacéutica LifeMedic.',
                'is_active' => true,
            ];
        }

        return $categories;
    }

    /**
     * Carga el catálogo scrapeado de Farmacias San Nicolás o usa el respaldo determinístico.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function loadCatalogEntries(): array
    {
        $path = resource_path('data/sannicolas_catalog.json');
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $entries = array_values(array_filter($decoded, 'is_array'));
                if ($entries !== []) {
                    return $entries;
                }
            }
        }

        return self::fallbackCatalogEntries();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fallbackCatalogEntries(): array
    {
        $byCode = [];

        foreach (PharmacyMockData::medicines()['items'] as $med) {
            $description = 'Medicamento farmacéutico '.$med['active'].' distribuido por '.$med['brand'].'.';
            if ($med['codigo'] === 'MED-00101') {
                $description = 'Alivio sintomático con Acetaminofén para el dolor leve a moderado y estados febriles.';
            }

            $byCode[$med['codigo']] = [
                'codigo' => $med['codigo'],
                'catalog_type' => 'medicine',
                'name' => $med['name'],
                'active_ingredient' => $med['active'],
                'category' => $med['category'],
                'brand' => $med['brand'],
                'laboratory' => $med['brand'],
                'batch' => $med['batch'],
                'expiry' => $med['expiry'],
                'expiry_status' => $med['expiry_status'],
                'prescription' => $med['prescription'],
                'price' => (float) $med['price'],
                'stock' => (int) $med['stock'],
                'min_stock' => 20,
                'is_active' => (bool) $med['is_active'],
                'description' => $description,
            ];
        }

        foreach (PharmacyMockData::products()['items'] as $prod) {
            $code = $prod['codigo'];
            if (isset($byCode[$code])) {
                continue;
            }

            $byCode[$code] = [
                'codigo' => $code,
                'catalog_type' => 'product',
                'name' => $prod['name'],
                'active_ingredient' => $prod['category'],
                'category' => $prod['category'],
                'brand' => $prod['brand'],
                'laboratory' => $prod['brand'],
                'batch' => null,
                'expiry' => null,
                'expiry_status' => 'Vigente',
                'prescription' => 'Venta Libre',
                'price' => (float) $prod['price'],
                'stock' => (int) $prod['stock'],
                'min_stock' => 20,
                'is_active' => (bool) $prod['is_active'],
                'description' => $prod['description'],
            ];
        }

        return array_values($byCode);
    }

    /**
     * Normaliza un payload crudo para persistirlo en `medicines`.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $current
     * @return array<string, mixed>
     */
    public static function normalizeMedicinePayload(string $codigo, array $data, ?array $current = null): array
    {
        $base = $current ?? [];
        $categoryName = trim((string) ($data['category'] ?? ($base['category'] ?? 'Medicamentos')));
        $brand = trim((string) ($data['brand'] ?? ($base['brand'] ?? 'Generico')));
        $stock = array_key_exists('stock', $data) ? max(0, (int) $data['stock']) : (int) ($base['stock'] ?? 0);
        $isActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : (bool) ($base['is_active'] ?? true);
        $prescription = trim((string) ($data['prescription'] ?? ($base['prescription'] ?? 'Venta Libre')));
        $catalogType = self::resolveCatalogType($data['catalog_type'] ?? ($base['catalog_type'] ?? null), $categoryName);

        $formula = array_key_exists('formula', $data)
            ? self::normalizeFormula($data['formula'])
            : ($base['formula'] ?? []);

        $formulaSummary = array_key_exists('formula_summary', $data)
            ? trim((string) $data['formula_summary'])
            : trim((string) ($base['formula_summary'] ?? self::formatFormulaSummary($formula)));

        $imagePath = array_key_exists('image_path', $data)
            ? ($data['image_path'] !== null && trim((string) $data['image_path']) !== '' ? trim((string) $data['image_path']) : null)
            : ($base['image_path'] ?? null);

        $imageUrl = match (true) {
            array_key_exists('image_path', $data) && $imagePath !== null => self::formatImageUrl($imagePath),
            array_key_exists('image_url', $data) => self::formatImageUrl($data['image_url'] !== null ? (string) $data['image_url'] : null),
            default => $base['image_url'] ?? self::formatImageUrl($imagePath),
        };

        $previousPriceRaw = array_key_exists('previous_price', $data)
            ? $data['previous_price']
            : ($base['previous_price'] ?? null);

        return [
            'codigo' => mb_strtoupper(trim($codigo)),
            'catalog_type' => $catalogType,
            'name' => trim((string) ($data['name'] ?? ($base['name'] ?? ''))),
            'source_name' => self::nullableString($data['source_name'] ?? ($base['source_name'] ?? null)),
            'active_ingredient' => trim((string) ($data['active_ingredient'] ?? ($base['active_ingredient'] ?? $categoryName))),
            'formula' => $formula,
            'formula_summary' => $formulaSummary,
            'category' => $categoryName,
            'category_slug' => Str::slug($categoryName),
            'san_nicolas_category' => self::nullableString(
                array_key_exists('san_nicolas_category', $data)
                    ? $data['san_nicolas_category']
                    : ($base['san_nicolas_category'] ?? null)
            ),
            'brand' => $brand,
            'laboratory' => trim((string) ($data['laboratory'] ?? ($base['laboratory'] ?? $brand))) ?: $brand,
            'batch' => self::nullableString(array_key_exists('batch', $data) ? $data['batch'] : ($base['batch'] ?? null)),
            'expiry' => self::nullableString(array_key_exists('expiry', $data) ? $data['expiry'] : ($base['expiry'] ?? null)),
            'expiry_status' => trim((string) ($data['expiry_status'] ?? ($base['expiry_status'] ?? 'Vigente'))),
            'prescription' => $prescription,
            'prescription_required' => mb_strtolower($prescription) === 'con receta',
            'price' => array_key_exists('price', $data) ? round((float) $data['price'], 2) : round((float) ($base['price'] ?? 0), 2),
            'previous_price' => is_numeric($previousPriceRaw) ? round((float) $previousPriceRaw, 2) : null,
            'stock' => $stock,
            'min_stock' => array_key_exists('min_stock', $data) && $data['min_stock'] !== null
                ? max(1, (int) $data['min_stock'])
                : max(1, (int) ($base['min_stock'] ?? 20)),
            'is_active' => $isActive,
            'indications' => trim((string) ($data['indications'] ?? ($base['indications'] ?? ''))),
            'contraindications' => trim((string) ($data['contraindications'] ?? ($base['contraindications'] ?? ''))),
            'description' => trim((string) ($data['description'] ?? ($base['description'] ?? ''))),
            'source_url' => self::nullableString(
                array_key_exists('source_url', $data)
                    ? $data['source_url']
                    : ($base['source_url'] ?? null)
            ),
            'image_path' => $imagePath,
            'image_url' => $imageUrl,
        ];
    }

    public static function resolveCatalogType(mixed $explicit, string $categoryName): string
    {
        $normalized = self::normalize((string) ($explicit ?? ''));
        if ($normalized === 'product' || $normalized === 'producto' || $normalized === 'productos') {
            return 'product';
        }
        if ($normalized === 'medicine' || $normalized === 'medicamento' || $normalized === 'medicamentos') {
            return 'medicine';
        }

        return in_array(Str::slug($categoryName), self::RETAIL_CATEGORY_SLUGS, true) ? 'product' : 'medicine';
    }

    /**
     * @return array<int, array{ingredient: string, amount: string}>
     */
    public static function normalizeFormula(mixed $formula): array
    {
        if (! is_array($formula)) {
            return [];
        }

        $clean = [];
        foreach ($formula as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $ingredient = trim((string) ($entry['ingredient'] ?? ''));
            if ($ingredient === '') {
                continue;
            }
            $clean[] = [
                'ingredient' => $ingredient,
                'amount' => trim((string) ($entry['amount'] ?? '')),
            ];
        }

        return $clean;
    }

    /**
     * @param  array<int, array{ingredient: string, amount: string}>  $formula
     */
    public static function formatFormulaSummary(array $formula): string
    {
        if ($formula === []) {
            return '';
        }

        return implode(', ', array_map(
            fn (array $row): string => $row['amount'] !== '' ? "{$row['ingredient']} ({$row['amount']})" : $row['ingredient'],
            $formula
        ));
    }

    public static function formatImageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $trimmed = trim($path);
        if (str_starts_with($trimmed, 'http') || str_starts_with($trimmed, '/')) {
            return $trimmed;
        }

        return '/storage/'.ltrim($trimmed, '/');
    }

    public static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function normalize(string $value): string
    {
        return mb_strtolower(Str::ascii(trim($value)));
    }
}
