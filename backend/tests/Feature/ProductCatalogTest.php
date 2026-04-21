<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithPrice(array $productAttrs = [], ?float $price = 12.50): Product
    {
        $unit = Unit::factory()->create();
        $category = Category::factory()->create(['nivel' => 1, 'activo' => true]);
        $brand = Brand::factory()->create();
        $supplier = Supplier::factory()->create();
        $customerType = CustomerType::factory()->create(['codigo' => 'PVP-' . uniqid(), 'orden' => 1]);

        $product = Product::factory()->create(array_merge([
            'categoria_id' => $category->id,
            'marca_id' => $brand->id,
            'proveedor_principal_id' => $supplier->id,
            'unidad_base_id' => $unit->id,
            'visible_web' => true,
        ], $productAttrs));

        if ($price !== null) {
            ProductPrice::factory()->create([
                'producto_id' => $product->id,
                'tipo_cliente_id' => $customerType->id,
                'precio_base' => $price,
                'fecha_vigencia_desde' => now()->subMonth(),
                'activo' => true,
            ]);
        }

        return $product;
    }

    public function test_returns_paginated_products(): void
    {
        $this->createProductWithPrice();

        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'nombre', 'slug', 'categoria', 'marca', 'unidad', 'precio_pvp']],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_paginates_15_per_page(): void
    {
        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200)
            ->assertJsonPath('per_page', 15);
    }

    public function test_excludes_products_with_visible_web_false(): void
    {
        $this->createProductWithPrice(['visible_web' => true, 'nombre' => 'Visible']);
        $this->createProductWithPrice(['visible_web' => false, 'nombre' => 'Hidden']);

        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Visible');
    }

    public function test_excludes_products_with_inactive_category(): void
    {
        $activeCategory = Category::factory()->create(['nivel' => 1, 'activo' => true]);
        $inactiveCategory = Category::factory()->create(['nivel' => 1, 'activo' => false]);

        $this->createProductWithPrice(['categoria_id' => $activeCategory->id, 'nombre' => 'Active Cat']);
        $this->createProductWithPrice(['categoria_id' => $inactiveCategory->id, 'nombre' => 'Inactive Cat']);

        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Active Cat');
    }

    public function test_filters_by_category_id_including_subcategories(): void
    {
        $parentCategory = Category::factory()->create(['nivel' => 1, 'activo' => true]);
        $childCategory = Category::factory()->create([
            'parent_id' => $parentCategory->id,
            'nivel' => 2,
            'activo' => true,
        ]);
        $otherCategory = Category::factory()->create(['nivel' => 1, 'activo' => true]);

        $this->createProductWithPrice(['categoria_id' => $parentCategory->id, 'nombre' => 'Parent Product']);
        $this->createProductWithPrice(['categoria_id' => $childCategory->id, 'nombre' => 'Child Product']);
        $this->createProductWithPrice(['categoria_id' => $otherCategory->id, 'nombre' => 'Other Product']);

        $response = $this->getJson("/api/products/catalog?category_id={$parentCategory->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_by_subcategory_id(): void
    {
        $parentCategory = Category::factory()->create(['nivel' => 1, 'activo' => true]);
        $childCategory = Category::factory()->create([
            'parent_id' => $parentCategory->id,
            'nivel' => 2,
            'activo' => true,
        ]);

        $this->createProductWithPrice(['categoria_id' => $parentCategory->id, 'nombre' => 'Parent']);
        $this->createProductWithPrice(['categoria_id' => $childCategory->id, 'nombre' => 'Child']);

        $response = $this->getJson("/api/products/catalog?subcategory_id={$childCategory->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Child');
    }

    public function test_filters_by_brand_id(): void
    {
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();

        $this->createProductWithPrice(['marca_id' => $brand1->id, 'nombre' => 'Brand1 Product']);
        $this->createProductWithPrice(['marca_id' => $brand2->id, 'nombre' => 'Brand2 Product']);

        $response = $this->getJson("/api/products/catalog?brand_id={$brand1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Brand1 Product');
    }

    public function test_filters_by_supplier_id(): void
    {
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        $this->createProductWithPrice(['proveedor_principal_id' => $supplier1->id, 'nombre' => 'Sup1 Product']);
        $this->createProductWithPrice(['proveedor_principal_id' => $supplier2->id, 'nombre' => 'Sup2 Product']);

        $response = $this->getJson("/api/products/catalog?supplier_id={$supplier1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Sup1 Product');
    }

    public function test_search_by_nombre(): void
    {
        $this->createProductWithPrice(['nombre' => 'Yeso Proyectable']);
        $this->createProductWithPrice(['nombre' => 'Placa Cartón']);

        $response = $this->getJson('/api/products/catalog?search=Yeso');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Yeso Proyectable');
    }

    public function test_search_by_descripcion(): void
    {
        $this->createProductWithPrice(['nombre' => 'Producto A', 'descripcion' => 'Material aislante térmico']);
        $this->createProductWithPrice(['nombre' => 'Producto B', 'descripcion' => 'Yeso estándar']);

        $response = $this->getJson('/api/products/catalog?search=aislante');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Producto A');
    }

    public function test_returns_pvp_price(): void
    {
        $this->createProductWithPrice(['nombre' => 'Test Product'], 25.5000);

        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.precio_pvp', 25.5);
    }

    public function test_returns_category_name_brand_name_unit_abbreviation(): void
    {
        $category = Category::factory()->create(['nivel' => 1, 'activo' => true, 'nombre' => 'Yesos']);
        $brand = Brand::factory()->create(['nombre' => 'Knauf']);
        $unit = Unit::factory()->create(['abreviatura' => 'kg']);

        $this->createProductWithPrice([
            'categoria_id' => $category->id,
            'marca_id' => $brand->id,
            'unidad_base_id' => $unit->id,
        ]);

        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.categoria', 'Yesos')
            ->assertJsonPath('data.0.marca', 'Knauf')
            ->assertJsonPath('data.0.unidad', 'kg');
    }

    public function test_endpoint_accessible_without_authentication(): void
    {
        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200);
    }

    public function test_returns_empty_when_no_products(): void
    {
        $response = $this->getJson('/api/products/catalog');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
