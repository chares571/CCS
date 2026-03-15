<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole((string) Auth::user()->role);
        }

        return view('auth.login');
    }

    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
            'role' => 'required|in:parent,student',
        ]);

        $base = Str::slug($request->full_name, '');
        $base = $base !== '' ? $base : 'user';
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$counter;
            $counter++;
        }

        User::create([
            'full_name' => $request->full_name,
            'username' => $username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
            'force_password_change' => false,
        ]);

        return redirect()->route('login')->with('success', 'Registration successful.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$loginField => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // Ensure first redirect after a successful login always reaches the role landing page.
        $request->session()->put('allow_role_landing_once', true);

        if (in_array((string) $user->role, ['super_admin', 'admin'], true) && $this->requiresAdminSecuritySetup($user)) {
            return redirect()->route('account.security.setup.form');
        }

        if ($user->force_password_change && in_array((string) $user->role, ['super_admin', 'admin'], true)) {
            return redirect()->route('password.change.form');
        }

        return $this->redirectByRole($user->role);
    }

    private function requiresAdminSecuritySetup(User $user): bool
    {
        $email = mb_strtolower((string) $user->email);
        $domains = (array) config('ccs.admin_account_security.local_email_domains', ['ccs.local']);
        $domains = array_values(array_filter(array_map('trim', $domains), fn ($value) => $value !== ''));

        foreach ($domains as $domain) {
            $needle = '@'.mb_strtolower($domain);
            if ($needle !== '@' && Str::endsWith($email, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('welcome');
    }

    private function redirectByRole(string $role)
    {
        return match ($role) {
            'super_admin' => redirect()->route('master.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'parent', 'student' => redirect()->route('homepage.feed'),
            default => redirect()->route('homepage.feed'),
        };
    }
}
