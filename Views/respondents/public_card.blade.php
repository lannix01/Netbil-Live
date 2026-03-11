<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verified Card Download</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f6f7fb;margin:0;color:#101828}
        .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card{width:100%;max-width:520px;background:#fff;border:1px solid #e7e9f2;border-radius:14px;padding:20px;box-shadow:0 14px 36px rgba(16,24,40,.12)}
        h1{font-size:20px;margin:0 0 8px}
        .muted{font-size:13px;color:#667085}
        .meta{margin-top:14px;padding:10px;border:1px solid #eef2f6;border-radius:10px;background:#f8fafc}
        .meta .k{font-size:11px;color:#667085;text-transform:uppercase;letter-spacing:.04em}
        .meta .v{font-size:14px;font-weight:700;margin-top:2px}
        label{display:block;font-size:12px;font-weight:700;margin:14px 0 6px;color:#344054}
        input{width:100%;padding:10px 12px;border:1px solid #d0d5dd;border-radius:10px}
        button{margin-top:14px;width:100%;border:1px solid #175cd3;background:#175cd3;color:#fff;border-radius:10px;padding:10px 12px;font-weight:800;cursor:pointer}
        .alert{margin-top:12px;padding:10px 12px;border-radius:10px;font-size:13px}
        .alert-error{background:#fef3f2;border:1px solid #fecdca;color:#b42318}
        .alert-ok{background:#ecfdf3;border:1px solid #abefc6;color:#027a48}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Verified Profile Card</h1>
        <div class="muted">Enter the registered phone number to download this card.</div>

        <div class="meta">
            <div class="k">Name</div>
            <div class="v">{{ $respondent->name }}</div>
            <div class="k" style="margin-top:8px">Title</div>
            <div class="v">{{ $respondent->profile_title ?: '-' }}</div>
        </div>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first('password') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-ok">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('petty.respondents.card.public.download', ['token' => $token]) }}">
            @csrf
            <label>Password (phone number)</label>
            <input type="password" name="password" placeholder="e.g 07XXXXXXXX" required>
            <button type="submit">Download Card</button>
        </form>
    </div>
</div>
</body>
</html>
