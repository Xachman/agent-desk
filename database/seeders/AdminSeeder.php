<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin-user',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        // Create regular user
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'username' => 'regular-user',
                'email' => 'user@example.com',
                'password' => Hash::make('user'),
                'role' => 'user',
            ]
        );

        // Ensure personal groups exist (in case users already existed)
        $admin->personalGroup()->firstOrCreate(
            ['owner_id' => $admin->id],
            [
                'name' => $admin->name,
                'slug' => $admin->username,
                'description' => 'Personal workspace for ' . $admin->name,
            ]
        );

        $user->personalGroup()->firstOrCreate(
            ['owner_id' => $user->id],
            [
                'name' => $user->name,
                'slug' => $user->username,
                'description' => 'Personal workspace for ' . $user->name,
            ]
        );
    }
}
