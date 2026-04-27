<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $descripcion = $this->faker->unique()->words(3, true);

        return [
            'codigo_articulo' => $this->faker->unique()->bothify('ART-#####'),
            'descripcion' => $descripcion,
            'slug' => Str::slug($descripcion) . '-' . $this->faker->unique()->randomNumber(4),
            'categoria_id' => Category::factory(),
            'proveedor_id' => Supplier::factory(),
            'marca_id' => Brand::factory(),
            // Editable prices
            'pvp_proveedor' => $this->faker->randomFloat(4, 1, 500),
            'desc_prov_1' => $this->faker->randomFloat(2, 0, 50),
            'coste_transporte' => $this->faker->randomFloat(4, 0, 20),
            'desc_camion_vip' => $this->faker->randomFloat(2, 0, 50),
            'desc_camion' => $this->faker->randomFloat(2, 0, 50),
            'desc_oferta' => $this->faker->randomFloat(2, 0, 50),
            'desc_vip' => $this->faker->randomFloat(2, 0, 50),
            'desc_empresas' => $this->faker->randomFloat(2, 0, 50),
            'desc_empresas_a' => $this->faker->randomFloat(2, 0, 50),
            'iva_porcentaje' => 21,
            // State
            'estado_publicado' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['estado_publicado' => false]);
    }

    public function withMetros(float $metros = 1.5): static
    {
        return $this->state(fn () => ['metros_articulo' => $metros]);
    }
}
