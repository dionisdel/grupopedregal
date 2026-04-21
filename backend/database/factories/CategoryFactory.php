<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $nombre = $this->faker->unique()->words(2, true);

        return [
            'parent_id' => null,
            'codigo' => $this->faker->unique()->bothify('CAT-###'),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'descripcion' => $this->faker->sentence(),
            'descripcion_web' => $this->faker->sentence(),
            'imagen_url' => $this->faker->optional()->imageUrl(),
            'orden' => $this->faker->numberBetween(1, 100),
            'nivel' => 1,
            'activo' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }

    public function level(int $nivel): static
    {
        return $this->state(fn () => ['nivel' => $nivel]);
    }
}
