<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create super admin account
        User::firstOrCreate(
            ['email' => 'admin@mamtours.com'],
            [
                'name' => 'MAM Tours Admin',
                'email' => 'admin@mamtours.com',
                'password' => Hash::make('admin123456'), // Change this password!
                'role' => 'admin',
            ]
        );

        // Create a regular user for testing
        User::firstOrCreate(
            ['email' => 'user@mamtours.com'],
            [
                'name' => 'Test User',
                'email' => 'user@mamtours.com',
                'password' => Hash::make('user123456'),
                'role' => 'user',
            ]
        );
    }
}
