<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create or update admin user
$email = 'admin@example.com';
$password = 'admin';

$user = User::firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Admin User',
        'email' => $email,
        'password' => Hash::make($password),
        'role' => 'admin',
    ]
);

if ($user->wasRecentlyCreated) {
    echo "Admin user created successfully!\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
} else {
    echo "Admin user already exists.\n";
    echo "Email: {$email}\n";
}

// Create a regular user
$regularEmail = 'user@example.com';
$regularPassword = 'user';

$regularUser = User::firstOrCreate(
    ['email' => $regularEmail],
    [
        'name' => 'Regular User',
        'email' => $regularEmail,
        'password' => Hash::make($regularPassword),
        'role' => 'user',
    ]
);

if ($regularUser->wasRecentlyCreated) {
    echo "Regular user created successfully!\n";
    echo "Email: {$regularEmail}\n";
    echo "Password: {$regularPassword}\n";
} else {
    echo "Regular user already exists.\n";
    echo "Email: {$regularEmail}\n";
}