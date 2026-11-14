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

class MedicineController extends Controller
{
    private const RETAIL_ONLY_SLUGS = [
        'dermocosmetica',
        'vitaminas',
        'cuidado-personal',
        'bebe',
        'fragancias',
    ];

    public function __construct(private readonly CatalogRepositoryInterface $catalog) {}

    public function index(Request $request): View
    {
        $filters = [
            'estado' => (string) $request->query('estado', 'Todos'),
            'categoria' => (string) $request->query('categoria', 'Todos'),
            'receta' => (string) $request->query('receta', 'Todos'),
            'buscar' => (string) $request->query('buscar', ''),
        ];

        $categories = array_values(array_filter(
            $this->catalog->allCategories(),
            fn (array $cat): bool => ! in_array($cat['slug'], self::RETAIL_ONLY_SLUGS, true)
        ));

        $summary = $this->catalog->inventorySummary();
        $items = $this->catalog->all(array_merge($filters, ['catalog_type' => 'medicine']));

        return view('modules.medicamentos', [
            'medicines' => [
                'kpis' => [
                    ['label' => 'Medicamentos Clínicos', 'value' => (string) $summary['clinical_medicines'], 'trend' => null, 'positive' => true, 'icon' => 'medicine'],
                    ['label' => 'Bajo Stock / Alertas', 'value' => (string) $summary['clinical_low_stock_alerts'], 'trend' => null, 'positive' => $summary['clinical_low_stock_alerts'] === 0, 'icon' => 'stock-alert'],
                    ['label' => 'Por Vencer (30d)', 'value' => (string) $summary['clinical_expiring_soon'], 'trend' => null, 'positive' => false, 'icon' => 'invoice'],
                ],
                'items' => $items,
            ],
            'categories' => $categories,
            'summary' => $summary,
            'filters' => $filters,
            'nextSuggestedCode' => $this->nextMedicineCode(),
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

        $payload = $this->buildPersistencePayload($request, $validated, [
            'codigo' => $codigo,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        if ($request->hasFile('image')) {
            $payload['image_path'] = Storage::disk('public')->putFile('medicines', $request->file('image'));
        }

        $created = $this->catalog->createMedicine($payload);

        return redirect()
            ->route('medicamentos.index')
            ->with('status_message', "Medicamento {$created['name']} ({$created['codigo']}) registrado correctamente.");
    }

    public function update(Request $request, string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'medicine', 404);

        $validated = $this->validatedPayload($request, isCreate: false);

        $payload = $this->buildPersistencePayload($request, $validated, [
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : (bool) $existing['is_active'],
        ], $existing);

        if ($request->hasFile('image')) {
            $payload['image_path'] = Storage::disk('public')->putFile('medicines', $request->file('image'));
        }

        $updated = $this->catalog->updateMedicine($existing['codigo'], $payload);

        return redirect()
            ->route('medicamentos.index')
            ->with('status_message', "Medicamento {$updated['name']} actualizado correctamente.");
    }

    public function updateInventory(Request $request, string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'medicine', 404);

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
            ->route('medicamentos.index')
            ->with('status_message', "Precio y stock de {$updated['name']} actualizados.");
    }

    public function toggleStatus(string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'medicine', 404);

        $updated = $this->catalog->toggleMedicineStatus($existing['codigo']);
        abort_if($updated === null, 404);

        $action = $updated['is_active'] ? 'reactivado' : 'dado de baja';

        return redirect()
            ->route('medicamentos.index')
            ->with('status_message', "Medicamento {$updated['name']} {$action} correctamente.");
    }

    public function destroy(string $codigo): RedirectResponse
    {
        $existing = $this->catalog->findByCode($codigo);
        abort_if($existing === null || ($existing['catalog_type'] ?? 'medicine') !== 'medicine', 404);

        try {
            $removed = $this->catalog->deleteMedicine($existing['codigo']);
        } catch (DomainException|QueryException $e) {
            $message = $e instanceof DomainException
                ? $e->getMessage()
                : "No se puede eliminar {$existing['name']} ({$existing['codigo']}) porque tiene registros asociados en pedidos o carritos.";

            return redirect()
                ->route('medicamentos.index')
                ->withErrors(['delete' => $message]);
        }

        abort_if($removed === null, 404);

        return redirect()
            ->route('medicamentos.index')
            ->with('status_message', "Medicamento {$removed['name']} ({$removed['codigo']}) eliminado del catálogo.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:140'],
            'active_ingredient' => ['required', 'string', 'min:2', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'san_nicolas_category' => ['nullable', 'string', 'max:80'],
            'brand' => ['required', 'string', 'min:2', 'max:80'],
            'laboratory' => ['nullable', 'string', 'max:80'],
            'formula_summary' => ['nullable', 'string', 'max:255'],
            'batch' => ['nullable', 'string', 'max:40'],
            'expiry' => ['nullable', 'string', 'max:40'],
            'expiry_status' => ['required', 'string', Rule::in(['Vigente', 'Por Vencer', 'Vencido'])],
            'prescription' => ['required', 'string', Rule::in(['Venta Libre', 'Con Receta'])],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'previous_price' => ['nullable', 'numeric', 'min:0.01', 'max:99999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'min_stock' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'indications' => ['nullable', 'string', 'max:1200'],
            'contraindications' => ['nullable', 'string', 'max:1200'],
            'description' => ['nullable', 'string', 'max:1200'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($isCreate) {
            $rules['codigo'] = ['required', 'string', 'min:3', 'max:24'];
        }

        return $request->validate($rules, [
            'codigo.required' => 'El código SKU del medicamento es obligatorio.',
            'name.required' => 'El nombre comercial es obligatorio.',
            'active_ingredient.required' => 'El principio activo es obligatorio.',
            'brand.required' => 'El laboratorio fabricante es obligatorio.',
            'price.required' => 'El precio unitario es obligatorio.',
            'stock.required' => 'El stock inicial es obligatorio.',
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
    private function buildPersistencePayload(Request $request, array $validated, array $extra = [], ?array $existing = null): array
    {
        $laboratory = trim((string) ($validated['laboratory'] ?? ''));
        if ($laboratory === '') {
            $laboratory = $existing['laboratory'] ?? $validated['brand'];
        }

        $description = $validated['description'] ?? ($existing['description']
            ?? ('Medicamento farmacéutico '.$validated['active_ingredient'].' distribuido por '.$validated['brand'].'.'));

        return array_merge([
            'catalog_type' => 'medicine',
            'name' => $validated['name'],
            'active_ingredient' => $validated['active_ingredient'],
            'category' => $validated['category'],
            'san_nicolas_category' => $validated['san_nicolas_category'] ?? ($existing['san_nicolas_category'] ?? null),
            'brand' => $validated['brand'],
            'laboratory' => $laboratory,
            'formula_summary' => $validated['formula_summary'] ?? ($existing['formula_summary'] ?? $validated['active_ingredient']),
            'batch' => $validated['batch'] ?? null,
            'expiry' => $validated['expiry'] ?? null,
            'expiry_status' => $validated['expiry_status'],
            'prescription' => $validated['prescription'],
            'price' => (float) $validated['price'],
            'previous_price' => array_key_exists('previous_price', $validated)
                ? ($validated['previous_price'] !== null ? (float) $validated['previous_price'] : null)
                : ($existing['previous_price'] ?? null),
            'stock' => (int) $validated['stock'],
            'min_stock' => (int) ($validated['min_stock'] ?? ($existing['min_stock'] ?? 20)),
            'indications' => $validated['indications'] ?? ($existing['indications'] ?? $description),
            'contraindications' => $validated['contraindications'] ?? ($existing['contraindications'] ?? ''),
            'description' => $description,
        ], $extra);
    }

    private function nextMedicineCode(): string
    {
        $max = 110;
        foreach ($this->catalog->all(['catalog_type' => 'medicine']) as $item) {
            if (preg_match('/^MED-(\d+)$/i', (string) $item['codigo'], $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return sprintf('MED-%05d', $max + 1);
    }
}
