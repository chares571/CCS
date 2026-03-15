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
                <h1>Enter the verification code.</h1>
                <p class="welcome-subtitle">Account recovery for the Super Administrator.</p>
                <p class="welcome-subtitle">We sent a 6-digit code to <strong>{{ $maskedEmail }}</strong>.</p>
            </section>

            <section class="auth-shell-card auth-shell-card--welcome auth-shell-card--update">
                <div class="auth-shell-head">
                    <h2>Verify Code</h2>
                    <p>Enter the 6-digit code from your email.</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.verify.submit.super-admin', ['id' => $reset->id]) }}">
                    @csrf
                    <label>Verification Code</label>
                    <input
                        class="auth-code-input"
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        placeholder="Enter 6-digit code"
                        autocomplete="one-time-code"
                        required
                    >

                    @php
                        $remainingSeconds = 0;
                        if ($reset->expires_at) {
                            $remainingSeconds = max(0, (int) ($reset->expires_at->getTimestamp() - now()->getTimestamp()));
                        }
                    @endphp
                    <p class="auth-timer" data-remaining-seconds="{{ $remainingSeconds }}">
                        The code will expire in:
                        <strong><span data-otp-countdown aria-live="polite">--:--</span></strong>
                    </p>

                    <button class="btn btn-auth mt-12" type="submit" data-otp-verify-button>Verify</button>
                </form>

                <form method="POST" action="{{ route('password.resend.super-admin', ['id' => $reset->id]) }}" class="mt-12">
                    @csrf
                    <button class="btn btn-auth btn-auth-secondary" type="submit">Resend Code</button>
                </form>

                <p class="auth-foot-link"><button type="button" class="auth-link-button" data-open-cancel-modal>Cancel</button></p>
            </section>
        </div>

        <footer class="welcome-card-footer">
            <span>&copy; {{ now()->year }} Cabugbugan Community School. Tagudin District, Ilocos Sur.</span>
        </footer>
    </main>
</div>
<div class="logout-modal" id="cancelRecoveryModal" aria-hidden="true">
    <div class="logout-modal__backdrop" data-dismiss-cancel></div>
    <div class="logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="cancelRecoveryTitle" aria-describedby="cancelRecoveryDesc">
        <div class="logout-modal__icon" aria-hidden="true">!</div>
        <h2 id="cancelRecoveryTitle">Cancel Recovery</h2>
        <p id="cancelRecoveryDesc">Are you sure you want to cancel password recovery and go back to sign in?</p>
        <div class="logout-modal__actions">
            <button type="button" class="btn btn-danger" data-dismiss-cancel>No</button>
            <button type="button" class="btn" id="cancelRecoveryConfirmBtn">Yes, Cancel</button>
        </div>
    </div>
</div>
<script>
(() => {
    const timer = document.querySelector('[data-remaining-seconds]');
    const label = document.querySelector('[data-otp-countdown]');
    const verifyButton = document.querySelector('[data-otp-verify-button]');
    if (!timer || !label) return;

    let remaining = parseInt(timer.getAttribute('data-remaining-seconds') || '0', 10);
    if (!Number.isFinite(remaining) || remaining < 0) remaining = 0;

    const format = (seconds) => {
        seconds = Math.max(0, Math.floor(seconds));
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    };

    const render = () => {
        label.textContent = format(remaining);
        if (verifyButton) {
            verifyButton.disabled = remaining <= 0;
        }
        timer.classList.toggle('is-expired', remaining <= 0);
    };

    render();

    const interval = setInterval(() => {
        remaining -= 1;
        if (remaining <= 0) {
            remaining = 0;
            render();
            clearInterval(interval);
            return;
        }
        render();
    }, 1000);
})();
</script>
<script>
(() => {
    const modal = document.getElementById('cancelRecoveryModal');
    const openButton = document.querySelector('[data-open-cancel-modal]');
    const confirmButton = document.getElementById('cancelRecoveryConfirmBtn');

    if (!modal || !openButton || !confirmButton) {
        return;
    }

    const dismissButtons = modal.querySelectorAll('[data-dismiss-cancel]');

    const openModal = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        confirmButton.focus();
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    openButton.addEventListener('click', openModal);

    confirmButton.addEventListener('click', () => {
        window.location.href = "{{ route('login') }}";
    });

    dismissButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
})();
</script>
</body>
</html>
