<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    /** @test */
    public function it_can_determine_if_user_is_admin()
    {
        $admin = User::factory()->admin()->make();
        $customer = User::factory()->customer()->make();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($customer->isAdmin());
    }

    /** @test */
    public function it_can_determine_if_user_is_customer()
    {
        $admin = User::factory()->admin()->make();
        $customer = User::factory()->customer()->make();

        $this->assertFalse($admin->isCustomer());
        $this->assertTrue($customer->isCustomer());
    }

    /** @test */
    public function it_can_check_if_account_is_locked()
    {
        $lockedUser = User::factory()->locked()->make();
        $normalUser = User::factory()->make();

        $this->assertTrue($lockedUser->isLocked());
        $this->assertFalse($normalUser->isLocked());
    }

    /** @test */
    public function it_can_lock_account_after_failed_attempts()
    {
        $user = User::factory()->create();

        $user->lockAccount(30);

        $this->assertTrue($user->fresh()->isLocked());
        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    /** @test */
    public function it_can_unlock_account()
    {
        $user = User::factory()->locked()->create();

        $user->unlockAccount();

        $this->assertFalse($user->fresh()->isLocked());
        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    /** @test */
    public function it_increments_failed_attempts()
    {
        $user = User::factory()->create(['failed_login_attempts' => 3]);

        $user->incrementFailedAttempts();

        $this->assertEquals(4, $user->fresh()->failed_login_attempts);
    }

    /** @test */
    public function it_locks_account_after_five_failed_attempts()
    {
        $user = User::factory()->create(['failed_login_attempts' => 4]);

        $user->incrementFailedAttempts();

        $this->assertTrue($user->fresh()->isLocked());
    }

    /** @test */
    public function it_can_reset_failed_attempts()
    {
        $user = User::factory()->create(['failed_login_attempts' => 3]);

        $user->resetFailedAttempts();

        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    /** @test */
    public function it_updates_login_info()
    {
        $user = User::factory()->create();
        $ip = '192.168.1.1';

        $user->updateLoginInfo($ip);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->last_login_at);
        $this->assertEquals($ip, $fresh->last_login_ip);
        $this->assertEquals(0, $fresh->failed_login_attempts);
    }

    /** @test */
    public function it_can_create_api_token()
    {
        $user = User::factory()->create();

        $token = $user->createApiToken('test-token');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /** @test */
    public function it_can_revoke_all_tokens()
    {
        $user = User::factory()->create();
        $user->createApiToken('token1');
        $user->createApiToken('token2');

        $this->assertCount(2, $user->tokens);

        $user->revokeAllTokens();

        $this->assertCount(0, $user->fresh()->tokens);
    }

    /** @test */
    public function it_hides_sensitive_fields()
    {
        $user = User::factory()->make();

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
    }
}