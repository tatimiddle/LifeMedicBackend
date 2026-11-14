<?php

namespace App\Contracts;

interface CatalogRepositoryInterface
{
    /**
     * Lista las categorías activas disponibles del catálogo farmacéutico.
     *
     * @return array<int, array<string, mixed>>
     */
    public function categories(): array;

    /**
     * Lista todas las categorías (activas o inactivas) para el panel administrativo.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function allCategories(array $filters = []): array;

    /**
     * Busca una categoría por su identificador slug.
     *
     * @return array<string, mixed>|null
     */
    public function findCategory(string $slug): ?array;

    /**
     * Registra una nueva categoría en el catálogo persistente.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createCategory(array $data): array;

    /**
     * Actualiza una categoría existente y sincroniza su nombre en los medicamentos asignados.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function updateCategory(string $slug, array $data): ?array;

    /**
     * Activa o desactiva lógicamente una categoría.
     *
     * @return array<string, mixed>|null
     */
    public function toggleCategoryStatus(string $slug): ?array;

    /**
     * Elimina una categoría vacía del catálogo.
     *
     * @return array{deleted: bool, message: string}
     */
    public function deleteCategory(string $slug): array;

    /**
     * Lista medicamentos/productos aplicando filtros opcionales.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function all(array $filters = []): array;

    /**
     * Consulta el detalle de un medicamento o producto por ID numérico o código SKU.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $identifier): ?array;

    /**
     * Consulta un medicamento o producto exclusivamente por su código SKU.
     *
     * @return array<string, mixed>|null
     */
    public function findByCode(string $codigo): ?array;

    /**
     * Registra un nuevo medicamento en el catálogo persistente.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createMedicine(array $data): array;

    /**
     * Actualiza la ficha completa de un medicamento existente por código SKU.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function updateMedicine(string $codigo, array $data): ?array;

    /**
     * Actualiza precio y control de existencias de un medicamento.
     *
     * @return array<string, mixed>|null
     */
    public function updateInventory(string $codigo, float $price, int $stock, ?int $minStock = null): ?array;

    /**
     * Cambia el estado de alta/baja lógica (is_active) de un medicamento.
     *
     * @return array<string, mixed>|null
     */
    public function toggleMedicineStatus(string $codigo): ?array;

    /**
     * Elimina un medicamento del catálogo.
     *
     * @return array<string, mixed>|null
     */
    public function deleteMedicine(string $codigo): ?array;

    /**
     * Resume indicadores en tiempo real del inventario y categorías.
     *
     * @return array<string, int>
     */
    public function inventorySummary(): array;
}
