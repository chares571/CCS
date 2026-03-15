<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetVerificationCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    private const CODE_TTL_MINUTES = 5;
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const RESET_WINDOW_MINUTES = 10;

    public function showParentStudentRequest(): View
    {
        return view('auth.forgot-password-parent-student');
    }

    public function showRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function requestCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        $user = User::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            if ((bool) config('ccs.password_reset.reveal_email_existence')) {
                return back()->withErrors([
                    'email' => 'No account found with that email address.',
                ])->withInput();
            }

            return back()->with('status', 'If the email is registered, a 6-digit verification code has been sent.');
        }

        return match ((string) $user->role) {
            'super_admin' => $this->requestSuperAdminCode($request),
            'admin' => $this->requestAdminCode($request),
            'parent', 'student' => $this->requestParentStudentCode($request),
            default => back()->withErrors([
                'email' => 'No account found with that email address.',
            ])->withInput(),
        };
    }

    public function showAdminRequest(): View
    {
        return view('auth.forgot-password-admin');
    }

    public function showSuperAdminRequest(): View
    {
        return view('auth.forgot-password-super-admin');
    }

    public function requestParentStudentCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        $ipKey = 'pwreset:request:ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            return back()->withErrors([
                'email' => 'Too many requests. Please try again in a few minutes.',
            ])->withInput();
        }
        RateLimiter::hit($ipKey, self::CODE_TTL_MINUTES * 60);

        $emailKey = 'pwreset:request:email:'.sha1($email);
        if (RateLimiter::tooManyAttempts($emailKey, 3)) {
            return back()->withErrors([
                'email' => 'Too many requests for this email. Please try again in a few minutes.',
            ])->withInput();
        }
        RateLimiter::hit($emailKey, self::CODE_TTL_MINUTES * 60);

        $now = CarbonImmutable::now();
        $expiresAt = $now->addMinutes(self::CODE_TTL_MINUTES);
        $code = $this->generateSixDigitCode();

        $user = User::query()
            ->whereIn('role', ['parent', 'student'])
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$user && (bool) config('ccs.password_reset.reveal_email_existence')) {
            return back()->withErrors([
                'email' => 'No account found with that email address.',
            ])->withInput();
        }

        $reset = PasswordResetCode::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user?->id,
            'email' => $email,
            'requested_role' => 'parent_student',
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'resend_count' => 0,
            'last_sent_at' => null,
            'locked_at' => null,
            'requested_ip' => $request->ip(),
        ]);

        if ($user) {
            try {
                $this->deliverResetCodeMail($email, $code, 'Account recovery for Parent/Student.', 'emails.password-reset-code');
                $reset->forceFill([
                    'last_sent_at' => $now,
                    'resend_count' => 1,
                ])->save();
            } catch (\Throwable $e) {
                Log::warning('Password reset code email send failed.', [
                    'email' => $email,
                    'exception' => $e->getMessage(),
                ]);

                if ((bool) config('ccs.password_reset.reveal_email_existence')) {
                    return back()->withErrors([
                        'email' => 'Unable to send verification code. Please check email (SMTP) settings.',
                    ])->withInput();
                }
            }
        }

        return redirect()
            ->route('password.verify.parent-student', ['id' => $reset->id])
            ->with('status', 'If the email is registered, a 6-digit verification code has been sent.');
    }

    public function requestAdminCode(Request $request): RedirectResponse
    {
        return $this->requestCodeForRoles(
            request: $request,
            roles: ['admin'],
            requestedRole: 'admin',
            verifyRoute: 'password.verify.admin',
            requestRoute: 'password.request.admin',
            accountLabel: 'Account recovery for the Administrator.',
        );
    }

    public function requestSuperAdminCode(Request $request): RedirectResponse
    {
        return $this->requestCodeForRoles(
            request: $request,
            roles: ['super_admin'],
            requestedRole: 'super_admin',
            verifyRoute: 'password.verify.super-admin',
            requestRoute: 'password.request.super-admin',
            accountLabel: 'Account recovery for the Super Administrator.',
        );
    }

    public function showParentStudentVerify(string $id): View
    {
        $reset = PasswordResetCode::query()->findOrFail($id);

        return view('auth.verify-password-parent-student', [
            'reset' => $reset,
            'maskedEmail' => $this->maskEmail((string) $reset->email),
        ]);
    }

    public function showAdminVerify(string $id): View
    {
        $reset = PasswordResetCode::query()->findOrFail($id);

        return view('auth.verify-password-admin', [
            'reset' => $reset,
            'maskedEmail' => $this->maskEmail((string) $reset->email),
        ]);
    }

    public function showSuperAdminVerify(string $id): View
    {
        $reset = PasswordResetCode::query()->findOrFail($id);

        return view('auth.verify-password-super-admin', [
            'reset' => $reset,
            'maskedEmail' => $this->maskEmail((string) $reset->email),
        ]);
    }

    public function verifyParentStudentCode(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'parent_student', 'password.request.parent-student');
        if ($roleCheck) {
            return $roleCheck;
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ($reset->reset_at) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['code' => 'This password reset request has already been used.']);
        }

        if ($reset->locked_at || $reset->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            return back()->withErrors([
                'code' => 'Too many verification attempts. Please request a new code.',
            ]);
        }

        if (!$reset->expires_at || CarbonImmutable::now()->greaterThan($reset->expires_at)) {
            return back()->withErrors([
                'code' => 'Verification code has expired. Please request a new code.',
            ]);
        }

        $reset->forceFill([
            'attempts' => (int) $reset->attempts + 1,
        ])->save();

        if (!$reset->user_id) {
            return back()->withErrors([
                'code' => 'Invalid or expired verification code.',
            ]);
        }

        if (!$reset->code_hash || !Hash::check((string) $validated['code'], (string) $reset->code_hash)) {
            if ($reset->attempts >= self::MAX_VERIFY_ATTEMPTS) {
                $reset->forceFill(['locked_at' => CarbonImmutable::now()])->save();
            }

            return back()->withErrors([
                'code' => 'Invalid or expired verification code.',
            ]);
        }

        $reset->forceFill([
            'verified_at' => CarbonImmutable::now(),
            'verified_ip' => $request->ip(),
        ])->save();

        return redirect()
            ->route('password.reset.parent-student', ['id' => $reset->id])
            ->with('status', 'Verification successful. You can now reset your password.');
    }

    public function verifyAdminCode(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'admin', 'password.request.admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->verifyCodeForRoles(
            request: $request,
            id: $id,
            roles: ['admin'],
            requestedRole: 'admin',
            requestRoute: 'password.request.admin',
            resetRoute: 'password.reset.admin',
        );
    }

    public function verifySuperAdminCode(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'super_admin', 'password.request.super-admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->verifyCodeForRoles(
            request: $request,
            id: $id,
            roles: ['super_admin'],
            requestedRole: 'super_admin',
            requestRoute: 'password.request.super-admin',
            resetRoute: 'password.reset.super-admin',
        );
    }

    public function resendParentStudentCode(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'parent_student', 'password.request.parent-student');
        if ($roleCheck) {
            return $roleCheck;
        }

        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ($reset->reset_at) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['email' => 'This password reset request has already been used.']);
        }

        $resendKey = 'pwreset:resend:'.$id.':ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            return back()->withErrors([
                'code' => 'Please wait a moment before requesting another code.',
            ]);
        }
        RateLimiter::hit($resendKey, 60);

        $now = CarbonImmutable::now();
        $expiresAt = $now->addMinutes(self::CODE_TTL_MINUTES);
        $code = $this->generateSixDigitCode();

        $reset->forceFill([
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'locked_at' => null,
            'verified_at' => null,
            'resend_count' => min(65535, (int) $reset->resend_count + 1),
            'last_sent_at' => $now,
        ])->save();

        $user = $reset->user_id
            ? User::query()->whereKey($reset->user_id)->whereIn('role', ['parent', 'student'])->where('is_active', true)->first()
            : null;

        if ($user) {
            try {
                $this->deliverResetCodeMail((string) $reset->email, $code, 'Account recovery for Parent/Student.', 'emails.password-reset-code');
            } catch (\Throwable $e) {
                Log::warning('Password reset resend email failed.', [
                    'email' => (string) $reset->email,
                    'exception' => $e->getMessage(),
                ]);

                if ((bool) config('ccs.password_reset.reveal_email_existence')) {
                    return back()->withErrors([
                        'code' => 'Unable to resend code right now. Please check email (SMTP) settings.',
                    ]);
                }
            }
        }

        return back()->with('status', 'If the email is registered, a new verification code has been sent.');
    }

    public function resendAdminCode(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'admin', 'password.request.admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->resendCodeForRoles(
            request: $request,
            id: $id,
            roles: ['admin'],
            accountLabel: 'Account recovery for the Administrator.',
            requestRoute: 'password.request.admin',
        );
    }

    public function resendSuperAdminCode(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'super_admin', 'password.request.super-admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->resendCodeForRoles(
            request: $request,
            id: $id,
            roles: ['super_admin'],
            accountLabel: 'Account recovery for the Super Administrator.',
            requestRoute: 'password.request.super-admin',
        );
    }

    public function showParentStudentReset(string $id): View|RedirectResponse
    {
        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ($reset->reset_at) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['email' => 'This password reset request has already been used.']);
        }

        if (!$reset->verified_at) {
            return redirect()->route('password.verify.parent-student', ['id' => $reset->id])
                ->withErrors(['code' => 'Please verify your code first.']);
        }

        $resetExpiresAt = CarbonImmutable::instance($reset->verified_at)->addMinutes(self::RESET_WINDOW_MINUTES);
        if (CarbonImmutable::now()->greaterThan($resetExpiresAt)) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['email' => 'This reset session has expired. Please request a new code.']);
        }

        return view('auth.reset-password-parent-student', [
            'reset' => $reset,
            'maskedEmail' => $this->maskEmail((string) $reset->email),
        ]);
    }

    public function showAdminReset(string $id): View|RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'admin', 'password.request.admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->showResetForRoles(
            id: $id,
            view: 'auth.reset-password-admin',
            requestedRole: 'admin',
            verifyRoute: 'password.verify.admin',
            requestRoute: 'password.request.admin',
        );
    }

    public function showSuperAdminReset(string $id): View|RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'super_admin', 'password.request.super-admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->showResetForRoles(
            id: $id,
            view: 'auth.reset-password-super-admin',
            requestedRole: 'super_admin',
            verifyRoute: 'password.verify.super-admin',
            requestRoute: 'password.request.super-admin',
        );
    }

    public function resetParentStudentPassword(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'parent_student', 'password.request.parent-student');
        if ($roleCheck) {
            return $roleCheck;
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
        ]);

        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ($reset->reset_at) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['password' => 'This password reset request has already been used.']);
        }

        if (!$reset->verified_at || !$reset->user_id) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['password' => 'Invalid password reset request. Please try again.']);
        }

        $resetExpiresAt = CarbonImmutable::instance($reset->verified_at)->addMinutes(self::RESET_WINDOW_MINUTES);
        if (CarbonImmutable::now()->greaterThan($resetExpiresAt)) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['password' => 'This reset session has expired. Please request a new code.']);
        }

        $user = User::query()
            ->whereKey($reset->user_id)
            ->whereIn('role', ['parent', 'student'])
            ->first();

        if (!$user || !$user->is_active) {
            return redirect()->route('password.request.parent-student')
                ->withErrors(['password' => 'Invalid password reset request. Please try again.']);
        }

        if (Hash::check((string) $validated['password'], (string) $user->password)) {
            return back()->withErrors([
                'password' => 'New password must be different from your previous password.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
            'force_password_change' => false,
        ])->save();

        $reset->forceFill([
            'reset_at' => CarbonImmutable::now(),
            'reset_ip' => $request->ip(),
        ])->save();

        PasswordResetCode::query()
            ->where('user_id', $user->id)
            ->whereNull('reset_at')
            ->where('id', '!=', $reset->id)
            ->update([
                'expires_at' => CarbonImmutable::now(),
            ]);

        return redirect()->route('login')->with('success', 'Password has been successfully reset. You may now sign in.');
    }

    public function resetAdminPassword(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'admin', 'password.request.admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->resetPasswordForRoles(
            request: $request,
            id: $id,
            roles: ['admin'],
            requestRoute: 'password.request.admin',
        );
    }

    public function resetSuperAdminPassword(Request $request, string $id): RedirectResponse
    {
        $roleCheck = $this->ensureResetRoleMatches($id, 'super_admin', 'password.request.super-admin');
        if ($roleCheck) {
            return $roleCheck;
        }

        return $this->resetPasswordForRoles(
            request: $request,
            id: $id,
            roles: ['super_admin'],
            requestRoute: 'password.request.super-admin',
        );
    }

    private function generateSixDigitCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function deliverResetCodeMail(
        string $email,
        string $code,
        ?string $accountLabel = null,
        string $viewName = 'emails.password-reset-code',
    ): void
    {
        $mailable = new PasswordResetVerificationCodeMail($code, self::CODE_TTL_MINUTES, $accountLabel, $viewName);

        $delivery = strtolower((string) config('ccs.password_reset.mail_delivery'));

        if ($delivery === 'queue') {
            if ((string) config('queue.default') === 'sync') {
                Mail::to($email)->send($mailable);
                return;
            }

            Mail::to($email)->queue($mailable);
            return;
        }

        Mail::to($email)->send($mailable);
    }

    private function requestCodeForRoles(
        Request $request,
        array $roles,
        string $requestedRole,
        string $verifyRoute,
        string $requestRoute,
        string $accountLabel,
    ): RedirectResponse {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        $ipKey = 'pwreset:request:ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            return back()->withErrors([
                'email' => 'Too many requests. Please try again in a few minutes.',
            ])->withInput();
        }
        RateLimiter::hit($ipKey, self::CODE_TTL_MINUTES * 60);

        $emailKey = 'pwreset:request:email:'.sha1($email.':'.$requestedRole);
        if (RateLimiter::tooManyAttempts($emailKey, 3)) {
            return back()->withErrors([
                'email' => 'Too many requests for this email. Please try again in a few minutes.',
            ])->withInput();
        }
        RateLimiter::hit($emailKey, self::CODE_TTL_MINUTES * 60);

        $now = CarbonImmutable::now();
        $expiresAt = $now->addMinutes(self::CODE_TTL_MINUTES);
        $code = $this->generateSixDigitCode();

        $user = User::query()
            ->whereIn('role', $roles)
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$user && (bool) config('ccs.password_reset.reveal_email_existence')) {
            return back()->withErrors([
                'email' => 'No account found with that email address.',
            ])->withInput();
        }

        $reset = PasswordResetCode::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user?->id,
            'email' => $email,
            'requested_role' => $requestedRole,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'resend_count' => 0,
            'last_sent_at' => null,
            'locked_at' => null,
            'requested_ip' => $request->ip(),
        ]);

        if ($user) {
            try {
                $this->deliverResetCodeMail(
                    $email,
                    $code,
                    $accountLabel,
                    match ($requestedRole) {
                        'admin' => 'emails.password-reset-code-admin',
                        'super_admin' => 'emails.password-reset-code-super-admin',
                        default => 'emails.password-reset-code',
                    },
                );
                $reset->forceFill([
                    'last_sent_at' => $now,
                    'resend_count' => 1,
                ])->save();
            } catch (\Throwable $e) {
                Log::warning('Password reset code email send failed.', [
                    'email' => $email,
                    'exception' => $e->getMessage(),
                ]);

                if ((bool) config('ccs.password_reset.reveal_email_existence')) {
                    return back()->withErrors([
                        'email' => 'Unable to send verification code. Please check email (SMTP) settings.',
                    ])->withInput();
                }
            }
        }

        return redirect()
            ->route($verifyRoute, ['id' => $reset->id])
            ->with('status', 'If the email is registered, a 6-digit verification code has been sent.');
    }

    private function ensureResetRoleMatches(string $id, string $expected, string $requestRouteName): ?RedirectResponse
    {
        $reset = PasswordResetCode::query()->find($id);
        if (!$reset) {
            return null;
        }

        $role = (string) ($reset->requested_role ?? '');
        if ($role !== '' && $role !== $expected) {
            return redirect()->route($requestRouteName)->withErrors([
                'email' => 'Invalid password reset request. Please try again.',
            ]);
        }

        return null;
    }

    private function verifyCodeForRoles(
        Request $request,
        string $id,
        array $roles,
        string $requestedRole,
        string $requestRoute,
        string $resetRoute,
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ((string) $reset->requested_role !== $requestedRole) {
            return redirect()->route($requestRoute)->withErrors(['code' => 'Invalid password reset request.']);
        }

        if ($reset->reset_at) {
            return redirect()->route($requestRoute)
                ->withErrors(['code' => 'This password reset request has already been used.']);
        }

        if ($reset->locked_at || $reset->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            return back()->withErrors([
                'code' => 'Too many verification attempts. Please request a new code.',
            ]);
        }

        if (!$reset->expires_at || CarbonImmutable::now()->greaterThan($reset->expires_at)) {
            return back()->withErrors([
                'code' => 'Verification code has expired. Please request a new code.',
            ]);
        }

        $reset->forceFill([
            'attempts' => (int) $reset->attempts + 1,
        ])->save();

        if (!$reset->user_id) {
            return back()->withErrors([
                'code' => 'Invalid or expired verification code.',
            ]);
        }

        $user = User::query()->whereKey($reset->user_id)->whereIn('role', $roles)->where('is_active', true)->first();
        if (!$user) {
            return back()->withErrors([
                'code' => 'Invalid or expired verification code.',
            ]);
        }

        if (!$reset->code_hash || !Hash::check((string) $validated['code'], (string) $reset->code_hash)) {
            if ($reset->attempts >= self::MAX_VERIFY_ATTEMPTS) {
                $reset->forceFill(['locked_at' => CarbonImmutable::now()])->save();
            }

            return back()->withErrors([
                'code' => 'Invalid or expired verification code.',
            ]);
        }

        $reset->forceFill([
            'verified_at' => CarbonImmutable::now(),
            'verified_ip' => $request->ip(),
        ])->save();

        return redirect()
            ->route($resetRoute, ['id' => $reset->id])
            ->with('status', 'Verification successful. You can now reset your password.');
    }

    private function resendCodeForRoles(
        Request $request,
        string $id,
        array $roles,
        string $accountLabel,
        string $requestRoute,
    ): RedirectResponse {
        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ($reset->reset_at) {
            return redirect()->route($requestRoute)
                ->withErrors(['email' => 'This password reset request has already been used.']);
        }

        $resendKey = 'pwreset:resend:'.$id.':ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            return back()->withErrors([
                'code' => 'Please wait a moment before requesting another code.',
            ]);
        }
        RateLimiter::hit($resendKey, 60);

        $now = CarbonImmutable::now();
        $expiresAt = $now->addMinutes(self::CODE_TTL_MINUTES);
        $code = $this->generateSixDigitCode();

        $reset->forceFill([
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'locked_at' => null,
            'verified_at' => null,
            'resend_count' => min(65535, (int) $reset->resend_count + 1),
            'last_sent_at' => $now,
        ])->save();

        $user = $reset->user_id
            ? User::query()->whereKey($reset->user_id)->whereIn('role', $roles)->where('is_active', true)->first()
            : null;

        if ($user) {
            try {
                $this->deliverResetCodeMail(
                    (string) $reset->email,
                    $code,
                    $accountLabel,
                    match ((string) $reset->requested_role) {
                        'admin' => 'emails.password-reset-code-admin',
                        'super_admin' => 'emails.password-reset-code-super-admin',
                        default => 'emails.password-reset-code',
                    },
                );
            } catch (\Throwable $e) {
                Log::warning('Password reset resend email failed.', [
                    'email' => (string) $reset->email,
                    'exception' => $e->getMessage(),
                ]);

                if ((bool) config('ccs.password_reset.reveal_email_existence')) {
                    return back()->withErrors([
                        'code' => 'Unable to resend code right now. Please check email (SMTP) settings.',
                    ]);
                }
            }
        }

        return back()->with('status', 'If the email is registered, a new verification code has been sent.');
    }

    private function showResetForRoles(
        string $id,
        string $view,
        string $requestedRole,
        string $verifyRoute,
        string $requestRoute,
    ): View|RedirectResponse {
        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ($reset->reset_at) {
            return redirect()->route($requestRoute)
                ->withErrors(['email' => 'This password reset request has already been used.']);
        }

        if ((string) $reset->requested_role !== $requestedRole) {
            return redirect()->route($requestRoute)->withErrors(['email' => 'Invalid password reset request.']);
        }

        if (!$reset->verified_at) {
            return redirect()->route($verifyRoute, ['id' => $reset->id])
                ->withErrors(['code' => 'Please verify your code first.']);
        }

        $resetExpiresAt = CarbonImmutable::instance($reset->verified_at)->addMinutes(self::RESET_WINDOW_MINUTES);
        if (CarbonImmutable::now()->greaterThan($resetExpiresAt)) {
            return redirect()->route($requestRoute)
                ->withErrors(['email' => 'This reset session has expired. Please request a new code.']);
        }

        return view($view, [
            'reset' => $reset,
            'maskedEmail' => $this->maskEmail((string) $reset->email),
        ]);
    }

    private function resetPasswordForRoles(
        Request $request,
        string $id,
        array $roles,
        string $requestRoute,
    ): RedirectResponse {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
        ]);

        /** @var PasswordResetCode $reset */
        $reset = PasswordResetCode::query()->findOrFail($id);

        if ($reset->reset_at) {
            return redirect()->route($requestRoute)
                ->withErrors(['password' => 'This password reset request has already been used.']);
        }

        if (!$reset->verified_at || !$reset->user_id) {
            return redirect()->route($requestRoute)
                ->withErrors(['password' => 'Invalid password reset request. Please try again.']);
        }

        $resetExpiresAt = CarbonImmutable::instance($reset->verified_at)->addMinutes(self::RESET_WINDOW_MINUTES);
        if (CarbonImmutable::now()->greaterThan($resetExpiresAt)) {
            return redirect()->route($requestRoute)
                ->withErrors(['password' => 'This reset session has expired. Please request a new code.']);
        }

        $user = User::query()
            ->whereKey($reset->user_id)
            ->whereIn('role', $roles)
            ->first();

        if (!$user || !$user->is_active) {
            return redirect()->route($requestRoute)
                ->withErrors(['password' => 'Invalid password reset request. Please try again.']);
        }

        if (Hash::check((string) $validated['password'], (string) $user->password)) {
            return back()->withErrors([
                'password' => 'New password must be different from your previous password.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
            'force_password_change' => false,
        ])->save();

        $reset->forceFill([
            'reset_at' => CarbonImmutable::now(),
            'reset_ip' => $request->ip(),
        ])->save();

        PasswordResetCode::query()
            ->where('user_id', $user->id)
            ->whereNull('reset_at')
            ->where('id', '!=', $reset->id)
            ->update([
                'expires_at' => CarbonImmutable::now(),
            ]);

        return redirect()->route('login')->with('success', 'Password has been successfully reset. You may now sign in.');
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        $atPos = strpos($email, '@');
        if ($atPos === false) {
            return $email;
        }

        $local = substr($email, 0, $atPos);
        $domain = substr($email, $atPos + 1);

        $maskedLocal = match (strlen($local)) {
            0 => '',
            1 => '*',
            2 => substr($local, 0, 1).'*',
            default => substr($local, 0, 1).str_repeat('*', max(1, strlen($local) - 2)).substr($local, -1),
        };

        $domainParts = explode('.', $domain);
        $domainRoot = $domainParts[0] ?? $domain;
        $maskedRoot = strlen($domainRoot) <= 2 ? str_repeat('*', strlen($domainRoot)) : substr($domainRoot, 0, 1).str_repeat('*', strlen($domainRoot) - 2).substr($domainRoot, -1);
        $maskedDomain = $maskedRoot.(count($domainParts) > 1 ? '.'.implode('.', array_slice($domainParts, 1)) : '');

        return $maskedLocal.'@'.$maskedDomain;
    }
}
