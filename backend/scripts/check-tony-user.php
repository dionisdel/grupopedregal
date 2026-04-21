<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'tony@local.com')->first();

if (!$user) {
    echo "Usuario NO encontrado\n";
    exit(1);
}

echo "Usuario encontrado: " . $user->email . "\n";
echo "Hash actual: " . $user->password . "\n";
echo "Verifica 'tony123': " . (password_verify('tony123', $user->password) ? 'OK' : 'FALLO') . "\n";
echo "Estado activo: " . ($user->is_active ?? 'N/A') . "\n";
echo "Email verified: " . ($user->email_verified_at ?? 'NULL') . "\n";
