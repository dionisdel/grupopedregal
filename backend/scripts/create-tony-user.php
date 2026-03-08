<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

try {
    // Buscar el rol de admin
    $adminRole = Role::where('slug', 'admin')->first();
    
    if (!$adminRole) {
        echo "Error: Rol 'admin' no encontrado. Ejecuta el seeder primero.\n";
        exit(1);
    }

    // Verificar si el usuario ya existe
    $existingUser = User::where('email', 'tony@local.com')->first();
    
    if ($existingUser) {
        // Actualizar contraseña del usuario existente
        $existingUser->password = Hash::make('Demo1234');
        $existingUser->save();
        
        echo "✅ Usuario actualizado exitosamente:\n";
        echo "   Email: tony@local.com\n";
        echo "   Contraseña: Demo1234\n";
        echo "   Rol: {$existingUser->role->nombre}\n";
    } else {
        // Crear nuevo usuario
        $user = User::create([
            'name' => 'Tony',
            'email' => 'tony@local.com',
            'password' => Hash::make('Demo1234'),
            'role_id' => $adminRole->id,
        ]);

        echo "✅ Usuario creado exitosamente:\n";
        echo "   Email: tony@local.com\n";
        echo "   Contraseña: Demo1234\n";
        echo "   Rol: {$user->role->nombre}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
