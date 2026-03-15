<?php

namespace Tests\Feature;

use App\Mail\PasswordResetVerificationCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordParentStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_request_verify_and_reset_password_via_email_code(): void
    {
        Mail::fake();

        $user = User::query()->create([
            'full_name' => 'Parent User',
            'username' => 'parentuser',
            'email' => 'parent@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'parent',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $response = $this->post(route('password.request-code.parent-student'), [
            'email' => 'parent@example.com',
        ]);

        $reset = PasswordResetCode::query()->first();
        $this->assertNotNull($reset);
        $this->assertSame($user->id, $reset->user_id);

        $response->assertRedirect(route('password.verify.parent-student', ['id' => $reset->id]));

        $code = null;
        Mail::assertSent(PasswordResetVerificationCodeMail::class, function (PasswordResetVerificationCodeMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $this->assertNotNull($code);

        $this->post(route('password.verify.submit.parent-student', ['id' => $reset->id]), [
            'code' => $code,
        ])->assertRedirect(route('password.reset.parent-student', ['id' => $reset->id]));

        $this->post(route('password.reset.submit.parent-student', ['id' => $reset->id]), [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', (string) $user->password));

        $reset->refresh();
        $this->assertNotNull($reset->reset_at);
    }

    public function test_unregistered_email_does_not_send_mail_and_cannot_reset(): void
    {
        Mail::fake();

        $response = $this->post(route('password.request-code.parent-student'), [
            'email' => 'unknown@example.com',
        ]);

        $reset = PasswordResetCode::query()->first();
        $this->assertNotNull($reset);
        $this->assertNull($reset->user_id);

        $response->assertRedirect(route('password.verify.parent-student', ['id' => $reset->id]));

        Mail::assertNothingSent();

        $this->post(route('password.verify.submit.parent-student', ['id' => $reset->id]), [
            'code' => '123456',
        ])->assertSessionHasErrors('code');
    }

    public function test_verification_attempts_are_limited(): void
    {
        Mail::fake();

        User::query()->create([
            'full_name' => 'Student User',
            'username' => 'studentuser',
            'email' => 'student@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'student',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->post(route('password.request-code.parent-student'), [
            'email' => 'student@example.com',
        ]);

        $reset = PasswordResetCode::query()->firstOrFail();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.verify.submit.parent-student', ['id' => $reset->id]), [
                'code' => '000000',
            ])->assertSessionHasErrors('code');
        }

        $reset->refresh();
        $this->assertNotNull($reset->locked_at);

        $this->post(route('password.verify.submit.parent-student', ['id' => $reset->id]), [
            'code' => '000000',
        ])->assertSessionHasErrors('code');
    }

    public function test_expired_code_is_rejected(): void
    {
        Mail::fake();

        User::query()->create([
            'full_name' => 'Parent User',
            'username' => 'parentuser',
            'email' => 'parent@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'parent',
            'is_active' => true,
            'force_password_change' => false,
        ]);

        $this->post(route('password.request-code.parent-student'), [
            'email' => 'parent@example.com',
        ]);

        $reset = PasswordResetCode::query()->firstOrFail();
        $reset->forceFill([
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ])->save();

        $this->post(route('password.verify.submit.parent-student', ['id' => $reset->id]), [
            'code' => '123456',
        ])->assertSessionHasErrors('code');
    }
}

