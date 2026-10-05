<?php

namespace App\\Console\\Commands;

use App\\Models\\User;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {name} {email}';
    protected $description = 'Create a new admin user account with a hidden password prompt';

    public function handle()
    {
        $name = trim($this->argument('name'));
        $email = strtolower(trim($this->argument('email')));

        if ($name === '') {
            $this->error('A name is required.');
            return 1;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');
            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        $password = $this->secret('Admin password (minimum 12 characters):', false);
        if (!is_string($password) || strlen($password) < 12) {
            $this->error('Password must contain at least 12 characters.');
            return 1;
        }

        $confirmation = $this->secret('Confirm admin password:', false);
        if (!is_string($confirmation) || !hash_equals($password, $confirmation)) {
            $this->error('The passwords did not match.');
            return 1;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->info('Admin user created successfully!');
        $this->line("Name: {$admin->name}");
        $this->line("Email: {$admin->email}");
        $this->line("Role: {$admin->role}");

        return 0;
    }
}
