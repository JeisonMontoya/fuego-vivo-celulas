<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::first();
if ($user) {
    // Delete previous tokens for this name to keep it clean
    $user->tokens()->where('name', 'Consolidacion-API')->delete();
    $token = $user->createToken('Consolidacion-API')->plainTextToken;
    echo "TOKEN=" . $token . "\n";
} else {
    echo "No users found in celulas.\n";
}
