<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .container {
            max-width: 480px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .card { 
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: none;
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        h4 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .text-muted {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.9375rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .mb-3 {
            margin-bottom: 1.25rem;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        
        .btn-dark {
            background: #1e293b;
            color: white;
        }
        
        .btn-dark:hover {
            background: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-outline-secondary {
            background: transparent;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        
        .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #475569;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        @media (max-width: 640px) {
            .card-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4>Change Password</h4>
            <p class="text-muted">Default password must be changed to continue.</p>
            <form method="POST" action="{{ route('inventory.auth.password.update') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input class="form-control" type="password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input class="form-control" type="password" name="password_confirmation" required>
                </div>
                <button class="btn btn-dark">Update Password</button>
            </form>
            <form class="mt-3" method="POST" action="{{ route('inventory.auth.logout') }}">
                @csrf
                <button class="btn btn-outline-secondary">Logout</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>