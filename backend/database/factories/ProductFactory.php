<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $nombre = $this->faker->unique()->words(3, true);

        return [
            'sku' => $this->faker->unique()->bothify('SKU-#####'),
            'nombre' => $nombre,
            'descripcion' => $this->faker->sentence(),
            'slug' => Str::slug($nombre) . '-' . $this->faker->unique()->randomNumber(4),
            'categoria_id' => Category::factory(),
            'marca_id' => Brand::factory(),
            'proveedor_principal_id' => Supplier::factory(),
            'unidad_base_id' => Unit::factory(),
            'activo' => true,
            'visible_web' => true,
            'destacado' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['visible_web' => false]);
    }
}
