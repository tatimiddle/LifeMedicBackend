<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = is_array($this->resource) ? $this->resource : (array) $this->resource;

        return [
            'id' => $item['id'],
            'codigo' => $item['codigo'],
            'catalog_type' => $item['catalog_type'] ?? 'medicine',
            'name' => $item['name'],
            'source_name' => $item['source_name'] ?? null,
            'active_ingredient' => $item['active_ingredient'] ?? null,
            'formula' => $item['formula'] ?? [],
            'formula_summary' => $item['formula_summary'] ?? null,
            'category' => $item['category'],
            'category_slug' => $item['category_slug'],
            'san_nicolas_category' => $item['san_nicolas_category'] ?? null,
            'brand' => $item['brand'],
            'laboratory' => $item['laboratory'] ?? $item['brand'],
            'indications' => $item['indications'] ?? null,
            'contraindications' => $item['contraindications'] ?? null,
            'description' => $item['description'] ?? null,
            'batch' => $item['batch'] ?? null,
            'expiry' => $item['expiry'] ?? null,
            'expiry_status' => $item['expiry_status'] ?? 'Vigente',
            'prescription' => $item['prescription'] ?? 'Venta Libre',
            'prescription_required' => (bool) ($item['prescription_required'] ?? false),
            'price' => round((float) $item['price'], 2),
            'previous_price' => isset($item['previous_price']) ? round((float) $item['previous_price'], 2) : null,
            'stock' => (int) $item['stock'],
            'min_stock' => (int) ($item['min_stock'] ?? 20),
            'available' => (bool) $item['available'],
            'is_active' => (bool) $item['is_active'],
            'image_url' => $item['image_url'] ?? null,
            'source_url' => $item['source_url'] ?? null,
        ];
    }
}
