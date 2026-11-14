<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Medicine;
use App\Support\CatalogDataLoader;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories()
    {
        return response()->json([
            'data' => Category::query()
                ->where('is_active', true)
                ->withCount(['medicines' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function medicines(Request $request)
    {
        return $this->queryCatalog($request);
    }

    public function products(Request $request)
    {
        $request->merge([
            'catalog_type' => 'product',
        ]);

        return $this->queryCatalog($request);
    }

    public function show(string $identifier)
    {
        $needle = trim($identifier);
        $query = Medicine::with('category');

        if ($needle === '') {
            abort(404);
        }

        if (ctype_digit($needle)) {
            $medicine = (clone $query)->whereKey((int) $needle)->first()
                ?? (clone $query)->whereRaw('LOWER(codigo) = ?', [mb_strtolower($needle)])->first();
        } else {
            $medicine = $query->whereRaw('LOWER(codigo) = ?', [mb_strtolower($needle)])->first();
        }

        abort_unless($medicine !== null && $medicine->is_active, 404);

        return response()->json([
            'data' => $this->formatMedicine($medicine),
        ]);
    }

    private function queryCatalog(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category' => ['nullable', 'string', 'max:120'],
            'catalog_type' => ['nullable', 'in:medicine,product,all,todos'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:name,price,created_at,id'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (isset($filters['min_price'], $filters['max_price']) && $filters['max_price'] < $filters['min_price']) {
            return response()->json([
                'message' => 'El precio máximo debe ser mayor o igual al precio mínimo.',
                'errors' => ['max_price' => ['El precio máximo debe ser mayor o igual al precio mínimo.']],
            ], 422);
        }

        $query = Medicine::with('category')->where('is_active', true);

        if (! empty($filters['catalog_type']) && ! in_array($filters['catalog_type'], ['all', 'todos'], true)) {
            $query->where('catalog_type', $filters['catalog_type']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('active_ingredient', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('laboratory', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        } elseif (! empty($filters['category'])) {
            $needle = CatalogDataLoader::normalize($filters['category']);
            $matchedIds = Category::query()->get()
                ->filter(fn (Category $cat): bool => CatalogDataLoader::normalize((string) ($cat->slug ?: $cat->name)) === $needle
                    || CatalogDataLoader::normalize($cat->name) === $needle)
                ->pluck('id')
                ->all();

            if ($matchedIds !== []) {
                $query->whereIn('category_id', $matchedIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $query->orderBy(
            $filters['sort'] ?? 'name',
            $filters['direction'] ?? 'asc'
        );

        $paginator = $query->paginate($filters['per_page'] ?? 20);
        $paginator->getCollection()->transform(fn (Medicine $medicine): array => $this->formatMedicine($medicine));

        return response()->json([
            'data' => $paginator,
        ]);
    }

    private function formatMedicine(Medicine $medicine): array
    {
        $data = $medicine->toArray();
        $data['image_url'] = CatalogDataLoader::formatImageUrl($medicine->image_url ?: $medicine->image_path);
        $data['available'] = (bool) $medicine->is_active && (int) $medicine->stock > 0;

        return $data;
    }
}
