<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | System32KT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary: #4f46e5;
            --danger: #dc2626;
            --gray: #e5e7eb;
            --bg: #f9fafb;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .reset-container {
            background: #fff;
            padding: 2rem 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
        }
        .reset-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #111827;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            color: #374151;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 8px;
            font-size: 1rem;
        }
        .error {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.4rem;
        }
        .btn-submit {
            display: block;
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.9rem;
            font-size: 1rem;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
            transition: 0.3s all;
        }
        .btn-submit:hover {
            background-color: #4338ca;
        }
        .alert {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #10b981;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <h2>Reset Your Password</h2>

    @if (session('status'))
        <div class="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm New Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-submit">Reset Password</button>
    </form>
</div>

</body>
</html>
