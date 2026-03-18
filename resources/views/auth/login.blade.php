<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skybrix Inventory | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --ink: #142016;
            --muted: #6b7f71;
            --paper: rgba(255,255,255,.96);
            --paper-soft: #f4f9f2;
            --canvas: #eff5ec;
            --line: rgba(87,109,91,.14);
            --line-strong: rgba(87,109,91,.24);
            --brand: #4d8a66;
            --brand-strong: #2f6947;
            --shadow: 0 28px 80px rgba(28,46,34,.12);
        }

        *{ box-sizing: border-box; }

        body{
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: "IBM Plex Sans", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(77,138,102,.14), transparent 22%),
                radial-gradient(circle at top right, rgba(112,121,111,.10), transparent 20%),
                linear-gradient(180deg, #f7fbf4 0%, var(--canvas) 100%);
        }

        .login-card{
            width: min(100%, 460px);
            padding: 30px;
            border-radius: 32px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(247,251,245,.98) 100%);
            box-shadow: var(--shadow);
        }

        .login-kicker{
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .login-title{
            margin: 10px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 34px;
            line-height: .98;
            letter-spacing: -.05em;
        }

        .login-alert{
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid var(--line-strong);
            background: linear-gradient(180deg, #ffffff 0%, #f7fbf5 100%);
            font-size: 14px;
            line-height: 1.55;
        }

        .login-alert strong{
            display: block;
            margin-bottom: 4px;
            font-family: "Space Grotesk", sans-serif;
            font-size: 16px;
            letter-spacing: -.03em;
        }

        .login-form{
            margin-top: 22px;
            display: grid;
            gap: 16px;
        }

        .login-field{
            padding: 14px;
            border-radius: 22px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, #ffffff 0%, var(--paper-soft) 100%);
        }

        .login-label{
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted);
        }

        .login-input{
            width: 100%;
            min-height: 50px;
            border-radius: 18px;
            border: 1px solid var(--line-strong);
            background: #ffffff;
            padding: .82rem .95rem;
            font-size: 15px;
            font-family: inherit;
            color: var(--ink);
            outline: none;
            transition: .18s ease;
        }

        .login-input:focus{
            border-color: rgba(77,138,102,.42);
            box-shadow: 0 0 0 .16rem rgba(77,138,102,.10);
        }

        .login-password{
            position: relative;
        }

        .login-toggle{
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%);
            color: #ffffff;
            padding: 0;
            cursor: pointer;
            box-shadow: 0 10px 18px rgba(77,138,102,.18);
        }
        .login-toggle svg{
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .login-submit{
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .02em;
            cursor: pointer;
            transition: .18s ease;
            box-shadow: 0 18px 28px rgba(77,138,102,.18);
        }

        .login-submit:hover{
            transform: translateY(-1px);
            box-shadow: 0 22px 34px rgba(77,138,102,.22);
        }

        .login-submit:disabled{
            opacity: .7;
            cursor: not-allowed;
        }

        @media (max-width: 575.98px){
            body{ padding: 14px; }
            .login-card{
                padding: 22px;
                border-radius: 28px;
            }
            .login-title{
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-kicker">Login</div>
        <h1 class="login-title">Sign in to continue.</h1>

        @if ($errors->any())
            <div class="login-alert">
                <strong>Login failed</strong>
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div class="login-alert">
                <strong>Status</strong>
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.auth.login.submit') }}" class="login-form" id="inventoryLoginForm">
            @csrf

            <div class="login-field">
                <label class="login-label">Email Address</label>
                <input
                    class="login-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="name@skybrix.co.ke"
                    required
                    autofocus
                >
            </div>

            <div class="login-field">
                <label class="login-label">Password</label>
                <div class="login-password">
                    <input
                        class="login-input"
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="login-toggle" id="togglePassword" aria-label="Show password">
                        <svg id="passwordIcon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-submit" id="loginButton">Sign In</button>
        </form>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');
        const loginForm = document.getElementById('inventoryLoginForm');
        const loginButton = document.getElementById('loginButton');

        if (togglePassword && passwordInput && passwordIcon) {
            togglePassword.addEventListener('click', function () {
                const showing = passwordInput.type === 'text';
                passwordInput.type = showing ? 'password' : 'text';
                togglePassword.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                passwordIcon.innerHTML = showing
                    ? '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path><circle cx="12" cy="12" r="3"></circle>'
                    : '<path d="m3 3 18 18"></path><path d="M10.58 10.58A2 2 0 0 0 13.42 13.42"></path><path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a17.78 17.78 0 0 1-4.22 4.92"></path><path d="M6.61 6.61A17.34 17.34 0 0 0 2 12s3.5 7 10 7a9.76 9.76 0 0 0 5.39-1.61"></path>';
            });
        }

        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', function () {
                loginButton.textContent = 'Signing In...';
                loginButton.disabled = true;
            });
        }
    </script>
</body>
</html>
