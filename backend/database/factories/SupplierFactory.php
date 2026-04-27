<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->bothify('SUP-###'),
            'nombre_comercial' => $this->faker->company(),
            'nif_cif' => $this->faker->unique()->bothify('B########'),
            'activo' => true,
        ];
    }
}
