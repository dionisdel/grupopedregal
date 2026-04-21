<?php

namespace Database\Factories;

use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return [
            'producto_id' => Product::factory(),
            'tipo_cliente_id' => CustomerType::factory(),
            'precio_base' => $this->faker->randomFloat(4, 1, 500),
            'descuento_porcentaje' => 0,
            'fecha_vigencia_desde' => now()->subMonth(),
            'activo' => true,
        ];
    }
}
