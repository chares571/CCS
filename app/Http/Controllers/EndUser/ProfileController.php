<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array((string) $user?->role, ['parent', 'student'], true), 403);

        return view('enduser.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array((string) $user?->role, ['parent', 'student'], true), 403);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $oldName = (string) ($user->full_name ?? '');
        $oldEmail = (string) ($user->email ?? '');
        $newName = trim((string) $validated['full_name']);
        $newEmail = mb_strtolower(trim((string) $validated['email']));

        $user->forceFill([
            'full_name' => $newName,
            'email' => $newEmail,
        ])->save();

        AuditLogger::log('enduser_profile_updated', 'user', $user->id, [
            'changed_by' => $user->id,
            'full_name_changed' => $oldName !== $newName,
            'email_changed' => $oldEmail !== $newEmail,
            'role' => $user->role,
        ]);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array((string) $user?->role, ['parent', 'student'], true), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
        ]);

        if (!Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        if (Hash::check((string) $validated['password'], (string) $user->password)) {
            return back()->withErrors([
                'password' => 'New password must be different from your previous password.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
        ])->save();

        AuditLogger::log('enduser_password_updated', 'user', $user->id, [
            'changed_by' => $user->id,
            'role' => $user->role,
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateProfilePhoto(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array((string) $user?->role, ['parent', 'student'], true), 403);

        if (!Schema::hasColumn('users', 'profile_photo_path')) {
            return back()->withErrors([
                'profile_photo' => 'Profile photo column is missing. Please run migrations (php artisan migrate) then try again.',
            ]);
        }

        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldPath = (string) ($user->profile_photo_path ?? '');
        $file = $request->file('profile_photo');
        $ext = mb_strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg'));
        $dir = public_path('uploads/profile-photos');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'user-'.$user->id.'-'.now()->format('YmdHis').'-'.Str::random(8).'.'.$ext;
        $file->move($dir, $filename);
        $path = 'uploads/profile-photos/'.$filename;

        $user->forceFill([
            'profile_photo_path' => $path,
        ])->save();

        $this->deleteProfilePhotoFile($oldPath);

        AuditLogger::log('enduser_profile_photo_updated', 'user', $user->id, [
            'changed_by' => $user->id,
            'role' => $user->role,
        ]);

        return back()->with('success', 'Profile picture updated.');
    }

    public function removeProfilePhoto(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array((string) $user?->role, ['parent', 'student'], true), 403);

        if (!Schema::hasColumn('users', 'profile_photo_path')) {
            return back()->withErrors([
                'profile_photo' => 'Profile photo column is missing. Please run migrations (php artisan migrate) then try again.',
            ]);
        }

        $oldPath = trim((string) ($user->profile_photo_path ?? ''));

        $user->forceFill([
            'profile_photo_path' => null,
        ])->save();

        $this->deleteProfilePhotoFile($oldPath);

        AuditLogger::log('enduser_profile_photo_removed', 'user', $user->id, [
            'changed_by' => $user->id,
            'role' => $user->role,
        ]);

        return back()->with('success', 'Profile picture removed.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array((string) $user?->role, ['parent', 'student'], true), 403);

        if ($user->account_deletion_requested_at) {
            return back()->withErrors([
                'delete_account' => 'Your account deletion request is already pending approval from the Super Administrator.',
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (!Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'delete_account' => 'Current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'account_deletion_requested_at' => now(),
        ])->save();

        AuditLogger::log('enduser_account_deletion_requested', 'user', $user->id, [
            'changed_by' => $user->id,
            'role' => $user->role,
        ]);

        return back()->with('success', 'Your account deletion request has been submitted for Super Administrator approval.');
    }

    private function deleteProfilePhotoFile(string $path): void
    {
        $path = trim($path);

        if ($path === '') {
            return;
        }

        if (Str::startsWith($path, 'uploads/')) {
            $file = public_path($path);
            if (is_file($file)) {
                @unlink($file);
            }

            return;
        }

        Storage::disk('public')->delete($path);
    }
}
