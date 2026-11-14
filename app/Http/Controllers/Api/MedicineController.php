<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Medicine;
use App\Support\CatalogDataLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'catalog_type' => ['nullable', 'in:medicine,product,all,todos'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', Rule::in(['name', 'price', 'created_at'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Medicine::with('category')->where('is_active', true);

        if (! empty($validated['catalog_type']) && ! in_array($validated['catalog_type'], ['all', 'todos'], true)) {
            $query->where('catalog_type', $validated['catalog_type']);
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('active_ingredient', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }

        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        $medicines = $query
            ->orderBy($validated['sort_by'] ?? 'name', $validated['sort_direction'] ?? 'asc')
            ->paginate($validated['per_page'] ?? 15);

        $medicines->getCollection()->transform(
            fn (Medicine $medicine) => $this->formatMedicine($medicine)
        );

        return response()->json($medicines);
    }

    public function show(Medicine $medicine)
    {
        abort_unless($medicine->is_active, 404);

        return response()->json(
            $this->formatMedicine($medicine->load('category'))
        );
    }

    public function store(Request $request)
    {
        if (is_string($request->input('codigo'))) {
            $request->merge([
                'codigo' => mb_strtoupper(trim((string) $request->input('codigo'))),
            ]);
        }

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'codigo' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $exists = Medicine::query()
                        ->whereRaw('LOWER(codigo) = ?', [mb_strtolower(trim((string) $value))])
                        ->exists();
                    if ($exists) {
                        $fail('El código SKU ya se encuentra registrado.');
                    }
                },
            ],
            'catalog_type' => ['nullable', 'in:medicine,product'],
            'name' => ['required', 'string', 'max:255'],
            'active_ingredient' => ['nullable', 'string', 'max:200'],
            'brand' => ['nullable', 'string', 'max:255'],
            'laboratory' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'indications' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'batch' => ['nullable', 'string', 'max:80'],
            'expiry' => ['nullable', 'string', 'max:60'],
            'expiry_status' => ['nullable', 'string', 'max:60'],
            'prescription' => ['nullable', 'string', 'max:60'],
            'formula' => ['nullable', 'array'],
            'price' => ['required', 'numeric', 'min:0'],
            'previous_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        unset($validated['image']);
        $validated['codigo'] = mb_strtoupper(trim((string) $validated['codigo']));

        if (empty($validated['catalog_type'])) {
            $category = Category::query()->find($validated['category_id']);
            $validated['catalog_type'] = CatalogDataLoader::resolveCatalogType(null, $category?->name ?? 'Medicamentos');
        }

        $validated['expiry_status'] = filled($validated['expiry_status'] ?? null)
            ? trim((string) $validated['expiry_status'])
            : 'Vigente';
        $validated['prescription'] = filled($validated['prescription'] ?? null)
            ? trim((string) $validated['prescription'])
            : 'Venta Libre';
        $validated['prescription_required'] = mb_strtolower($validated['prescription']) === 'con receta';
        $validated['min_stock'] = isset($validated['min_stock']) ? (int) $validated['min_stock'] : 20;
        $validated['is_active'] = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true;

        if (array_key_exists('formula', $validated)) {
            $formula = CatalogDataLoader::normalizeFormula($validated['formula']);
            $validated['formula'] = $formula;
            $validated['formula_summary'] = CatalogDataLoader::formatFormulaSummary($formula);
        }

        if ($request->hasFile('image')) {
            $storedPath = $request->file('image')->store('medicines', 'public');
            $validated['image_path'] = $storedPath;
            $validated['image_url'] = '/storage/'.ltrim($storedPath, '/');
        }

        $medicine = Medicine::create($validated)->load('category');

        return response()->json($this->formatMedicine($medicine), 201);
    }

    public function update(Request $request, Medicine $medicine)
    {
        if ($request->has('codigo') && is_string($request->input('codigo'))) {
            $request->merge([
                'codigo' => mb_strtoupper(trim((string) $request->input('codigo'))),
            ]);
        }

        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($medicine): void {
                    $exists = Medicine::query()
                        ->where('id', '!=', $medicine->id)
                        ->whereRaw('LOWER(codigo) = ?', [mb_strtolower(trim((string) $value))])
                        ->exists();
                    if ($exists) {
                        $fail('El código SKU ya se encuentra registrado.');
                    }
                },
            ],
            'catalog_type' => ['sometimes', 'nullable', 'in:medicine,product'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'active_ingredient' => ['nullable', 'string', 'max:200'],
            'brand' => ['nullable', 'string', 'max:255'],
            'laboratory' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'indications' => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'batch' => ['nullable', 'string', 'max:80'],
            'expiry' => ['nullable', 'string', 'max:60'],
            'expiry_status' => ['nullable', 'string', 'max:60'],
            'prescription' => ['nullable', 'string', 'max:60'],
            'formula' => ['nullable', 'array'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'previous_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        unset($validated['image']);
        if (isset($validated['codigo'])) {
            $validated['codigo'] = mb_strtoupper(trim((string) $validated['codigo']));
        }
        if (array_key_exists('catalog_type', $validated) && empty($validated['catalog_type'])) {
            unset($validated['catalog_type']);
        }
        if (array_key_exists('expiry_status', $validated)) {
            if (! filled($validated['expiry_status'])) {
                unset($validated['expiry_status']);
            } else {
                $validated['expiry_status'] = trim((string) $validated['expiry_status']);
            }
        }
        if (array_key_exists('prescription', $validated)) {
            if (! filled($validated['prescription'])) {
                unset($validated['prescription']);
            } else {
                $validated['prescription'] = trim((string) $validated['prescription']);
                $validated['prescription_required'] = mb_strtolower($validated['prescription']) === 'con receta';
            }
        }
        if (array_key_exists('min_stock', $validated) && $validated['min_stock'] === null) {
            unset($validated['min_stock']);
        }
        if (array_key_exists('formula', $validated)) {
            $formula = CatalogDataLoader::normalizeFormula($validated['formula']);
            $validated['formula'] = $formula;
            $validated['formula_summary'] = CatalogDataLoader::formatFormulaSummary($formula);
        }

        $oldImagePath = $medicine->image_path ?: (
            $medicine->image_url && ! str_starts_with($medicine->image_url, '/') && ! str_starts_with($medicine->image_url, 'http')
                ? $medicine->image_url
                : null
        );

        if ($request->hasFile('image')) {
            $storedPath = $request->file('image')->store('medicines', 'public');
            $validated['image_path'] = $storedPath;
            $validated['image_url'] = '/storage/'.ltrim($storedPath, '/');
        }

        $medicine->update($validated);

        if ($request->hasFile('image') && $oldImagePath && ! str_starts_with($oldImagePath, '/') && ! str_starts_with($oldImagePath, 'images/')) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return response()->json(
            $this->formatMedicine($medicine->fresh()->load('category'))
        );
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->update(['is_active' => false]);

        return response()->json([
            'message' => 'Medicamento desactivado correctamente.',
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
