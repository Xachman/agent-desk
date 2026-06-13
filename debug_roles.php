<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "Checking user roles...\n\n";

$admin = User::where('email', 'admin@example.com')->first();
$regular = User::where('email', 'user@example.com')->first();

if ($admin) {
    echo "Admin user: {$admin->email}\n";
    echo "Role: '{$admin->role}'\n";
    echo "Attributes: " . json_encode($admin->getAttributes()) . "\n";
}

if ($regular) {
    echo "\nRegular user: {$regular->email}\n";
    echo "Role: '{$regular->role}'\n";
    echo "Attributes: " . json_encode($regular->getAttributes()) . "\n";
}

// Try manual update
if ($admin) {
    $admin->role = 'admin';
    $admin->save();
    echo "\n✓ Manual update successful for admin user\n";
}

if ($regular) {
    $regular->role = 'user';
    $regular->save();
    echo "✓ Manual update successful for regular user\n";
}

// Verify
$admin = User::where('email', 'admin@example.com')->first();
$regular = User::where('email', 'user@example.com')->first();

echo "\n\nFinal verification:\n";
echo "Admin role: '{$admin->role}'\n";
echo "Regular role: '{$regular->role}'\n";