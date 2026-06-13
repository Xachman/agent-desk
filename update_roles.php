<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "Updating user roles...\n\n";

// Update admin user
$admin = User::where('email', 'admin@example.com')->first();
if ($admin) {
    $admin->update(['role' => 'admin']);
    echo "✓ Admin user updated: {$admin->email} (role: {$admin->role})\n";
} else {
    echo "✗ Admin user not found\n";
}

// Update regular user
$regular = User::where('email', 'user@example.com')->first();
if ($regular) {
    $regular->update(['role' => 'user']);
    echo "✓ Regular user updated: {$regular->email} (role: {$regular->role})\n";
} else {
    echo "✗ Regular user not found\n";
}

echo "\nSetup complete!\n";