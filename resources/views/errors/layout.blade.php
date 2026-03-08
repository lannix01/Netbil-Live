<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($statusCode ?? 500) }} | NetBil</title>
    <style>
        :root {
            --bg-a: #f8fafc;
            --bg-b: #eef2ff;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --line: #dbe2ef;
            --accent: #2563eb;
            --accent-soft: #dbeafe;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at 18% 12%, var(--bg-b) 0%, var(--bg-a) 48%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: min(640px, 100%);
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 24px 64px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }
        .bar {
            height: 6px;
            background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 40%, #60a5fa 100%);
        }
        .body { padding: 26px 28px; }
        .code {
            display: inline-flex;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: #1d4ed8;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        h1 {
            margin: 14px 0 8px;
            font-size: clamp(1.35rem, 2.4vw, 1.9rem);
            line-height: 1.25;
        }
        p {
            margin: 0;
            color: var(--muted);
            font-size: 0.98rem;
            line-height: 1.55;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: .15s ease;
        }
        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .btn-primary:hover { filter: brightness(0.95); }
        .btn-soft {
            background: #f8fafc;
            border-color: var(--line);
            color: #1f2937;
        }
        .btn-soft:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .meta {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px dashed var(--line);
            color: #64748b;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>
@php
    $statusCode = (int)($statusCode ?? 500);
    $homeUrl = auth()->check()
        ? (\Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/dashboard'))
        : (\Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login'));
@endphp
<section class="card" role="alert" aria-live="assertive">
    <div class="bar"></div>
    <div class="body">
        <span class="code">Error {{ $statusCode }}</span>
        <h1>{{ $title ?? 'Something went wrong.' }}</h1>
        <p>{{ $message ?? 'We could not complete this request. Please retry in a moment.' }}</p>
        <div class="actions">
            <a href="{{ $homeUrl }}" class="btn btn-primary">Go to Dashboard</a>
            <button type="button" class="btn btn-soft" onclick="window.location.reload()">Try Again</button>
            <button type="button" class="btn btn-soft" onclick="window.history.back()">Go Back</button>
        </div>
        <div class="meta">
            Timestamp: {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
</section>
</body>
</html>
