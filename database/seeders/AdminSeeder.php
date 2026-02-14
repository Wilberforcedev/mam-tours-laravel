<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Create admin account
        User::firstOrCreate(
            ['email' => 'admin@mamtours.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123456'),
                'phone' => '+256755943973',
                'role' => 'admin',
                'email_notifications' => true,
                'sms_notifications' => false,
            ]
        );

        // Create sample customer accounts
        User::firstOrCreate(
            ['email' => 'customer@mamtours.com'],
            [
                'name' => 'Wilberforce Kandahura',
                'password' => Hash::make('customer123456'),
                'phone' => '+256700000000',
                'role' => 'customer',
                'email_notifications' => true,
                'sms_notifications' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'jane@mamtours.com'],
            [
                'name' => 'Jane Smith',
                'password' => Hash::make('customer123456'),
                'phone' => '+256701111111',
                'role' => 'customer',
                'email_notifications' => true,
                'sms_notifications' => false,
            ]
        );

        echo "✅ Admin and sample customer accounts created!\n";
        echo "Admin Email: admin@mamtours.com\n";
        echo "Admin Password: admin123456\n";
        echo "\nCustomer Email: customer@mamtours.com\n";
        echo "Customer Password: customer123456\n";
    }
}
