<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            // Tarifas
            ['name' => 'Ver Tarifas', 'slug' => 'view-tarifas', 'module' => 'tarifas'],
            ['name' => 'Exportar Tarifas', 'slug' => 'export-tarifas', 'module' => 'tarifas'],
            ['name' => 'Editar Precios', 'slug' => 'edit-prices', 'module' => 'tarifas'],
            
            // Productos
            ['name' => 'Ver Productos', 'slug' => 'view-products', 'module' => 'productos'],
            ['name' => 'Crear Productos', 'slug' => 'create-products', 'module' => 'productos'],
            ['name' => 'Editar Productos', 'slug' => 'edit-products', 'module' => 'productos'],
            ['name' => 'Eliminar Productos', 'slug' => 'delete-products', 'module' => 'productos'],
            
            // Clientes
            ['name' => 'Ver Clientes', 'slug' => 'view-customers', 'module' => 'clientes'],
            ['name' => 'Crear Clientes', 'slug' => 'create-customers', 'module' => 'clientes'],
            ['name' => 'Editar Clientes', 'slug' => 'edit-customers', 'module' => 'clientes'],
            
            // Admin
            ['name' => 'Acceso Admin Panel', 'slug' => 'access-admin', 'module' => 'admin'],
            ['name' => 'Gestionar Usuarios', 'slug' => 'manage-users', 'module' => 'admin'],
            ['name' => 'Gestionar Roles', 'slug' => 'manage-roles', 'module' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Crear roles
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrador',
                'description' => 'Acceso completo al sistema',
                'activo' => true,
            ]
        );

        $comercialRole = Role::firstOrCreate(
            ['slug' => 'comercial'],
            [
                'name' => 'Comercial',
                'description' => 'Acceso a tarifas y clientes',
                'activo' => true,
            ]
        );

        $publicoRole = Role::firstOrCreate(
            ['slug' => 'publico'],
            [
                'name' => 'Público',
                'description' => 'Acceso solo a tarifas públicas',
                'activo' => true,
            ]
        );

        // Asignar todos los permisos al admin
        $adminRole->permissions()->sync(Permission::all());

        // Asignar permisos al comercial
        $comercialRole->permissions()->sync(
            Permission::whereIn('slug', [
                'view-tarifas',
                'export-tarifas',
                'view-products',
                'view-customers',
                'create-customers',
                'edit-customers',
            ])->pluck('id')
        );

        // Asignar permisos al público
        $publicoRole->permissions()->sync(
            Permission::whereIn('slug', [
                'view-tarifas',
            ])->pluck('id')
        );

        // Actualizar usuario admin existente
        $adminUser = User::where('email', 'admin@tony.local')->first();
        if ($adminUser) {
            $adminUser->update(['role_id' => $adminRole->id]);
        }

        $this->command->info('✅ Roles y permisos creados exitosamente');
        $this->command->info('   - Admin: Todos los permisos');
        $this->command->info('   - Comercial: Tarifas, productos y clientes');
        $this->command->info('   - Público: Solo ver tarifas');
    }
}
