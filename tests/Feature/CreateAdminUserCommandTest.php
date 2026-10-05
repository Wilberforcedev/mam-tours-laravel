<?php

namespace Tests\\Feature;

use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Hash;
use Tests\\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_using_a_hidden_confirmed_password(): void
    {
        $password = 'a-unique-test-password';

        $this->artisan('admin:create', [
            'name' => 'MAM Tours Admin',
            'email' => 'admin@example.test',
        ])
            ->expectsQuestion('Admin password (minimum 12 characters):', $password)
            ->expectsQuestion('Confirm admin password:', $password)
            ->expectsOutput('Admin user created successfully!')
            ->assertExitCode(0);

        $admin = User::where('email', 'admin@example.test')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check($password, $admin->password));
    }

    public function test_it_rejects_a_password_shorter_than_twelve_characters(): void
    {
        $this->artisan('admin:create', [
            'name' => 'MAM Tours Admin',
            'email' => 'admin@example.test',
        ])
            ->expectsQuestion('Admin password (minimum 12 characters):', 'too-short')
            ->expectsOutput('Password must contain at least 12 characters.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }
}
