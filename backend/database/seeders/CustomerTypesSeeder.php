<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['codigo' => 'CAMION_VIP', 'nombre' => 'CAMION VIP', 'descripcion' => 'Clientes VIP con descuento máximo', 'orden' => 1],
            ['codigo' => 'CAMION', 'nombre' => 'CAMION', 'descripcion' => 'Clientes estándar con descuento medio', 'orden' => 2],
            ['codigo' => 'OFERTA', 'nombre' => 'OFERTA', 'descripcion' => 'Precios especiales de oferta', 'orden' => 3],
            ['codigo' => 'VIP', 'nombre' => 'VIP', 'descripcion' => 'Clientes VIP', 'orden' => 4],
            ['codigo' => 'EMPRESAS', 'nombre' => 'EMPRESAS', 'descripcion' => 'Empresas y constructoras', 'orden' => 5],
            ['codigo' => 'EMPRESAS_A', 'nombre' => 'EMPRESAS A', 'descripcion' => 'Empresas categoría A', 'orden' => 6],
        ];

        foreach ($types as $type) {
            \App\Models\CustomerType::create($type);
        }
    }
}
