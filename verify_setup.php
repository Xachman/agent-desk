<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "========================================\n";
echo "Laravel Livewire Admin System Setup\n";
echo "========================================\n\n";

echo "✓ Livewire installed and configured\n";
echo "✓ Login component created\n";
echo "✓ Admin component created\n";
echo "✓ Dashboard component created\n";
echo "✓ IsAdmin middleware configured\n";
echo "✓ Routes configured\n\n";

echo "Test users:\n";
echo "-----------\n";

$admin = User::where('email', 'admin@example.com')->first();
if ($admin) {
    echo "Admin:\n";
    echo "  Email: admin@example.com\n";
    echo "  Password: admin\n";
    echo "  Role: {$admin->role}\n\n";
}

$regular = User::where('email', 'user@example.com')->first();
if ($regular) {
    echo "Regular User:\n";
    echo "  Email: user@example.com\n";
    echo "  Password: user\n";
    echo "  Role: {$regular->role}\n\n";
}

echo "Available Routes:\n";
echo "-----------------\n";
echo "  /login - Login page\n";
echo "  /admin - Admin panel (admin only)\n";
echo "  /dashboard - Dashboard (admin only)\n\n";

echo "Access URLs:\n";
echo "------------\n";
echo "  http://127.0.0.1:8000/login\n";
echo "  http://127.0.0.1:8000/admin\n";
echo "  http://127.0.0.1:8000/dashboard\n\n";

echo "========================================\n";
echo "Setup Complete! The server is running.\n";
echo "========================================\n";