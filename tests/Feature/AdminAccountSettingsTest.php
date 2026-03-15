<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_email_and_password_in_account_settings(): void
    {
        $user = User::query()->create([
            'full_name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->actingAs($user)->get(route('admin.settings.index'))->assertOk();

        $this->actingAs($user)->post(route('admin.settings.update'), [
            'email' => 'admin2@example.com',
            'current_password' => 'Password123',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('admin2@example.com', $user->email);
        $this->assertTrue(Hash::check('NewPassword123', (string) $user->password));
    }
}

