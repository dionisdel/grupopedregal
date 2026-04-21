<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->company(),
            'descripcion' => $this->faker->sentence(),
            'logo_url' => null,
            'activo' => true,
        ];
    }
}
