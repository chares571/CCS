<!doctype html>
<html lang="en-US">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cabugbugan Community School</title>
<link rel="icon" type="image/png" href="{{ asset('images/branding/CCS_logo.png') }}">
<link rel="stylesheet" href="{{ asset('css/ccs-ui.css') }}">
</head>
<body class="welcome-page">
<div class="welcome-layout">
    <header class="welcome-system-brand">
        <img src="{{ asset('images/branding/CCS_logo.png') }}" alt="Cabugbugan Community School logo">
        <div class="welcome-system-text">
            <strong>Cabugbugan Community School</strong>
            <span>Information and Online Enrollment System</span>
        </div>
    </header>

    <main class="welcome-card">
        <span class="welcome-particle p1" aria-hidden="true"></span>
        <span class="welcome-particle p2" aria-hidden="true"></span>
        <span class="welcome-particle p3" aria-hidden="true"></span>
        <span class="welcome-particle p4" aria-hidden="true"></span>
        <span class="welcome-particle p5" aria-hidden="true"></span>

        <div class="welcome-card-content">
            <section class="welcome-message">
                <p class="welcome-kicker">Welcome to</p>
                <h1>CABUGBUGAN COMMUNITY SCHOOL!</h1>
                <div class="welcome-actions">
                    @auth
                        <a class="btn welcome-btn-login" href="{{ route('dashboard') }}">Continue</a>
                        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                            @csrf
                            <button class="btn btn-logout" type="submit">Logout</button>
                        </form>
                    @else
                        <a class="btn welcome-btn-login" href="{{ route('login') }}">Sign in</a>
                        <a class="btn welcome-btn-register" href="{{ route('register') }}">Register</a>
                    @endauth
                </div>
            </section>

            <section class="welcome-logo-stage">
                <div class="welcome-seal-wrap">
                    <img src="{{ asset('images/branding/CCS_logo.png') }}" alt="Cabugbugan Community School seal">
                </div>
            </section>
        </div>

        <footer class="welcome-card-footer">
            <span>&copy; {{ now()->year }} Cabugbugan Community School. Tagudin District, Ilocos Sur.</span>
        </footer>
    </main>
</div>
</body>
</html>
