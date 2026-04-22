<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::orderBy('role')->orderBy('full_name')->paginate(20);
        return view('super-admin.users', compact('users'));
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLogger::log('user_active_toggled', 'user', $user->id, [
            'is_active' => $user->is_active,
        ]);

        return back()->with('success', 'User activation status updated.');
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,parent,student',
        ]);

        if ($user->role === 'super_admin') {
            return back()->withErrors(['user' => 'Super Admin role cannot be changed here.']);
        }

        $oldRole = $user->role;
        $user->role = $request->role;
        $user->save();

        AuditLogger::log('user_role_changed', 'user', $user->id, [
            'old_role' => $oldRole,
            'new_role' => $user->role,
        ]);

        return back()->with('success', 'User role updated.');
    }

    public function approveDeletion(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot approve deletion of your own account.']);
        }

        if (!in_array((string) $user->role, ['parent', 'student'], true)) {
            return back()->withErrors(['user' => 'Only parent and student accounts can use this deletion request flow.']);
        }

        if (!$user->account_deletion_requested_at) {
            return back()->withErrors(['user' => 'This account has no pending deletion request.']);
        }

        $oldPhotoPath = trim((string) ($user->profile_photo_path ?? ''));
        $releasedEmail = trim((string) ($user->email ?? ''));
        $archivedEmail = $this->archivedEmailFor($user);
        $releasedUsername = trim((string) ($user->username ?? ''));
        $archivedUsername = $this->archivedUsernameFor($user);

        $user->forceFill([
            'username' => $archivedUsername,
            'email' => $archivedEmail,
            'is_active' => false,
            'profile_photo_path' => null,
            'account_deletion_requested_at' => null,
        ])->save();

        if ($oldPhotoPath !== '') {
            if (Str::startsWith($oldPhotoPath, 'uploads/')) {
                $oldFile = public_path($oldPhotoPath);
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            } else {
                Storage::disk('public')->delete($oldPhotoPath);
            }
        }

        $user->delete();

        AuditLogger::log('user_account_deletion_approved', 'user', $user->id, [
            'approved_by' => auth()->id(),
            'role' => $user->role,
            'released_email' => $releasedEmail,
            'archived_email' => $archivedEmail,
            'released_username' => $releasedUsername,
            'archived_username' => $archivedUsername,
        ]);

        return back()->with('success', 'Account deletion approved and the old email/username are now available again.');
    }

    private function archivedEmailFor(User $user): string
    {
        $email = trim((string) ($user->email ?? ''));
        $fallbackLocal = 'deleted-user-'.$user->id;
        $fallbackDomain = 'deleted.local';

        if ($email === '' || !str_contains($email, '@')) {
            return $fallbackLocal.'+'.time().'@'.$fallbackDomain;
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = trim($local) !== '' ? trim($local) : $fallbackLocal;
        $domain = trim($domain) !== '' ? trim($domain) : $fallbackDomain;

        return $local.'+deleted-user-'.$user->id.'-'.time().'@'.$domain;
    }

    private function archivedUsernameFor(User $user): string
    {
        $username = trim((string) ($user->username ?? ''));
        $base = $username !== '' ? $username : 'deleteduser';

        return $base.'_deleted_'.$user->id.'_'.time();
    }
}
