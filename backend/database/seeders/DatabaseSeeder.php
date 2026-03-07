<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeders de datos maestros
        $this->call([
            UnitsSeeder::class,
            PaymentMethodsSeeder::class,
            CustomerTypesSeeder::class,
            VehiclesSeeder::class,
        ]);

        // Usuario de prueba
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@tony.local',
            'password' => bcrypt('password'),
        ]);
    }
}
