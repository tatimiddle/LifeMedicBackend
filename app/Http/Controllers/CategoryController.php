<?php

namespace App\Http\Controllers;

use App\Contracts\CatalogRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CatalogRepositoryInterface $catalog) {}

    public function index(Request $request): View
    {
        $filters = [
            'estado' => (string) $request->query('estado', 'todos'),
            'buscar' => (string) $request->query('buscar', ''),
        ];

        $allCategories = $this->catalog->allCategories();
        $activeCount = count(array_filter($allCategories, fn (array $c): bool => (bool) $c['is_active']));
        $classifiedItems = array_sum(array_map(fn (array $c): int => (int) ($c['items_count'] ?? 0), $allCategories));

        return view('modules.categorias', [
            'categories' => $this->catalog->allCategories($filters),
            'summary' => [
                'total' => count($allCategories),
                'active' => $activeCount,
                'inactive' => count($allCategories) - $activeCount,
                'classified_items' => $classifiedItems,
            ],
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:240'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
        ]);

        $slug = Str::slug($validated['name']);
        if ($slug === '' || $this->catalog->findCategory($slug) !== null) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Ya existe una categoría con un nombre equivalente.']);
        }

        $created = $this->catalog->createCategory([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('status_message', "Categoría \"{$created['name']}\" registrada correctamente.");
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $existing = $this->catalog->findCategory($slug);
        abort_if($existing === null, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:240'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'El nombre de la categoría es obligatorio.',
        ]);

        $newSlug = Str::slug($validated['name']);
        $conflict = $this->catalog->findCategory($newSlug);
        if ($newSlug === '' || ($conflict !== null && $conflict['slug'] !== $existing['slug'])) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Otra categoría ya utiliza ese nombre.']);
        }

        $updated = $this->catalog->updateCategory($existing['slug'], [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : (bool) $existing['is_active'],
        ]);

        return redirect()
            ->route('categorias.index')
            ->with('status_message', "Categoría \"{$updated['name']}\" actualizada correctamente.");
    }

    public function toggleStatus(string $slug): RedirectResponse
    {
        $updated = $this->catalog->toggleCategoryStatus($slug);
        abort_if($updated === null, 404);

        $label = $updated['is_active'] ? 'activada' : 'desactivada';

        return redirect()
            ->route('categorias.index')
            ->with('status_message', "Categoría \"{$updated['name']}\" {$label} correctamente.");
    }

    public function destroy(string $slug): RedirectResponse
    {
        $result = $this->catalog->deleteCategory($slug);

        if (! $result['deleted']) {
            return redirect()
                ->route('categorias.index')
                ->withErrors(['delete' => $result['message']]);
        }

        return redirect()
            ->route('categorias.index')
            ->with('status_message', $result['message']);
    }
}
