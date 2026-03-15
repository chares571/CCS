<?php

namespace Tests\Feature;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordRoleDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_detects_role_and_redirects_to_correct_flow(): void
    {
        Mail::fake();

        $users = [
            ['role' => 'super_admin', 'email' => 'superadmin@example.com', 'expected' => 'super_admin', 'verifyRoute' => 'password.verify.super-admin'],
            ['role' => 'admin', 'email' => 'admin@example.com', 'expected' => 'admin', 'verifyRoute' => 'password.verify.admin'],
            ['role' => 'parent', 'email' => 'parent@example.com', 'expected' => 'parent_student', 'verifyRoute' => 'password.verify.parent-student'],
        ];

        foreach ($users as $index => $data) {
            User::query()->create([
                'full_name' => 'User '.$index,
                'username' => 'user'.$index,
                'email' => $data['email'],
                'password' => Hash::make('Password123'),
                'role' => $data['role'],
                'is_active' => true,
                'force_password_change' => false,
            ]);

            $this->post(route('password.request-code'), [
                'email' => $data['email'],
            ])->assertRedirect();

            $reset = PasswordResetCode::query()
                ->where('email', $data['email'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->firstOrFail();
            $this->assertSame($data['expected'], $reset->requested_role);

            $this->get(route($data['verifyRoute'], ['id' => $reset->id]))->assertOk();
        }
    }
}
