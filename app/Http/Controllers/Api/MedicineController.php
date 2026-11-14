<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MedicineController extends Controller
{
    // Lista y filtra medicamentos activos.
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', Rule::in(['name', 'price', 'created_at'])],
            'sort_direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Medicine::with('category')->where('is_active', true);

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
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

    // Muestra un medicamento por su ID.
    public function show(Medicine $medicine)
    {
        abort_unless($medicine->is_active, 404);

        return response()->json(
            $this->formatMedicine($medicine->load('category'))
        );
    }

    // Crea un medicamento y guarda su imagen, si se envió.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'codigo' => ['required', 'string', 'max:255', 'unique:medicines,codigo'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')
                ->store('medicines', 'public');
        }

        $medicine = Medicine::create($validated)->load('category');

        return response()->json($this->formatMedicine($medicine), 201);
    }

    // Actualiza un medicamento y, si llega una imagen nueva, reemplaza la anterior.
    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('medicines', 'codigo')->ignore($medicine->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        unset($validated['image']);

        $oldImagePath = $medicine->image_url;

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')
                ->store('medicines', 'public');
        }

        $medicine->update($validated);

        if ($request->hasFile('image') && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return response()->json(
            $this->formatMedicine($medicine->fresh()->load('category'))
        );
    }

    // Lo desactiva para que no aparezca en el catálogo, conservando el historial.
    public function destroy(Medicine $medicine)
    {
        $medicine->update(['is_active' => false]);

        return response()->json([
            'message' => 'Medicamento desactivado correctamente.',
        ]);
    }

    // Convierte la ruta guardada en la BD en una URL para mostrar la imagen.
    private function formatMedicine(Medicine $medicine): array
    {
        $data = $medicine->toArray();

        $data['image_url'] = $medicine->image_url
            ? Storage::disk('public')->url($medicine->image_url)
            : null;

        return $data;
    }
}