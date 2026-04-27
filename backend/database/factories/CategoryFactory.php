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
            'nombre' => $nombre,
            'slug' => Str::slug($nombre) . '-' . $this->faker->unique()->randomNumber(4),
            'descripcion' => $this->faker->sentence(),
            'imagen_banner_url' => null,
            'imagen_thumbnail_url' => null,
            'orden' => $this->faker->numberBetween(1, 100),
            'activo' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
