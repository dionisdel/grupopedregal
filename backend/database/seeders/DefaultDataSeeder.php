<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultDataSeeder extends Seeder
{
    /**
     * Seed default superadmin user and default warehouse.
     * Requirements: 12.5, 1.1
     */
    public function run(): void
    {
        // --- Default superadmin user ---
        $superadminRole = Role::where('slug', 'superadmin')->first();

        User::firstOrCreate(
            ['email' => 'admin@grupopedregal.es'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'role_id' => $superadminRole?->id,
                'estado' => 'activo',
            ]
        );

        User::firstOrCreate(
            ['email' => 'tony@local.com'],
            [
                'name' => 'Tony',
                'password' => bcrypt('tony123'),
                'role_id' => $superadminRole?->id,
                'estado' => 'activo',
            ]
        );

        // --- Default warehouse ---
        DB::table('warehouses')->insertOrIgnore([
            'name' => 'Almacén Principal',
            'slug' => 'almacen-principal',
            'address' => 'Grupo Pedregal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✅ Usuario superadmin y almacén por defecto creados');
    }
}
