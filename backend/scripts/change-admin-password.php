<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('email', 'admin@tony.local')->first();

if ($user) {
    $user->password = bcrypt('Admin123');
    $user->save();
    
    echo "✅ Contraseña actualizada exitosamente\n";
    echo "Email: admin@tony.local\n";
    echo "Nueva contraseña: Admin123\n";
} else {
    echo "❌ Usuario no encontrado\n";
}
