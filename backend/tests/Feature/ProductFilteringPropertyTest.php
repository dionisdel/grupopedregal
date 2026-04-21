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

/**
 * Feature: web-portal-product-catalog
 *
 * Property-based tests for product filtering.
 * Uses Faker with iteration loops to simulate PBT (100+ iterations).
 */
class ProductFilteringPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const PBT_ITERATIONS = 100;

    /**
     * Create a product with all required relations and an active PVP price.
     */
    private function createProduct(array $overrides = [], ?float $price = null): Product
    {
        $product = Product::factory()->create($overrides);

        if ($price !== null) {
            $customerType = CustomerType::factory()->create(['codigo' => 'PVP-' . uniqid(), 'orden' => 1]);
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

    /**
     * Feature: web-portal-product-catalog, Property 3: Product filtering returns only matching products
     *
     * For any combination of active filters (category_id, brand_id, supplier_id),
     * every product returned should satisfy all active filter conditions simultaneously.
     *
     * **Validates: Requirements 2.3, 2.6**
     */
    public function test_property3_product_filtering_returns_only_matching_products(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean data between iterations
            Product::query()->forceDelete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();
            CustomerType::query()->delete();
            ProductPrice::query()->delete();

            // Create shared resources
            $unit = Unit::factory()->create();
            $categories = Category::factory()->count(fake()->numberBetween(2, 4))->create([
                'nivel' => 1,
                'activo' => true,
            ]);
            $brands = Brand::factory()->count(fake()->numberBetween(2, 3))->create();
            $suppliers = Supplier::factory()->count(fake()->numberBetween(2, 3))->create();

            // Create a pool of visible products with random assignments
            $productCount = fake()->numberBetween(5, 12);
            $products = [];
            for ($j = 0; $j < $productCount; $j++) {
                $products[] = $this->createProduct([
                    'categoria_id' => $categories->random()->id,
                    'marca_id' => $brands->random()->id,
                    'proveedor_principal_id' => $suppliers->random()->id,
                    'unidad_base_id' => $unit->id,
                    'visible_web' => true,
                ], fake()->randomFloat(2, 1, 500));
            }

            // Randomly pick which filters to apply (at least one)
            $filterCategory = fake()->boolean(60) ? $categories->random() : null;
            $filterBrand = fake()->boolean(50) ? $brands->random() : null;
            $filterSupplier = fake()->boolean(50) ? $suppliers->random() : null;

            // Ensure at least one filter is active
            if (!$filterCategory && !$filterBrand && !$filterSupplier) {
                $filterCategory = $categories->random();
            }

            // Build query params
            $params = [];
            if ($filterCategory) {
                $params['category_id'] = $filterCategory->id;
            }
            if ($filterBrand) {
                $params['brand_id'] = $filterBrand->id;
            }
            if ($filterSupplier) {
                $params['supplier_id'] = $filterSupplier->id;
            }

            $queryString = http_build_query($params);
            $response = $this->getJson("/api/products/catalog?{$queryString}");
            $response->assertStatus(200);

            $data = $response->json('data');

            // Verify every returned product satisfies ALL active filters
            foreach ($data as $index => $item) {
                $product = Product::find($item['id']);
                $this->assertNotNull($product, "Iteration {$i}: Product ID {$item['id']} not found in DB");

                if ($filterCategory) {
                    // category_id filter includes subcategories, but here all are level 1
                    $this->assertEquals(
                        $filterCategory->id,
                        $product->categoria_id,
                        "Iteration {$i}, product {$index}: category_id mismatch. Expected {$filterCategory->id}, got {$product->categoria_id}"
                    );
                }

                if ($filterBrand) {
                    $this->assertEquals(
                        $filterBrand->id,
                        $product->marca_id,
                        "Iteration {$i}, product {$index}: brand_id mismatch. Expected {$filterBrand->id}, got {$product->marca_id}"
                    );
                }

                if ($filterSupplier) {
                    $this->assertEquals(
                        $filterSupplier->id,
                        $product->proveedor_principal_id,
                        "Iteration {$i}, product {$index}: supplier_id mismatch. Expected {$filterSupplier->id}, got {$product->proveedor_principal_id}"
                    );
                }
            }
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 4: Results counter matches filtered product count
     *
     * For any filter state, the total count in the paginated response should match
     * the actual number of matching products.
     *
     * **Validates: Requirements 2.4**
     */
    public function test_property4_results_counter_matches_filtered_product_count(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean data between iterations
            Product::query()->forceDelete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();
            CustomerType::query()->delete();
            ProductPrice::query()->delete();

            // Create shared resources
            $unit = Unit::factory()->create();
            $categories = Category::factory()->count(fake()->numberBetween(2, 4))->create([
                'nivel' => 1,
                'activo' => true,
            ]);
            $brands = Brand::factory()->count(fake()->numberBetween(2, 3))->create();
            $suppliers = Supplier::factory()->count(fake()->numberBetween(2, 3))->create();

            // Create products with random visibility
            $productCount = fake()->numberBetween(3, 10);
            for ($j = 0; $j < $productCount; $j++) {
                $this->createProduct([
                    'categoria_id' => $categories->random()->id,
                    'marca_id' => $brands->random()->id,
                    'proveedor_principal_id' => $suppliers->random()->id,
                    'unidad_base_id' => $unit->id,
                    'visible_web' => true,
                ], fake()->randomFloat(2, 1, 500));
            }

            // Randomly pick a filter combination (may be empty = no filters)
            $params = [];
            if (fake()->boolean(50)) {
                $params['category_id'] = $categories->random()->id;
            }
            if (fake()->boolean(40)) {
                $params['brand_id'] = $brands->random()->id;
            }
            if (fake()->boolean(40)) {
                $params['supplier_id'] = $suppliers->random()->id;
            }

            $queryString = http_build_query($params);
            $response = $this->getJson("/api/products/catalog?{$queryString}");
            $response->assertStatus(200);

            $responseData = $response->json();
            $total = $responseData['total'];
            $dataCount = count($responseData['data']);
            $perPage = $responseData['per_page'];
            $currentPage = $responseData['current_page'];

            // Count matching products manually in DB
            $query = Product::query()
                ->where('visible_web', true)
                ->whereHas('categoria', fn ($q) => $q->where('activo', true));

            if (isset($params['category_id'])) {
                $query->where('categoria_id', $params['category_id']);
            }
            if (isset($params['brand_id'])) {
                $query->where('marca_id', $params['brand_id']);
            }
            if (isset($params['supplier_id'])) {
                $query->where('proveedor_principal_id', $params['supplier_id']);
            }

            $expectedTotal = $query->count();

            $this->assertEquals(
                $expectedTotal,
                $total,
                "Iteration {$i}: Total count mismatch. Expected {$expectedTotal}, got {$total}. Filters: " . json_encode($params)
            );

            // On page 1, data count should be min(total, perPage)
            $expectedPageCount = min($expectedTotal, $perPage);
            $this->assertEquals(
                $expectedPageCount,
                $dataCount,
                "Iteration {$i}: Page data count mismatch. Expected {$expectedPageCount}, got {$dataCount}"
            );
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 5: Product table rows contain all required columns
     *
     * For any product in the catalog results, the response should include
     * nombre, categoria, marca, unidad, and precio_pvp fields.
     *
     * **Validates: Requirements 2.5**
     */
    public function test_property5_product_table_rows_contain_all_required_columns(): void
    {
        $requiredFields = ['id', 'nombre', 'slug', 'categoria', 'marca', 'unidad', 'precio_pvp'];

        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean data between iterations
            Product::query()->forceDelete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();
            CustomerType::query()->delete();
            ProductPrice::query()->delete();

            // Create shared resources
            $unit = Unit::factory()->create();
            $category = Category::factory()->create(['nivel' => 1, 'activo' => true]);
            $brand = Brand::factory()->create();
            $supplier = Supplier::factory()->create();

            // Create a random number of products
            $productCount = fake()->numberBetween(1, 5);
            for ($j = 0; $j < $productCount; $j++) {
                $this->createProduct([
                    'categoria_id' => $category->id,
                    'marca_id' => $brand->id,
                    'proveedor_principal_id' => $supplier->id,
                    'unidad_base_id' => $unit->id,
                    'visible_web' => true,
                ], fake()->randomFloat(2, 1, 500));
            }

            $response = $this->getJson('/api/products/catalog');
            $response->assertStatus(200);

            $data = $response->json('data');

            $this->assertCount(
                $productCount,
                $data,
                "Iteration {$i}: Expected {$productCount} products, got " . count($data)
            );

            foreach ($data as $index => $item) {
                foreach ($requiredFields as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $item,
                        "Iteration {$i}, product {$index}: Missing required field '{$field}'"
                    );
                }

                // Verify non-null values for display fields
                $this->assertNotNull(
                    $item['nombre'],
                    "Iteration {$i}, product {$index}: 'nombre' should not be null"
                );
                $this->assertNotNull(
                    $item['categoria'],
                    "Iteration {$i}, product {$index}: 'categoria' should not be null"
                );
                $this->assertNotNull(
                    $item['marca'],
                    "Iteration {$i}, product {$index}: 'marca' should not be null"
                );
                $this->assertNotNull(
                    $item['unidad'],
                    "Iteration {$i}, product {$index}: 'unidad' should not be null"
                );
                $this->assertNotNull(
                    $item['precio_pvp'],
                    "Iteration {$i}, product {$index}: 'precio_pvp' should not be null"
                );

                // Verify categoria matches the brand name, etc.
                $this->assertEquals(
                    $category->nombre,
                    $item['categoria'],
                    "Iteration {$i}, product {$index}: 'categoria' should match category name"
                );
                $this->assertEquals(
                    $brand->nombre,
                    $item['marca'],
                    "Iteration {$i}, product {$index}: 'marca' should match brand name"
                );
                $this->assertEquals(
                    $unit->abreviatura,
                    $item['unidad'],
                    "Iteration {$i}, product {$index}: 'unidad' should match unit abbreviation"
                );
            }
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 17: Public catalog respects visibility flags
     *
     * For any product with visible_web=false or belonging to a category with activo=false,
     * the catalog endpoint should never include that product.
     *
     * **Validates: Requirements 8.3, 8.4**
     */
    public function test_property17_public_catalog_respects_visibility_flags(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean data between iterations
            Product::query()->forceDelete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();
            CustomerType::query()->delete();
            ProductPrice::query()->delete();

            // Create shared resources
            $unit = Unit::factory()->create();
            $brand = Brand::factory()->create();
            $supplier = Supplier::factory()->create();

            $activeCategory = Category::factory()->create(['nivel' => 1, 'activo' => true]);
            $inactiveCategory = Category::factory()->create(['nivel' => 1, 'activo' => false]);

            // Randomly create products with different visibility combinations
            $visibleProducts = [];
            $hiddenProducts = [];

            $totalProducts = fake()->numberBetween(3, 8);
            for ($j = 0; $j < $totalProducts; $j++) {
                // Randomly pick a visibility scenario
                $scenario = fake()->randomElement(['visible', 'hidden_web', 'inactive_category']);

                switch ($scenario) {
                    case 'visible':
                        $product = $this->createProduct([
                            'categoria_id' => $activeCategory->id,
                            'marca_id' => $brand->id,
                            'proveedor_principal_id' => $supplier->id,
                            'unidad_base_id' => $unit->id,
                            'visible_web' => true,
                        ], fake()->randomFloat(2, 1, 500));
                        $visibleProducts[] = $product->id;
                        break;

                    case 'hidden_web':
                        $product = $this->createProduct([
                            'categoria_id' => $activeCategory->id,
                            'marca_id' => $brand->id,
                            'proveedor_principal_id' => $supplier->id,
                            'unidad_base_id' => $unit->id,
                            'visible_web' => false,
                        ], fake()->randomFloat(2, 1, 500));
                        $hiddenProducts[] = $product->id;
                        break;

                    case 'inactive_category':
                        $product = $this->createProduct([
                            'categoria_id' => $inactiveCategory->id,
                            'marca_id' => $brand->id,
                            'proveedor_principal_id' => $supplier->id,
                            'unidad_base_id' => $unit->id,
                            'visible_web' => true,
                        ], fake()->randomFloat(2, 1, 500));
                        $hiddenProducts[] = $product->id;
                        break;
                }
            }

            $response = $this->getJson('/api/products/catalog');
            $response->assertStatus(200);

            $data = $response->json('data');
            $returnedIds = array_column($data, 'id');

            // All returned products must be in the visible set
            foreach ($returnedIds as $returnedId) {
                $this->assertContains(
                    $returnedId,
                    $visibleProducts,
                    "Iteration {$i}: Product ID {$returnedId} should not appear in catalog (hidden or inactive category)"
                );
            }

            // No hidden product should appear
            foreach ($hiddenProducts as $hiddenId) {
                $this->assertNotContains(
                    $hiddenId,
                    $returnedIds,
                    "Iteration {$i}: Hidden product ID {$hiddenId} should not appear in catalog"
                );
            }

            // All visible products should appear
            foreach ($visibleProducts as $visibleId) {
                $this->assertContains(
                    $visibleId,
                    $returnedIds,
                    "Iteration {$i}: Visible product ID {$visibleId} should appear in catalog"
                );
            }
        }
    }
}
