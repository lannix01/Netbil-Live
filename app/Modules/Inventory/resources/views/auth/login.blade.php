<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skybrix | Inventory Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }
        
        .inv-shell, .inv-sidebar, .inv-topbar, .inv-content {
            display: none !important;
        }
        
        .login-main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 40px 30px 35px;
            text-align: center;
        }
        
        .logo-container h1 {
            font-size: 26px;
            font-weight: 700;
            margin: 0 0 8px;
            letter-spacing: -0.5px;
        }
        
        .logo-container p {
            opacity: 0.9;
            font-size: 15px;
            margin: 0;
            font-weight: 400;
            color: #cbd5e1;
        }
        
        .login-body {
            padding: 35px 30px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.15s ease;
            background-color: white;
            color: #1e293b;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 4px 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        
        .password-toggle:hover {
            color: #334155;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .login-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .error-message {
            background-color: #fef2f2;
            border-left: 3px solid #ef4444;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #991b1b;
        }
        
        .error-message strong {
            display: block;
            margin-bottom: 4px;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            border-left: 3px solid #22c55e;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #166534;
        }
        
        @media (max-width: 576px) {
            .login-card {
                border-radius: 12px;
            }
            
            .login-header {
                padding: 30px 24px 28px;
            }
            
            .login-body {
                padding: 28px 24px 24px;
            }
            
            .logo-container h1 {
                font-size: 24px;
            }
        }
        
        .eye-icon::before {
            content: 'Show';
            font-style: normal;
        }
        
        .eye-slash-icon::before {
            content: 'Hide';
            font-style: normal;
        }
    </style>
</head>
<body>
    <div class="login-main-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <h1>Skybrix Inventory</h1>
                    <p>Sign in to your account</p>
                </div>
            </div>
            
            <div class="login-body">
                @if ($errors->any())
                    <div class="error-message">
                        <strong>Login failed</strong>
                        {{ $errors->first() }}
                    </div>
                @endif
                
                @if (session('status'))
                    <div class="alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('inventory.auth.login.submit') }}">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input 
                            class="form-control" 
                            type="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            placeholder="name@skybrix.co.ke" 
                            required
                            autofocus
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input 
                                class="form-control" 
                                type="password" 
                                name="password" 
                                id="password"
                                placeholder="Enter your password" 
                                required
                            >
                            <button type="button" class="password-toggle" id="togglePassword">
                                <span class="eye-icon"></span>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        Sign In
                    </button>
                </form>
                
                <div class="login-footer">
                    Admin sets initial credentials. You may be required to change your password on first login.
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('span');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'eye-slash-icon';
            } else {
                passwordInput.type = 'password';
                icon.className = 'eye-icon';
            }
        });
        
        const loginForm = document.querySelector('form');
        const loginButton = document.querySelector('.btn-login');
        
        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', function() {
                loginButton.textContent = 'Signing In...';
                loginButton.disabled = true;
            });
        }
    </script>
</body>
</html>
