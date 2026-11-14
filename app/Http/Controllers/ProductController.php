<?php

namespace App\Http\Controllers;

use App\Contracts\CatalogRepositoryInterface;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogRepositoryInterface $catalog) {}

    public function index(Request $request): View
    {
        $filters = [
            'estado' => (string) $request->query('estado', 'Todos'),
            'categoria' => (string) $request->query('categoria', 'Todos'),
            'buscar' => (string) $request->query('buscar', ''),
        ];

        $categories = array_values(array_filter(
            $this->catalog->allCategories(),
            fn (array $cat): bool => $cat['slug'] !== 'medicamentos'
        ));

        $summary = $this->catalog->inventorySummary();
        $items = $this->catalog->all(array_merge($filters, ['catalog_type' => 'product']));

        return view('modules.productos', [
            'products' => [
                'kpis' => [
                    ['label' => 'Productos No Medicinales', 'value' => (string) $summary['retail_products'], 'trend' => null, 'positive' => true, 'icon' => 'tag'],
                    ['label' => 'Activos en Catálogo', 'value' => (string) $summary['active_retail_products'], 'trend' => null, 'positive' => true, 'icon' => 'store'],
                    ['label' => 'Stock Bajo / Agotado', 'value' => (string) $summary['retail_low_stock_alerts'], 'trend' => null, 'positive' => $summary['retail_low_stock_alerts'] === 0, 'icon' => 'stock-alert'],
                ],
                'items' => $items,
            ],
            'categories' => $categories,
            'summary' => $summary,
            'filters' => $filters,
            'nextSuggestedCode' => $this->nextProductCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request, isCreate: true);

        $codigo = mb_strtoupper(trim($validated['codigo']));
        if ($this->catalog->findByCode($codigo) !== null) {
            return back()
                ->withInput()
                ->withErrors(['codigo' => "El código {$codigo} ya está registrado en el catálogo."]);
        }

        $payload = $this->buildPersistencePayload($validated, [
            'codigo' => $codigo,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        if ($request->hasFile('image')) {
            $payload['image_path'] = Storage::disk('public')->putFile('products', $request->file('image'));
        }

        $created = $this->catalog->createMedicine($payload);

        return redirect()
            ->route('productos.index')
            ->with('status_message', "Producto {$created['name']} ({$created['codigo']}) registrado correctamente.");
    }

    public function update(Request $request, string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'product', 404);

        $validated = $this->validatedPayload($request, isCreate: false);

        $payload = $this->buildPersistencePayload($validated, [
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : (bool) $existing['is_active'],
        ], $existing);

        if ($request->hasFile('image')) {
            $payload['image_path'] = Storage::disk('public')->putFile('products', $request->file('image'));
        }

        $updated = $this->catalog->updateMedicine($existing['codigo'], $payload);

        return redirect()
            ->route('productos.index')
            ->with('status_message', "Producto {$updated['name']} actualizado correctamente.");
    }

    public function updateInventory(Request $request, string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'product', 404);

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'min_stock' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $updated = $this->catalog->updateInventory(
            $existing['codigo'],
            (float) $validated['price'],
            (int) $validated['stock'],
            isset($validated['min_stock']) ? (int) $validated['min_stock'] : null
        );

        return redirect()
            ->route('productos.index')
            ->with('status_message', "Precio y stock de {$updated['name']} actualizados.");
    }

    public function toggleStatus(string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'product', 404);

        $updated = $this->catalog->toggleMedicineStatus($existing['codigo']);
        abort_if($updated === null, 404);

        $action = $updated['is_active'] ? 'reactivado' : 'dado de baja';

        return redirect()
            ->route('productos.index')
            ->with('status_message', "Producto {$updated['name']} {$action} correctamente.");
    }

    public function destroy(string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'product', 404);

        try {
            $removed = $this->catalog->deleteMedicine($existing['codigo']);
        } catch (DomainException|QueryException $e) {
            $message = $e instanceof DomainException
                ? $e->getMessage()
                : "No se puede eliminar {$existing['name']} ({$existing['codigo']}) porque tiene registros asociados en pedidos o carritos.";

            return redirect()
                ->route('productos.index')
                ->withErrors(['delete' => $message]);
        }

        abort_if($removed === null, 404);

        return redirect()
            ->route('productos.index')
            ->with('status_message', "Producto {$removed['name']} ({$removed['codigo']}) eliminado del catálogo.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, bool $isCreate): array
    {
        $retailCategories = array_values(array_map(
            fn (array $cat): string => $cat['name'],
            array_filter(
                $this->catalog->allCategories(),
                fn (array $cat): bool => $cat['slug'] !== 'medicamentos'
            )
        ));

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:140'],
            'category' => ['required', 'string', 'max:80', Rule::in($retailCategories)],
            'san_nicolas_category' => ['nullable', 'string', 'max:80'],
            'brand' => ['required', 'string', 'min:2', 'max:80'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'previous_price' => ['nullable', 'numeric', 'min:0.01', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'min_stock' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'description' => ['nullable', 'string', 'max:1200'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($isCreate) {
            $rules['codigo'] = ['required', 'string', 'min:3', 'max:24'];
        }

        return $request->validate($rules, [
            'codigo.required' => 'El código SKU del producto es obligatorio.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'category.in' => 'Los productos deben pertenecer a una categoría comercial (no medicinal).',
            'brand.required' => 'La marca o fabricante es obligatorio.',
            'price.required' => 'El precio unitario es obligatorio.',
            'stock.required' => 'Las existencias iniciales son obligatorias.',
            'image.image' => 'El archivo seleccionado debe ser una imagen válida (JPG, PNG o WebP).',
            'image.max' => 'La imagen no debe superar los 2 MB.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>|null  $existing
     * @return array<string, mixed>
     */
    private function buildPersistencePayload(array $validated, array $extra = [], ?array $existing = null): array
    {
        $description = trim((string) ($validated['description'] ?? ($existing['description']
            ?? ($validated['name'].' — '.$validated['brand'].'.'))));

        return array_merge([
            'catalog_type' => 'product',
            'name' => $validated['name'],
            'active_ingredient' => $validated['category'],
            'category' => $validated['category'],
            'san_nicolas_category' => $validated['san_nicolas_category'] ?? ($existing['san_nicolas_category'] ?? null),
            'brand' => $validated['brand'],
            'laboratory' => $existing['laboratory'] ?? $validated['brand'],
            'batch' => null,
            'expiry' => null,
            'expiry_status' => 'Vigente',
            'prescription' => 'Venta Libre',
            'price' => (float) $validated['price'],
            'previous_price' => array_key_exists('previous_price', $validated)
                ? ($validated['previous_price'] !== null ? (float) $validated['previous_price'] : null)
                : ($existing['previous_price'] ?? null),
            'stock' => (int) $validated['stock'],
            'min_stock' => (int) ($validated['min_stock'] ?? ($existing['min_stock'] ?? 15)),
            'description' => $description,
            'indications' => $existing['indications'] ?? '',
            'contraindications' => $existing['contraindications'] ?? '',
        ], $extra);
    }

    private function nextProductCode(): string
    {
        $max = 700;
        foreach ($this->catalog->all(['catalog_type' => 'product']) as $item) {
            if (preg_match('/^(?:PRD|DER|VIT|PER|BEB|FRA)-(\d+)$/i', (string) $item['codigo'], $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return sprintf('PRD-%05d', $max + 1);
    }
}
