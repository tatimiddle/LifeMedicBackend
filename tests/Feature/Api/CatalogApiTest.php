<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_returns_standardized_json_list(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonStructure([
                'success',
                'status',
                'message',
                'data' => [['slug', 'name', 'items_count']],
                'meta' => ['total'],
            ]);

        $names = array_column($response->json('data'), 'name');
        $this->assertContains('Medicamentos', $names);
        $this->assertContains('Dermocosmética', $names);
        $this->assertContains('Vitaminas', $names);
    }

    public function test_medicines_endpoint_lists_catalog_in_json_even_without_accept_header(): void
    {
        $response = $this->get('/api/medicines');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonFragment(['codigo' => 'MED-00101', 'name' => 'Paracetamol 500mg (Caja x 20)']);
    }

    public function test_medicines_endpoint_filters_by_category_with_or_without_accents(): void
    {
        $byAscii = $this->getJson('/api/medicines?category=Dermocosmetica')->assertOk();
        $byAccented = $this->getJson('/api/medicines?categoria=Dermocosmética')->assertOk();

        $this->assertNotEmpty($byAscii->json('data'));
        $this->assertSame($byAscii->json('meta.total'), $byAccented->json('meta.total'));

        foreach ($byAscii->json('data') as $item) {
            $this->assertSame('Dermocosmética', $item['category']);
        }

        $medicines = $this->getJson('/api/medicines?category=medicamentos')->assertOk();
        $codes = array_column($medicines->json('data'), 'codigo');
        $this->assertContains('MED-00101', $codes);
        $this->assertContains('MED-00103', $codes);
        $this->assertNotContains('DER-00205', $codes);
    }

    public function test_medicines_endpoint_searches_by_name_ingredient_or_sku_and_filters_available(): void
    {
        $byName = $this->getJson('/api/medicines?search=Ibuprofeno')->assertOk();
        $this->assertContains('MED-00102', array_column($byName->json('data'), 'codigo'));

        $byNombreAlias = $this->getJson('/api/medicines?nombre=Protector+Solar')->assertOk();
        $this->assertContains('DER-00205', array_column($byNombreAlias->json('data'), 'codigo'));

        $bySku = $this->getJson('/api/medicines?q=MED-00104')->assertOk();
        $this->assertCount(1, $bySku->json('data'));
        $this->assertSame('Metformina 850mg (Tabletas x 30)', $bySku->json('data.0.name'));

        $activeOnly = $this->getJson('/api/medicines?only_active=1&sort=price_asc')->assertOk();
        $activeCodes = array_column($activeOnly->json('data'), 'codigo');
        $this->assertNotContains('MED-00104', $activeCodes);
        $prices = array_column($activeOnly->json('data'), 'price');
        $sortedPrices = $prices;
        sort($sortedPrices);
        $this->assertSame($sortedPrices, $prices);
    }

    public function test_medicines_and_products_detail_endpoints_support_id_or_sku_and_404_when_missing(): void
    {
        $this->getJson('/api/medicines/MED-00101')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.codigo', 'MED-00101')
            ->assertJsonPath('data.name', 'Paracetamol 500mg (Caja x 20)');

        $this->getJson('/api/products/1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.id', 1);

        $this->getJson('/api/medicines/NO-EXISTE-999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 404);
    }

    public function test_unknown_api_endpoint_returns_standardized_404_json(): void
    {
        $this->get('/api/endpoint-inexistente')
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 404);
    }
}
