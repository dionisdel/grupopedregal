<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles, permissions, and role_permission associations.
     * Requirements: 1.1, 1.9
     */
    public function run(): void
    {
        // --- Permissions (module.action format) ---
        $permissionsData = [
            // Products
            ['name' => 'Ver Productos', 'slug' => 'products.view', 'description' => 'Ver listado y detalle de productos', 'module' => 'products'],
            ['name' => 'Crear Productos', 'slug' => 'products.create', 'description' => 'Crear nuevos productos', 'module' => 'products'],
            ['name' => 'Editar Productos', 'slug' => 'products.edit', 'description' => 'Editar productos existentes', 'module' => 'products'],
            ['name' => 'Eliminar Productos', 'slug' => 'products.delete', 'description' => 'Eliminar productos', 'module' => 'products'],
            ['name' => 'Importar Productos', 'slug' => 'products.import', 'description' => 'Importar productos desde Excel', 'module' => 'products'],
            // Categories
            ['name' => 'Ver Categorías', 'slug' => 'categories.view', 'description' => 'Ver árbol de categorías', 'module' => 'categories'],
            ['name' => 'Crear Categorías', 'slug' => 'categories.create', 'description' => 'Crear nuevas categorías', 'module' => 'categories'],
            ['name' => 'Editar Categorías', 'slug' => 'categories.edit', 'description' => 'Editar categorías existentes', 'module' => 'categories'],
            ['name' => 'Eliminar Categorías', 'slug' => 'categories.delete', 'description' => 'Eliminar categorías', 'module' => 'categories'],
            ['name' => 'Reordenar Categorías', 'slug' => 'categories.reorder', 'description' => 'Reordenar categorías drag-and-drop', 'module' => 'categories'],
            // Cart
            ['name' => 'Ver Carrito', 'slug' => 'cart.view', 'description' => 'Ver carrito de compra', 'module' => 'cart'],
            ['name' => 'Gestionar Carrito', 'slug' => 'cart.manage', 'description' => 'Añadir/eliminar items del carrito', 'module' => 'cart'],
            // Users
            ['name' => 'Ver Usuarios', 'slug' => 'users.view', 'description' => 'Ver listado de usuarios', 'module' => 'users'],
            ['name' => 'Crear Usuarios', 'slug' => 'users.create', 'description' => 'Crear nuevos usuarios', 'module' => 'users'],
            ['name' => 'Editar Usuarios', 'slug' => 'users.edit', 'description' => 'Editar usuarios existentes', 'module' => 'users'],
            ['name' => 'Eliminar Usuarios', 'slug' => 'users.delete', 'description' => 'Eliminar usuarios', 'module' => 'users'],
            // Roles
            ['name' => 'Ver Roles', 'slug' => 'roles.view', 'description' => 'Ver listado de roles', 'module' => 'roles'],
            ['name' => 'Editar Roles', 'slug' => 'roles.edit', 'description' => 'Editar roles y permisos', 'module' => 'roles'],
        ];

        $permissions = [];
        foreach ($permissionsData as $data) {
            $permissions[$data['slug']] = Permission::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        // --- Roles ---
        $superadmin = Role::firstOrCreate(
            ['slug' => 'superadmin'],
            ['name' => 'Super Administrador', 'description' => 'Acceso total al sistema', 'activo' => true]
        );

        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrador', 'description' => 'Todo excepto gestión de usuarios y roles', 'activo' => true]
        );

        $cliente = Role::firstOrCreate(
            ['slug' => 'cliente'],
            ['name' => 'Cliente', 'description' => 'Carrito, compras y catálogo', 'activo' => true]
        );

        $publico = Role::firstOrCreate(
            ['slug' => 'publico'],
            ['name' => 'Público', 'description' => 'Catálogo de solo lectura', 'activo' => true]
        );

        // --- Role-Permission Mapping ---

        // superadmin: ALL permissions
        $superadmin->permissions()->sync(Permission::pluck('id'));

        // admin: ALL except users.* and roles.*
        $adminPermissions = Permission::whereNotIn('module', ['users', 'roles'])->pluck('id');
        $admin->permissions()->sync($adminPermissions);

        // cliente: cart.view, cart.manage, products.view, categories.view
        $clientePermissions = Permission::whereIn('slug', [
            'cart.view', 'cart.manage', 'products.view', 'categories.view',
        ])->pluck('id');
        $cliente->permissions()->sync($clientePermissions);

        // publico: products.view, categories.view
        $publicoPermissions = Permission::whereIn('slug', [
            'products.view', 'categories.view',
        ])->pluck('id');
        $publico->permissions()->sync($publicoPermissions);

        $this->command->info('✅ Roles y permisos v2 creados exitosamente');
    }
}
