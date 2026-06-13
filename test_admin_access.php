<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "Testing Admin Access...\n\n";

// Test 1: Non-admin user cannot access admin page
echo "Test 1: Non-admin access check...\n";
$nonAdmin = User::where('email', 'user@example.com')->first();
Auth::login($nonAdmin);
$adminPage = app(\Illuminate\Http\Request::class)->create('/admin');
try {
    $response = app('Illuminate\Routing\Router')->toRoute('/admin');
    echo "✗ Non-admin can access admin page (should be blocked)\n";
} catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    echo "✓ Non-admin correctly blocked from admin page\n";
}
Auth::logout();

// Test 2: Admin user can access admin page
echo "\nTest 2: Admin access check...\n";
$admin = User::where('email', 'admin@example.com')->first();
Auth::login($admin);
try {
    $response = app('Illuminate\Routing\Router')->toRoute('/admin');
    echo "✓ Admin can access admin page\n";
} catch (\Exception $e) {
    echo "✗ Admin cannot access admin page\n";
    echo "Error: " . $e->getMessage() . "\n";
}
Auth::logout();

// Test 3: User without authentication cannot access admin page
echo "\nTest 3: Unauthenticated access check...\n";
try {
    $response = app('Illuminate\Routing\Router')->toRoute('/admin');
    echo "✗ Unauthenticated user can access admin page (should be redirected)\n";
} catch (\Exception $e) {
    echo "✓ Unauthenticated user correctly blocked\n";
}

echo "\n========================================\n";
echo "All access tests completed!\n";
echo "========================================\n";