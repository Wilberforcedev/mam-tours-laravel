<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /** @test */
    public function users_can_view_login_page()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /** @test */
    public function users_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/bookings');
    }

    /** @test */
    public function admin_users_are_redirected_to_admin_panel()
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin');
    }

    /** @test */
    public function users_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function locked_users_cannot_login()
    {
        $user = User::factory()->locked()->create([
            'email' => 'locked@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'locked@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function login_attempts_are_rate_limited()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    /** @test */
    public function users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /** @test */
    public function users_can_view_registration_page()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    /** @test */
    public function users_can_register_with_valid_data()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'phone' => '+256701234567',
            'terms' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'customer',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function registration_requires_valid_email()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function registration_requires_strong_password()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'terms' => true,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /** @test */
    public function registration_requires_terms_acceptance()
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'terms' => false,
        ]);

        $response->assertSessionHasErrors('terms');
        $this->assertGuest();
    }
}