<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->bothify('U-###'),
            'nombre' => $this->faker->word(),
            'abreviatura' => $this->faker->lexify('??'),
            'tipo' => 'cantidad',
            'activo' => true,
        ];
    }
}
