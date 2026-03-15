<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        abort_unless((string) auth()->user()?->role === 'super_admin', 403);
        $user = auth()->user();

        return view('master.settings', compact('user'));
    }

    public function updateOwnPassword(Request $request)
    {
        $user = $request->user();
        abort_unless((string) $user?->role === 'super_admin', 403);

        $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
            'current_password' => ['required', 'string'],
        ]);

        if (!Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        if (Hash::check((string) $request->input('password'), (string) $user->password)) {
            return back()->withErrors([
                'password' => 'This password was already used. Please enter a new password.',
            ]);
        }

        $oldEmail = (string) $user->email;
        $newEmail = mb_strtolower(trim((string) $request->input('email')));

        $user->forceFill([
            'email' => $newEmail,
            'password' => Hash::make((string) $request->input('password')),
            'force_password_change' => false,
        ])->save();

        AuditLogger::log('super_admin_account_security_updated', 'user', $user->id, [
            'changed_by' => $user->id,
            'role' => $user->role,
            'email_changed' => $oldEmail !== $newEmail,
        ]);

        return back()->with('success', 'Account settings updated successfully.');
    }
}
