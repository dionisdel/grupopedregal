<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            ['nombre' => 'Contado', 'dias_plazo' => 0],
            ['nombre' => '30 días', 'dias_plazo' => 30],
            ['nombre' => '60 días', 'dias_plazo' => 60],
            ['nombre' => '90 días', 'dias_plazo' => 90],
            ['nombre' => '120 días', 'dias_plazo' => 120],
        ];

        foreach ($methods as $method) {
            \App\Models\PaymentMethod::create($method);
        }
    }
}
