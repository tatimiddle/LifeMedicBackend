<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Contracts\CatalogRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MedicineResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogRepositoryInterface $catalog) {}

    public function categories(): JsonResponse
    {
        $categories = $this->catalog->categories();

        return ApiResponse::success($categories, 'Categorías obtenidas correctamente.', Response::HTTP_OK, [
            'total' => count($categories),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category',
            'categoria',
            'type',
            'catalog_type',
            'search',
            'nombre',
            'q',
            'only_active',
            'disponible',
            'sort',
        ]);

        $items = $this->catalog->all($filters);

        return ApiResponse::success(
            MedicineResource::collection($items)->resolve(),
            'Catálogo de medicamentos obtenido correctamente.',
            Response::HTTP_OK,
            [
                'total' => count($items),
                'filters' => array_filter($filters, fn (mixed $v): bool => $v !== null && $v !== ''),
            ]
        );
    }

    public function show(string $identifier): JsonResponse
    {
        $item = $this->catalog->find($identifier);

        if ($item === null) {
            return ApiResponse::error(
                'Producto o medicamento no encontrado.',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            (new MedicineResource($item))->resolve(),
            'Detalle del producto obtenido correctamente.'
        );
    }
}
