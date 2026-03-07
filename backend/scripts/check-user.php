<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::first();

if ($user) {
    echo "Usuario encontrado:\n";
    echo "Email: " . $user->email . "\n";
    echo "Nombre: " . $user->name . "\n";
} else {
    echo "No hay usuarios en la base de datos.\n";
    echo "Creando usuario admin...\n";
    
    $newUser = \App\Models\User::create([
        'name' => 'Admin',
        'email' => 'admin@tony.local',
        'password' => bcrypt('password'),
    ]);
    
    echo "Usuario creado:\n";
    echo "Email: admin@tony.local\n";
    echo "Password: password\n";
}
