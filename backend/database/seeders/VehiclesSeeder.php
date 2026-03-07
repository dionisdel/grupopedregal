<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehiclesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            [
                'nombre' => 'Camión 1000kg',
                'tipo' => 'camion',
                'capacidad_kg' => 1000,
                'capacidad_m3' => 10,
                'coste_km' => 0.50,
                'coste_fijo_salida' => 30,
            ],
            [
                'nombre' => 'Camión grúa 8000kg',
                'tipo' => 'camion_grua',
                'capacidad_kg' => 8000,
                'capacidad_m3' => 40,
                'coste_km' => 1.20,
                'coste_fijo_salida' => 80,
            ],
            [
                'nombre' => 'Tráiler 25000kg',
                'tipo' => 'trailer',
                'capacidad_kg' => 25000,
                'capacidad_m3' => 90,
                'coste_km' => 2.00,
                'coste_fijo_salida' => 150,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            \App\Models\Vehicle::create($vehicle);
        }
    }
}
