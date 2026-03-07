<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['codigo' => 'UNI', 'nombre' => 'Unidad', 'abreviatura' => 'ud', 'tipo' => 'cantidad'],
            ['codigo' => 'M2', 'nombre' => 'Metro cuadrado', 'abreviatura' => 'm²', 'tipo' => 'superficie'],
            ['codigo' => 'ML', 'nombre' => 'Metro lineal', 'abreviatura' => 'ml', 'tipo' => 'longitud'],
            ['codigo' => 'PAL', 'nombre' => 'Palet', 'abreviatura' => 'pal', 'tipo' => 'cantidad'],
            ['codigo' => 'KG', 'nombre' => 'Kilogramo', 'abreviatura' => 'kg', 'tipo' => 'peso'],
            ['codigo' => 'LT', 'nombre' => 'Litro', 'abreviatura' => 'l', 'tipo' => 'volumen'],
        ];

        foreach ($units as $unit) {
            \App\Models\Unit::create($unit);
        }
    }
}
