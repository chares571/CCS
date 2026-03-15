<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountSecuritySetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_local_email_is_forced_to_security_setup_on_login(): void
    {
        User::query()->create([
            'full_name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@ccs.local',
            'password' => Hash::make('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->post('/login', [
            'email' => 'admin@ccs.local',
            'password' => 'Password123',
        ])->assertRedirect(route('account.security.setup.form'));
    }

    public function test_admin_can_complete_security_setup_and_access_dashboard(): void
    {
        $user = User::query()->create([
            'full_name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@ccs.local',
            'password' => Hash::make('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => true,
        ]);

        $this->post('/login', [
            'email' => 'admin@ccs.local',
            'password' => 'Password123',
        ])->assertRedirect(route('account.security.setup.form'));

        $this->post(route('account.security.setup.update'), [
            'email' => 'admin.real@example.com',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('admin.dashboard'));

        $user->refresh();
        $this->assertSame('admin.real@example.com', $user->email);
        $this->assertTrue(Hash::check('NewPassword123', (string) $user->password));
        $this->assertFalse((bool) $user->force_password_change);
    }
}

