<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cabugbugan Community School</title>
<link rel="icon" type="image/png" href="{{ asset('images/branding/CCS_logo.png') }}">
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
        <span class="welcome-particle p2" aria-hidden="true"></span>
        <span class="welcome-particle p4" aria-hidden="true"></span>
        <span class="welcome-particle p5" aria-hidden="true"></span>

        <div class="welcome-card-content auth-welcome-content">
            <section class="auth-welcome-message">
                <p class="welcome-kicker">Account Recovery</p>
                <h1>Super Administrator password reset.</h1>
                <p class="welcome-subtitle">Account recovery for the Super Administrator.</p>
            </section>

            <section class="auth-shell-card auth-shell-card--welcome auth-shell-card--update">
                <div class="auth-shell-head">
                    <h2>Forgot Password</h2>
                    <p>Enter your registered email address.</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.request-code.super-admin') }}">
                    @csrf
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" autocomplete="email" required>

                    <button class="btn btn-auth mt-12" type="submit">Send Verification Code</button>
                </form>

                <p class="auth-foot-link"><a href="{{ route('login') }}">Back to Sign In</a></p>
            </section>
        </div>

        <footer class="welcome-card-footer">
            <span>&copy; {{ now()->year }} Cabugbugan Community School. Tagudin District, Ilocos Sur.</span>
        </footer>
    </main>
</div>
</body>
</html>
