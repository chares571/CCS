<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
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

        <div class="welcome-card-content auth-welcome-content auth-welcome-content--register">
            <section class="auth-welcome-message">
                <p class="welcome-kicker">Register an account</p>
                <h1>Start viewing announcements by creating an account. </h1>
            </section>

            <section class="auth-shell-card auth-shell-card--welcome">
                <div class="auth-shell-head">
                    <h2>Create Account</h2>
                    <p>Provide accurate details to register.</p>
                </div>
                @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Enter your full name" autocomplete="name" required>

                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" autocomplete="email" required>

                    <label>Parent or a Student?</label>
                    <select name="role" required>
                        <option value="">Select one</option>
                        <option value="parent" {{ old('role')==='parent'?'selected':'' }}>Parent</option>
                        <option value="student" {{ old('role')==='student'?'selected':'' }}>Student</option>
                    </select>
                    <p class="auth-help">Choose the role that matches your account type.</p>

                    <label>Password</label>
                    <div class="auth-password-wrap">
                        <input id="password" class="auth-password-input" type="password" name="password" placeholder="Create a password" autocomplete="new-password" required>
                        <button type="button" class="auth-password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                            <svg class="eye eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2.5 12s3.6-6.2 9.5-6.2S21.5 12 21.5 12s-3.6 6.2-9.5 6.2S2.5 12 2.5 12z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 9v6"></path>
                            </svg>
                            <svg class="eye eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 4l16 16"></path>
                                <path d="M9.7 6.1A10.8 10.8 0 0 1 12 5.8c5.9 0 9.5 6.2 9.5 6.2a16.2 16.2 0 0 1-3 3.7"></path>
                                <path d="M6.7 6.8A16.4 16.4 0 0 0 2.5 12s3.6 6.2 9.5 6.2c1.8 0 3.4-.4 4.7-1.1"></path>
                                <path d="M10.4 10.4a3 3 0 0 0 4.2 4.2"></path>
                            </svg>
                        </button>
                    </div>

                    <label>Confirm Password</label>
                    <div class="auth-password-wrap">
                        <input id="password_confirmation" class="auth-password-input" type="password" name="password_confirmation" placeholder="Confirm your password" autocomplete="new-password" required>
                        <button type="button" class="auth-password-toggle" data-toggle-password="password_confirmation" aria-label="Show password" aria-pressed="false">
                            <svg class="eye eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2.5 12s3.6-6.2 9.5-6.2S21.5 12 21.5 12s-3.6 6.2-9.5 6.2S2.5 12 2.5 12z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 9v6"></path>
                            </svg>
                            <svg class="eye eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 4l16 16"></path>
                                <path d="M9.7 6.1A10.8 10.8 0 0 1 12 5.8c5.9 0 9.5 6.2 9.5 6.2a16.2 16.2 0 0 1-3 3.7"></path>
                                <path d="M6.7 6.8A16.4 16.4 0 0 0 2.5 12s3.6 6.2 9.5 6.2c1.8 0 3.4-.4 4.7-1.1"></path>
                                <path d="M10.4 10.4a3 3 0 0 0 4.2 4.2"></path>
                            </svg>
                        </button>
                    </div>

                    <ul id="rules" class="requirements">
                        <li id="r1">8-20 characters</li>
                        <li id="r2">At least one uppercase and one lowercase letter</li>
                        <li id="r3">At least one number</li>
                        <li id="r4">No spaces</li>
                        <li id="r5">Disallow ; : " ' / .</li>
                        <li id="r6">Confirm password matches</li>
                    </ul>

                    <button id="registerBtn" class="btn btn-auth" type="submit" disabled>Register Account</button>
                </form>

                <p class="auth-foot">Already registered? <a href="{{ route('login') }}">Sign In</a></p>
                <p class="auth-foot-link"><a href="{{ route('welcome') }}">Back</a></p>
            </section>
        </div>

        <footer class="welcome-card-footer">
            <span>&copy; {{ now()->year }} Cabugbugan Community School. Tagudin District, Ilocos Sur.</span>
        </footer>
    </main>
</div>

<script>
const p=document.getElementById('password');
const c=document.getElementById('password_confirmation');
const b=document.getElementById('registerBtn');
const mark=(id,ok)=>{
    const item=document.getElementById(id);
    if(!item){return;}
    item.classList.toggle('is-met',ok);
};
function validate(){
    const s=p.value,m=c.value;
    const len=/^.{8,20}$/.test(s);
    const uc=/[A-Z]/.test(s);
    const lc=/[a-z]/.test(s);
    const num=/\d/.test(s);
    const noSpace=!/\s/.test(s);
    const noBanned=!/[;:"'\/\.]/.test(s);
    const matched=s.length>0&&s===m;

    mark('r1',len);
    mark('r2',uc&&lc);
    mark('r3',num);
    mark('r4',noSpace);
    mark('r5',noBanned);
    mark('r6',matched);

    b.disabled=!(len&&uc&&lc&&num&&noSpace&&noBanned&&matched);
}
p.addEventListener('input',validate);
c.addEventListener('input',validate);
validate();

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
