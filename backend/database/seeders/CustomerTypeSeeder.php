<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Seed customer types with discount_field mapping.
     * Requirements: 11.1, 15.7
     */
    public function run(): void
    {
        $types = [
            ['nombre' => 'CAMION VIP', 'slug' => 'camion-vip', 'discount_field' => 'desc_camion_vip', 'activo' => true],
            ['nombre' => 'CAMION', 'slug' => 'camion', 'discount_field' => 'desc_camion', 'activo' => true],
            ['nombre' => 'OFERTA', 'slug' => 'oferta', 'discount_field' => 'desc_oferta', 'activo' => true],
            ['nombre' => 'VIP', 'slug' => 'vip', 'discount_field' => 'desc_vip', 'activo' => true],
            ['nombre' => 'EMPRESAS', 'slug' => 'empresas', 'discount_field' => 'desc_empresas', 'activo' => true],
            ['nombre' => 'EMPRESAS A', 'slug' => 'empresas-a', 'discount_field' => 'desc_empresas_a', 'activo' => true],
        ];

        foreach ($types as $type) {
            CustomerType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }

        $this->command->info('✅ Tipos de cliente creados exitosamente');
    }
}
