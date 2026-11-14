<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Medicine;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories()
    {
        return response()->json([
            'data' => Category::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function medicines(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price'],
            'sort' => ['nullable', 'in:name,price'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Medicine::with('category')->where('is_active', true);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
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

        return response()->json([
            'data' => $query->paginate($filters['per_page'] ?? 20),
        ]);
    }

    public function show(Medicine $medicine)
    {
        abort_unless($medicine->is_active, 404);

        return response()->json([
            'data' => $medicine->load('category'),
        ]);
    }
}