<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="{{ asset('css/ccs-ui.css') }}">
</head>
<body class="welcome-page auth-welcome-page">
<div class="welcome-layout">
    <header class="welcome-system-brand">
        <img src="{{ asset('images/branding/CCS_logo.png') }}" alt="Cabugbugan Community School logo">
        <div class="welcome-system-text">
            <strong>Cabugbugan Community School</strong>
            <span>Information and Online Enrollment System</span>
        </div>
    </header>

    <main class="welcome-card auth-welcome-card">
        <span class="welcome-particle p1" aria-hidden="true"></span>
        <span class="welcome-particle p2" aria-hidden="true"></span>
        <span class="welcome-particle p3" aria-hidden="true"></span>

        <div class="welcome-card-content auth-welcome-content">
            <section class="auth-welcome-message">
                <p class="welcome-kicker">Welcome back!</p>
                <h1>Sign in to stay updated with latest announcements.</h1>
            </section>

            <section class="auth-shell-card auth-shell-card--welcome">
                <div class="auth-shell-head">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to continue.</p>
                </div>
                @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <label>Email or Username</label>
                    <input type="text" name="email" value="{{ old('email') }}" placeholder="Enter your email or username" autocomplete="username" required>
                    <label>Password</label>
                    <div class="auth-password-wrap">
                        <input id="login_password" class="auth-password-input" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="auth-password-toggle" data-toggle-password="login_password" aria-label="Show password" aria-pressed="false">
                            <svg class="eye eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"></path>
                                <circle cx="12" cy="12" r="3.2"></circle>
                            </svg>
                            <svg class="eye eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 6.3A11.3 11.3 0 0 1 12 6c6.5 0 10 6 10 6a17.5 17.5 0 0 1-3.4 4.1"></path>
                                <path d="M6.1 6.1A17.8 17.8 0 0 0 2 12s3.5 6 10 6c1.6 0 3-.3 4.2-.9"></path>
                                <path d="M9.9 9.9a3.2 3.2 0 0 0 4.2 4.2"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="auth-meta">
                        <label class="auth-check">
                            <input type="checkbox" name="remember" value="1"> Remember me
                        </label>
                        <a href="{{ route('password.request.parent-student') }}">Forgot Password?</a>
                    </div>

                    <button class="btn btn-auth" type="submit">Sign In</button>
                </form>

                <p class="auth-foot">No account yet? <a href="{{ route('register') }}">Register</a></p>
                <p class="auth-foot-link"><a href="{{ route('welcome') }}">Back</a></p>
            </section>
        </div>

        <footer class="welcome-card-footer">
            <span>&copy; {{ now()->year }} Cabugbugan Community School. Tagudin District, Ilocos Sur.</span>
        </footer>
    </main>
</div>
<script>
document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const id = button.getAttribute('data-toggle-password');
        const input = id ? document.getElementById(id) : null;
        if (!input) {
            return;
        }

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
});
</script>
</body>
</html>
