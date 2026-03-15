<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountSecuritySetupController extends Controller
{
    public function form(): View
    {
        $user = Auth::user();

        abort_unless($user && $this->isAdminRole((string) $user->role), 403);

        return view('auth.account-security-setup', [
            'user' => $user,
            'localEmailDomains' => $this->localEmailDomains(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user && $this->isAdminRole((string) $user->role), 403);

        $localDomains = $this->localEmailDomains();

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($localDomains) {
                    $email = mb_strtolower(trim((string) $value));
                    foreach ($localDomains as $domain) {
                        $needle = '@'.mb_strtolower($domain);
                        if ($needle !== '@' && Str::endsWith($email, $needle)) {
                            $fail('Please use a valid personal email address (not a local school email).');
                            return;
                        }
                    }
                },
            ],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));

        if (Hash::check((string) $validated['password'], (string) $user->password)) {
            return back()->withErrors([
                'password' => 'New password must be different from your previous password.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        $user->forceFill([
            'email' => $email,
            'password' => Hash::make((string) $validated['password']),
            'force_password_change' => false,
        ])->save();

        return redirect()->route($user->role === 'super_admin' ? 'master.dashboard' : 'admin.dashboard')
            ->with('success', 'Account security setup completed.');
    }

    private function isAdminRole(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin'], true);
    }

    /**
     * @return array<int, string>
     */
    private function localEmailDomains(): array
    {
        $domains = (array) config('ccs.admin_account_security.local_email_domains', ['ccs.local']);
        $domains = array_values(array_filter(array_map('trim', $domains), fn ($value) => $value !== ''));

        return $domains !== [] ? $domains : ['ccs.local'];
    }
}

