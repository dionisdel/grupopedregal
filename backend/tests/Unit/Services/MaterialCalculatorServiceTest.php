<?php

namespace Tests\Unit\Services;

use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductSpec;
use App\Services\MaterialCalculatorService;
use App\Services\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private MaterialCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MaterialCalculatorService(new PriceCalculationService());
    }

    public function test_calculate_returns_correct_quantity_for_positive_m2(): void
    {
        $product = Product::factory()->create(['nombre' => 'Placa Yeso 120x60']);
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 5.0000,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => 0.7200,
        ]);

        $result = $this->service->calculate($product, 10.0);

        // ceil(10 / 0.72) = ceil(13.888...) = 14
        $this->assertEquals(14, $result['materiales'][0]['cantidad_total']);
        $this->assertEquals(70.0, $result['materiales'][0]['total']); // 14 * 5.0
        $this->assertEquals(70.0, $result['subtotal_sin_merma']);
        $this->assertEquals(5.0, $result['merma_porcentaje']);
        $this->assertEquals(73.5, $result['total_con_merma']); // 70 * 1.05
    }

    public function test_calculate_returns_zero_quantity_when_m2_is_zero(): void
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 10.0000,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => 1.0000,
        ]);

        $result = $this->service->calculate($product, 0.0);

        $this->assertEquals(0, $result['materiales'][0]['cantidad_total']);
        $this->assertEquals(0.0, $result['materiales'][0]['total']);
        $this->assertEquals(0.0, $result['subtotal_sin_merma']);
        $this->assertEquals(0.0, $result['total_con_merma']);
    }

    public function test_calculate_returns_zero_quantity_when_m2_is_negative(): void
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 10.0000,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => 1.0000,
        ]);

        $result = $this->service->calculate($product, -5.0);

        $this->assertEquals(0, $result['materiales'][0]['cantidad_total']);
        $this->assertEquals(0.0, $result['subtotal_sin_merma']);
        $this->assertEquals(0.0, $result['total_con_merma']);
    }

    public function test_calculate_applies_custom_merma(): void
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 10.0000,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => 1.0000,
        ]);

        $result = $this->service->calculate($product, 10.0, 10.0);

        // ceil(10 / 1.0) = 10, total = 10 * 10 = 100
        $this->assertEquals(100.0, $result['subtotal_sin_merma']);
        $this->assertEquals(10.0, $result['merma_porcentaje']);
        $this->assertEquals(110.0, $result['total_con_merma']); // 100 * 1.10
    }

    public function test_calculate_handles_product_without_specs(): void
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 10.0000,
            'activo' => true,
        ]);

        $result = $this->service->calculate($product, 10.0);

        // No specs → m2_por_unidad = 0 → quantity = 0
        $this->assertEquals(0, $result['materiales'][0]['cantidad_total']);
        $this->assertEquals(0.0, $result['subtotal_sin_merma']);
        $this->assertEquals(0.0, $result['total_con_merma']);
    }

    public function test_calculate_returns_correct_structure(): void
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 8.0000,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => 0.5000,
        ]);

        $result = $this->service->calculate($product, 5.0);

        $this->assertArrayHasKey('materiales', $result);
        $this->assertArrayHasKey('subtotal_sin_merma', $result);
        $this->assertArrayHasKey('merma_porcentaje', $result);
        $this->assertArrayHasKey('total_con_merma', $result);

        $material = $result['materiales'][0];
        $this->assertArrayHasKey('descripcion', $material);
        $this->assertArrayHasKey('cantidad_por_m2', $material);
        $this->assertArrayHasKey('cantidad_total', $material);
        $this->assertArrayHasKey('unidad', $material);
        $this->assertArrayHasKey('precio_unitario', $material);
        $this->assertArrayHasKey('total', $material);
    }

    public function test_calculate_uses_product_unit_abbreviation(): void
    {
        $product = Product::factory()->create();
        $unit = $product->unidadBase;
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 5.0000,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => 1.0000,
        ]);

        $result = $this->service->calculate($product, 1.0);

        $this->assertEquals($unit->abreviatura, $result['materiales'][0]['unidad']);
    }

    public function test_calculate_row_total_equals_quantity_times_price(): void
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 3.7500,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => 0.3000,
        ]);

        $result = $this->service->calculate($product, 7.5);

        // ceil(7.5 / 0.3) = ceil(25) = 25
        $material = $result['materiales'][0];
        $this->assertEquals(25, $material['cantidad_total']);
        $this->assertEquals(round(25 * 3.75, 2), $material['total']);
    }
}
