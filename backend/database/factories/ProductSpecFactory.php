<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSpecFactory extends Factory
{
    protected $model = ProductSpec::class;

    public function definition(): array
    {
        return [
            'producto_id' => Product::factory(),
            'peso_kg' => $this->faker->randomFloat(3, 0.5, 50),
            'largo_cm' => $this->faker->randomFloat(2, 10, 300),
            'ancho_cm' => $this->faker->randomFloat(2, 10, 200),
            'alto_cm' => $this->faker->randomFloat(2, 1, 100),
            'volumen_m3' => $this->faker->randomFloat(4, 0.001, 1),
            'metros_por_unidad' => $this->faker->randomFloat(3, 0.1, 10),
            'm2_por_unidad' => $this->faker->randomFloat(4, 0.1, 5),
            'unidades_por_embalaje' => $this->faker->numberBetween(1, 50),
            'embalajes_por_palet' => $this->faker->numberBetween(1, 30),
            'unidades_por_palet' => $this->faker->numberBetween(10, 500),
            'palet_retornable' => $this->faker->boolean(),
        ];
    }
}
