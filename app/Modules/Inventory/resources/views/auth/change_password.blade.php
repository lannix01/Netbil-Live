<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skybrix Inventory | Change Password</title>
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
        .pass-card{
            width: min(100%, 480px);
            padding: 30px;
            border-radius: 32px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(247,251,245,.98) 100%);
            box-shadow: var(--shadow);
        }
        .pass-kicker{
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .pass-title{
            margin: 10px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 34px;
            line-height: .98;
            letter-spacing: -.05em;
        }
        .pass-copy{
            margin-top: 12px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }
        .pass-errors{
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid var(--line-strong);
            background: linear-gradient(180deg, #ffffff 0%, #f7fbf5 100%);
            font-size: 14px;
            line-height: 1.55;
        }
        .pass-errors ul{
            margin: 8px 0 0 18px;
            padding: 0;
        }
        .pass-form{
            margin-top: 22px;
            display: grid;
            gap: 16px;
        }
        .pass-field{
            padding: 14px;
            border-radius: 22px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, #ffffff 0%, var(--paper-soft) 100%);
        }
        .pass-label{
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted);
        }
        .pass-input{
            width: 100%;
            min-height: 50px;
            border-radius: 18px;
            border: 1px solid var(--line-strong);
            background: #ffffff;
            padding: .82rem .95rem;
            font-size: 15px;
            outline: none;
            transition: .18s ease;
        }
        .pass-input:focus{
            border-color: rgba(77,138,102,.42);
            box-shadow: 0 0 0 .16rem rgba(77,138,102,.10);
        }
        .pass-actions{
            display: grid;
            gap: 10px;
            margin-top: 6px;
        }
        .pass-btn{
            width: 100%;
            min-height: 52px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .02em;
            cursor: pointer;
            transition: .18s ease;
        }
        .pass-btn-primary{
            border: 0;
            background: linear-gradient(180deg, var(--brand) 0%, var(--brand-strong) 100%);
            color: #ffffff;
            box-shadow: 0 18px 28px rgba(77,138,102,.18);
        }
        .pass-btn-primary:hover{
            transform: translateY(-1px);
            box-shadow: 0 22px 34px rgba(77,138,102,.22);
        }
        .pass-btn-secondary{
            border: 1px solid var(--line-strong);
            background: #ffffff;
            color: var(--ink);
        }
        .pass-btn-secondary:hover{
            border-color: rgba(77,138,102,.32);
            background: #f8fbf6;
        }
        @media (max-width: 575.98px){
            body{ padding: 14px; }
            .pass-card{
                padding: 22px;
                border-radius: 28px;
            }
            .pass-title{
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <section class="pass-card">
        <div class="pass-kicker">Password Update</div>
        <h1 class="pass-title">Change password.</h1>
        <p class="pass-copy">Set a new password to continue.</p>

        @if ($errors->any())
            <div class="pass-errors">
                <strong>Fix the following:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('inventory.auth.password.update') }}" class="pass-form">
            @csrf

            <div class="pass-field">
                <label class="pass-label">Current Password</label>
                <input class="pass-input" type="password" name="current_password" required>
            </div>

            <div class="pass-field">
                <label class="pass-label">New Password</label>
                <input class="pass-input" type="password" name="password" required>
            </div>

            <div class="pass-field">
                <label class="pass-label">Confirm New Password</label>
                <input class="pass-input" type="password" name="password_confirmation" required>
            </div>

            <div class="pass-actions">
                <button class="pass-btn pass-btn-primary">Update Password</button>
            </div>
        </form>

        <form method="POST" action="{{ route('inventory.auth.logout') }}" class="pass-actions" style="margin-top: 12px;">
            @csrf
            <button class="pass-btn pass-btn-secondary">Logout</button>
        </form>
    </section>
</body>
</html>
