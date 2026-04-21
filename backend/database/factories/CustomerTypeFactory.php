<?php

namespace Database\Factories;

use App\Models\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerTypeFactory extends Factory
{
    protected $model = CustomerType::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->bothify('CT-###'),
            'nombre' => $this->faker->word(),
            'descripcion' => $this->faker->sentence(),
            'orden' => $this->faker->numberBetween(1, 10),
            'activo' => true,
        ];
    }
}
