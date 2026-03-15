<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && $this->requiresAdminSecuritySetup()) {
            if ($request->routeIs('account.security.setup.form', 'account.security.setup.update', 'logout', 'logout.get')) {
                return $next($request);
            }

            return redirect()->route('account.security.setup.form');
        }

        if (Auth::check() && Auth::user()->force_password_change) {
            if ($request->routeIs('password.change.form', 'password.change.update', 'logout', 'logout.get')) {
                return $next($request);
            }

            $canUseRoleLandingOnce = (bool) $request->session()->pull('allow_role_landing_once', false);
            if ($canUseRoleLandingOnce && $this->isRoleLandingRoute($request)) {
                return $next($request);
            }

            return redirect()->route('password.change.form');
        }

        return $next($request);
    }

    private function requiresAdminSecuritySetup(): bool
    {
        $user = Auth::user();
        if (!$user || !in_array((string) $user->role, ['super_admin', 'admin'], true)) {
            return false;
        }

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

    private function isRoleLandingRoute(Request $request): bool
    {
        $role = (string) (Auth::user()->role ?? '');

        return match ($role) {
            'parent', 'student' => $request->routeIs('homepage', 'homepage.feed', 'homepage.enrollment'),
            default => false,
        };
    }
}
