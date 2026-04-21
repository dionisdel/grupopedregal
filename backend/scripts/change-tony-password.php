<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'tony@local.com')->first();

if (!$user) {
    echo "Usuario no encontrado\n";
    exit(1);
}

$user->password = bcrypt('tony123');
$user->save();

echo "Contraseña actualizada para: " . $user->email . "\n";
