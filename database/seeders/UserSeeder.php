<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@mamtours.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Regular user
        User::create([
            'name' => 'Wilberforce Kandahura',
            'email' => 'user@mamtours.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
    }
}
