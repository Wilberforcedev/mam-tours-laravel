<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    /** @test */
    public function users_can_login_via_api()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'role']
        ]);

        $this->assertNotNull($response->json('token'));
    }

    /** @test */
    public function api_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function locked_users_cannot_login_via_api()
    {
        $user = User::factory()->locked()->create([
            'email' => 'locked@example.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'locked@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $response->assertStatus(423);
        $response->assertJson(['message' => 'Account is temporarily locked']);
    }

    /** @test */
    public function users_can_access_protected_endpoints_with_token()
    {
        $user = User::factory()->create();
        $token = $user->createApiToken();

        $response = $this->getJson('/api/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }

    /** @test */
    public function protected_endpoints_require_authentication()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function users_can_logout_via_api()
    {
        $user = User::factory()->create();
        $token = $user->createApiToken();

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logged out successfully']);

        // Token should be revoked
        $response = $this->getJson('/api/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function api_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

        $response = $this->postJson('/api/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}