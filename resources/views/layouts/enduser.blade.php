<!doctype html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Cabugbugan Community School' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/branding/CCS_logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/ccs-ui.css') }}">
</head>
<body class="enduser-body">
<div class="enduser-shell">
    <header class="enduser-topnav print-hide">
        <div class="enduser-topnav-inner">
            <a class="enduser-brand" href="{{ route('homepage.feed') }}">
                <img src="{{ asset('images/branding/CCS_logo.png') }}" alt="School logo">
                <div>
                    <strong>Cabugbugan Community School</strong>
                    <small>Information and Online Enrollment System</small>
                </div>
            </a>

            <nav class="enduser-menu" aria-label="Parent and student navigation">
                <a class="enduser-link {{ request()->routeIs('homepage.feed') ? 'active' : '' }}" href="{{ route('homepage.feed') }}">
                    <x-icon name="announcements" /> Information Feed
                </a>
                <a class="enduser-link {{ request()->routeIs('homepage') || request()->routeIs('homepage.enrollment') || request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('homepage.enrollment') }}">
                    <x-icon name="enrollment" /> Enrollment
                </a>
            </nav>

            <button type="button" class="enduser-account-toggle js-enduser-account-toggle" aria-expanded="false" aria-controls="enduserUserPanel">
                <span class="enduser-account-toggle__icon" aria-hidden="true">
                    <span class="enduser-account-toggle__bar"></span>
                    <span class="enduser-account-toggle__bar"></span>
                    <span class="enduser-account-toggle__bar"></span>
                </span>
                <span class="enduser-account-toggle__label sr-only">Account Menu</span>
            </button>

            <div class="enduser-user" id="enduserUserPanel">
                <div class="enduser-drawer-head">
                    <strong>Account</strong>
                    <button type="button" class="enduser-drawer-close" data-enduser-account-close aria-label="Close account menu">
                        Close
                    </button>
                </div>
                <div class="enduser-user-meta">
                    <span>Signed in as</span>
                    <strong>{{ auth()->user()->full_name ?? 'User' }}</strong>
                    <small>{{ strtoupper(str_replace('_', ' ', auth()->user()->role ?? 'USER')) }}</small>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="js-logout-form">
                    @csrf
                    <button class="btn btn-logout" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </header>
    <button type="button" class="enduser-account-backdrop" data-enduser-account-close aria-label="Close account menu"></button>

    <main class="enduser-main">
        <section class="enduser-pagehead print-hide">
            <div class="container">
                <h1 class="page-title">@yield('page_title', 'Parent/Student Portal')</h1>
                <p class="muted">@yield('page_subtitle', 'Enrollment management and school announcements')</p>
            </div>
        </section>

        <div class="container enduser-container">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<div class="logout-modal" id="logoutConfirmModal" aria-hidden="true">
    <div class="logout-modal__backdrop" data-dismiss-logout></div>
    <div class="logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle" aria-describedby="logoutConfirmDesc">
        <div class="logout-modal__icon" aria-hidden="true">!</div>
        <h2 id="logoutConfirmTitle">Confirm Logout</h2>
        <p id="logoutConfirmDesc">Are you sure you want to log out from your account?</p>
        <div class="logout-modal__actions">
            <button type="button" class="btn btn-danger" data-dismiss-logout>Cancel</button>
            <button type="button" class="btn btn-logout" id="logoutConfirmBtn">Log Out</button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('logoutConfirmModal');
    const confirmButton = document.getElementById('logoutConfirmBtn');

    if (!modal || !confirmButton) {
        return;
    }

    const dismissButtons = modal.querySelectorAll('[data-dismiss-logout]');
    const logoutForms = document.querySelectorAll('.js-logout-form');
    let pendingForm = null;

    const openModal = (form) => {
        pendingForm = form;
        document.body.classList.remove('enduser-account-open');
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        confirmButton.focus();
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        pendingForm = null;
    };

    logoutForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.skipConfirm === '1') {
                return;
            }

            event.preventDefault();
            openModal(form);
        });
    });

    confirmButton.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        pendingForm.dataset.skipConfirm = '1';
        pendingForm.submit();
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

(() => {
    const toggle = document.querySelector('.js-enduser-account-toggle');
    const panel = document.getElementById('enduserUserPanel');
    const closeTargets = document.querySelectorAll('[data-enduser-account-close]');
    const mobileQuery = window.matchMedia('(max-width: 1000px)');

    if (!toggle || !panel) {
        return;
    }

    const setOpen = (open) => {
        document.body.classList.toggle('enduser-account-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(!document.body.classList.contains('enduser-account-open'));
    });

    closeTargets.forEach((target) => {
        target.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('click', (event) => {
        if (!mobileQuery.matches) {
            return;
        }

        if (panel.contains(event.target) || toggle.contains(event.target)) {
            return;
        }

        setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    const syncForViewport = () => {
        if (!mobileQuery.matches) {
            setOpen(false);
        }
    };

    if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', syncForViewport);
    } else {
        mobileQuery.addListener(syncForViewport);
    }
})();
</script>
</body>
</html>
