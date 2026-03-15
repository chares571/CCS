<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless((string) auth()->user()?->role === 'admin', 403);

        $user = auth()->user();

        return view('admin.settings', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless((string) $user?->role === 'admin', 403);

        $localDomains = (array) config('ccs.admin_account_security.local_email_domains', ['ccs.local']);
        $localDomains = array_values(array_filter(array_map('trim', $localDomains), fn ($value) => $value !== ''));

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
                            $fail('Please use a valid email address (not a local school email).');
                            return;
                        }
                    }
                },
            ],
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
        ]);

        if (!Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput([
                'email' => $request->input('email'),
            ]);
        }

        if (Hash::check((string) $validated['password'], (string) $user->password)) {
            return back()->withErrors([
                'password' => 'New password must be different from your previous password.',
            ])->withInput([
                'email' => $request->input('email'),
            ]);
        }

        $oldEmail = (string) $user->email;
        $newEmail = mb_strtolower(trim((string) $validated['email']));

        $user->forceFill([
            'email' => $newEmail,
            'password' => Hash::make((string) $validated['password']),
            'force_password_change' => false,
        ])->save();

        AuditLogger::log('admin_account_security_updated', 'user', $user->id, [
            'changed_by' => $user->id,
            'role' => $user->role,
            'email_changed' => $oldEmail !== $newEmail,
        ]);

        return back()->with('success', 'Account settings updated successfully.');
    }
}

