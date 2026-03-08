<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PettyCash Login</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:#f6f7fb; margin:0; }
        .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { width:100%; max-width:420px; background:#fff; border:1px solid #e7e9f2; border-radius:14px; padding:22px; box-shadow: 0 8px 30px rgba(16,24,40,0.08); }
        h1 { font-size:18px; margin:0 0 6px; }
        p { margin:0 0 16px; color:#667085; font-size:13px; }
        label { display:block; font-size:12px; color:#344054; margin:12px 0 6px; }
        input { width:100%; padding:10px 12px; border:1px solid #d0d5dd; border-radius:10px; outline:none; }
        input:focus { border-color:#7f56d9; box-shadow:0 0 0 4px rgba(127,86,217,.12); }
        .row { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; }
        .btn { width:100%; margin-top:14px; background:#7f56d9; color:#fff; border:none; border-radius:10px; padding:10px 12px; font-weight:600; cursor:pointer; }
        .btn:hover { filter:brightness(0.95); }
        .err { margin-top:10px; background:#fef3f2; color:#b42318; border:1px solid #fecdca; padding:10px 12px; border-radius:10px; font-size:13px; }
        .small { font-size:12px; color:#475467; display:flex; align-items:center; gap:8px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>PettyCash Login</h1>
        <!-- <p>Sign in to manage credits, spendings, reports and PDFs.</p> -->

        @if ($errors->any())
            <div class="err">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('petty.login.submit') }}">
            @csrf

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label>Password</label>
            <input type="password" name="password" required>

            <div class="row">
                <label class="small">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
            </div>

            <button class="btn" type="submit">Login</button>
        </form>
    </div>
</div>
</body>
</html>
