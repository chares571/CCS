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

        return $this->redirectByRole($user->role);
    }

    public function recoverParentStudentPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?!.*[;:"\'\/\.])(?=\S+$).{8,20}$/'],
        ]);

        return $this->recoverPasswordByRoles($request, $validated, ['parent', 'student']);
    }

    private function recoverPasswordByRoles(Request $request, array $validated, array $roles): \Illuminate\Http\RedirectResponse
    {
        $user = User::query()
            ->whereIn('role', $roles)
            ->where('email', $validated['email'])
            ->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Account details do not match our records.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        if (!$this->namesRoughlyMatch((string) $validated['full_name'], (string) $user->full_name)) {
            return back()->withErrors([
                'full_name' => 'Account details do not match our records.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'email' => 'Account is inactive. Contact Super Admin.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        if (Hash::check((string) $validated['password'], (string) $user->password)) {
            return back()->withErrors([
                'password' => 'New password must be different from your previous password.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        $user->password = Hash::make((string) $validated['password']);
        $user->force_password_change = false;
        $user->save();

        return redirect()->route('password.request.parent-student')->with('success', 'Password recovery successful. You may now sign in.');
    }

    private function namesRoughlyMatch(string $inputName, string $storedName): bool
    {
        $normalizedInput = $this->normalizeComparableName($inputName);
        $normalizedStored = $this->normalizeComparableName($storedName);

        if ($normalizedInput === '' || $normalizedStored === '') {
            return false;
        }

        if ($normalizedInput === $normalizedStored) {
            return true;
        }

        $inputTokens = array_values(array_filter(explode(' ', $normalizedInput)));
        $storedTokens = array_values(array_filter(explode(' ', $normalizedStored)));
        $storedMap = array_fill_keys($storedTokens, true);

        foreach ($inputTokens as $token) {
            if (!isset($storedMap[$token])) {
                return false;
            }
        }

        return true;
    }

    private function normalizeComparableName(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = preg_replace('/[^A-Z0-9\s]/', ' ', $normalized) ?? '';
        return trim((string) preg_replace('/\s+/', ' ', $normalized));
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
            default => redirect()->route('homepage'),
        };
    }
}
