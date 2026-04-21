<?php

namespace Tests\Unit\Services;

use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PriceCalculationService();
    }

    public function test_get_pvp_price_returns_first_active_price(): void
    {
        $product = Product::factory()->create();
        $type1 = CustomerType::factory()->create(['orden' => 1]);
        $type2 = CustomerType::factory()->create(['orden' => 2]);

        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type1->id,
            'precio_base' => 15.5000,
            'activo' => true,
        ]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type2->id,
            'precio_base' => 20.0000,
            'activo' => true,
        ]);

        $price = $this->service->getPvpPrice($product);

        $this->assertEquals(15.5, $price);
    }

    public function test_get_pvp_price_skips_inactive_prices(): void
    {
        $product = Product::factory()->create();
        $type1 = CustomerType::factory()->create(['orden' => 1]);
        $type2 = CustomerType::factory()->create(['orden' => 2]);

        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type1->id,
            'precio_base' => 10.0000,
            'activo' => false,
        ]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type2->id,
            'precio_base' => 25.0000,
            'activo' => true,
        ]);

        $price = $this->service->getPvpPrice($product);

        $this->assertEquals(25.0, $price);
    }

    public function test_get_pvp_price_returns_zero_when_no_prices(): void
    {
        $product = Product::factory()->create();

        $price = $this->service->getPvpPrice($product);

        $this->assertEquals(0.0, $price);
    }

    public function test_get_client_price_returns_price_for_customer_type(): void
    {
        $product = Product::factory()->create();
        $type1 = CustomerType::factory()->create(['nombre' => 'CAMION VIP']);
        $type2 = CustomerType::factory()->create(['nombre' => 'EMPRESAS']);

        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type1->id,
            'precio_base' => 10.0000,
            'activo' => true,
        ]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type2->id,
            'precio_base' => 18.5000,
            'activo' => true,
        ]);

        $price = $this->service->getClientPrice($product, $type2);

        $this->assertEquals(18.5, $price);
    }

    public function test_get_client_price_returns_zero_when_no_matching_price(): void
    {
        $product = Product::factory()->create();
        $type1 = CustomerType::factory()->create();
        $type2 = CustomerType::factory()->create();

        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type1->id,
            'precio_base' => 10.0000,
            'activo' => true,
        ]);

        $price = $this->service->getClientPrice($product, $type2);

        $this->assertEquals(0.0, $price);
    }

    public function test_get_client_price_skips_inactive_price(): void
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create();

        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => 30.0000,
            'activo' => false,
        ]);

        $price = $this->service->getClientPrice($product, $type);

        $this->assertEquals(0.0, $price);
    }

    public function test_calculate_iva_with_default_rate(): void
    {
        $result = $this->service->calculateIva(100.0);

        $this->assertEquals(['base' => 100.0, 'iva' => 21.0, 'total' => 121.0], $result);
    }

    public function test_calculate_iva_with_custom_rate(): void
    {
        $result = $this->service->calculateIva(100.0, 10.0);

        $this->assertEquals(['base' => 100.0, 'iva' => 10.0, 'total' => 110.0], $result);
    }

    public function test_calculate_iva_rounds_to_two_decimals(): void
    {
        // 33.33 * 21 / 100 = 6.9993 → rounds to 7.0
        $result = $this->service->calculateIva(33.33);

        $this->assertEquals(33.33, $result['base']);
        $this->assertEquals(7.0, $result['iva']);
        $this->assertEquals(40.33, $result['total']);
    }

    public function test_calculate_iva_with_zero_price(): void
    {
        $result = $this->service->calculateIva(0.0);

        $this->assertEquals(['base' => 0.0, 'iva' => 0.0, 'total' => 0.0], $result);
    }
}
