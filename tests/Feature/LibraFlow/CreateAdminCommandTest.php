<?php

namespace Tests\Feature\LibraFlow;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_an_active_admin_with_a_secure_password(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Full name', 'Production Administrator')
            ->expectsQuestion('Username', 'production-admin')
            ->expectsQuestion('Email address', 'admin@example.com')
            ->expectsQuestion('Password', 'Strong-Password-2026!')
            ->expectsQuestion('Confirm password', 'Strong-Password-2026!')
            ->expectsOutputToContain('Administrator created successfully.')
            ->assertSuccessful();

        $admin = User::query()->where('username', 'production-admin')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('Strong-Password-2026!', $admin->password));
    }

    public function test_command_rejects_a_weak_password_without_creating_a_user(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Full name', 'Production Administrator')
            ->expectsQuestion('Username', 'production-admin')
            ->expectsQuestion('Email address', 'admin@example.com')
            ->expectsQuestion('Password', 'password')
            ->expectsQuestion('Confirm password', 'password')
            ->expectsOutputToContain('The password field must be at least 12 characters.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
