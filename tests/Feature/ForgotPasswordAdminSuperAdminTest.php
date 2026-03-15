<?php

namespace Tests\Feature;

use App\Mail\PasswordResetVerificationCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordAdminSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_request_verify_and_reset_password_via_email_code(): void
    {
        Mail::fake();

        $user = User::query()->create([
            'full_name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->post(route('password.request-code.admin'), [
            'email' => 'admin@example.com',
        ])->assertRedirect();

        $reset = PasswordResetCode::query()->firstOrFail();
        $this->assertSame($user->id, $reset->user_id);
        $this->assertSame('admin', $reset->requested_role);

        $code = null;
        Mail::assertSent(PasswordResetVerificationCodeMail::class, function (PasswordResetVerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;
            $this->assertSame('Account recovery for the Administrator.', $mail->accountLabel);
            return true;
        });

        $this->post(route('password.verify.submit.admin', ['id' => $reset->id]), [
            'code' => $code,
        ])->assertRedirect(route('password.reset.admin', ['id' => $reset->id]));

        $this->post(route('password.reset.submit.admin', ['id' => $reset->id]), [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', (string) $user->password));
    }

    public function test_super_admin_can_request_verify_and_reset_password_via_email_code(): void
    {
        Mail::fake();

        $user = User::query()->create([
            'full_name' => 'Super Admin User',
            'username' => 'superadminuser',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'super_admin',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->post(route('password.request-code.super-admin'), [
            'email' => 'superadmin@example.com',
        ])->assertRedirect();

        $reset = PasswordResetCode::query()->firstOrFail();
        $this->assertSame($user->id, $reset->user_id);
        $this->assertSame('super_admin', $reset->requested_role);

        $code = null;
        Mail::assertSent(PasswordResetVerificationCodeMail::class, function (PasswordResetVerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;
            $this->assertSame('Account recovery for the Super Administrator.', $mail->accountLabel);
            return true;
        });

        $this->post(route('password.verify.submit.super-admin', ['id' => $reset->id]), [
            'code' => $code,
        ])->assertRedirect(route('password.reset.super-admin', ['id' => $reset->id]));

        $this->post(route('password.reset.submit.super-admin', ['id' => $reset->id]), [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', (string) $user->password));
    }
}

