<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register</title>
  @vite('resources/css/app.css')
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #10b981;
      --primary-dark: #059669;
      --primary-light: #d1fae5;
      --text: #1f2937;
      --text-light: #6b7280;
      --bg: #f9fafb;
      --card-bg: rgba(255, 255, 255, 0.96);
      --error: #ef4444;
      --success: #10b981;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      color: var(--text);
      line-height: 1.5;
    }

    .login-card {
      max-width: 440px;
      width: 100%;
      background: var(--card-bg);
      border-radius: 1.25rem;
      padding: 2.5rem;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1),
                  0 10px 10px -5px rgba(0, 0, 0, 0.04);
      position: relative;
      overflow: hidden;
      animation: fadeInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, var(--primary-light) 0%, transparent 70%);
      opacity: 0.4;
      z-index: -1;
      animation: rotate 20s linear infinite;
    }

    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-header h2 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 0.5rem;
      background: linear-gradient(90deg, var(--primary), #3b82f6);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .login-header p {
      color: var(--text-light);
      font-size: 0.9375rem;
    }

    .input-group {
      position: relative;
      margin-bottom: 1.5rem;
    }

    .form-input {
      width: 100%;
      padding: 1rem;
      padding-top: 1.5rem;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      font-size: 0.9375rem;
      transition: all 0.2s ease;
      background-color: #f9fafb;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
      background-color: white;
    }

    .floating-label {
      position: absolute;
      top: 1rem;
      left: 1rem;
      font-size: 0.875rem;
      color: var(--text-light);
      pointer-events: none;
      transition: all 0.2s ease;
      transform-origin: left center;
    }

    .form-input:focus + .floating-label,
    .form-input:not(:placeholder-shown) + .floating-label {
      transform: translateY(-0.75rem) scale(0.85);
      color: var(--primary);
      font-weight: 500;
    }

    .toggle-password {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #9ca3af;
      font-size: 1.1rem;
      transition: color 0.2s;
    }

    .toggle-password:hover {
      color: var(--primary);
    }

    .submit-btn {
      width: 100%;
      padding: 0.875rem;
      background-color: var(--primary);
      color: white;
      border: none;
      border-radius: 0.5rem;
      font-weight: 600;
      font-size: 0.9375rem;
      cursor: pointer;
      transition: all 0.2s;
      margin-bottom: 1.5rem;
    }

    .submit-btn:hover {
      background-color: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .login-link {
      text-align: center;
      font-size: 0.875rem;
      color: var(--text-light);
      padding-top: 1.5rem;
      border-top: 1px solid #e5e7eb;
    }

    .login-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      margin-left: 0.25rem;
      transition: color 0.2s;
    }

    .login-link a:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    .toast {
      position: fixed;
      top: 1.5rem;
      right: 1.5rem;
      padding: 0.875rem 1.25rem;
      border-radius: 0.75rem;
      color: white;
      font-weight: 500;
      font-size: 0.875rem;
      z-index: 9999;
      animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .toast::before {
      content: '';
      display: inline-block;
      width: 1rem;
      height: 1rem;
      background-size: contain;
      background-repeat: no-repeat;
    }

    .toast-success {
      background-color: var(--success);
    }

    .toast-success::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'%3E%3Cpath fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/%3E%3C/svg%3E");
    }

    .toast-error {
      background-color: var(--error);
    }

    .toast-error::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'%3E%3Cpath fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z' clip-rule='evenodd'/%3E%3C/svg%3E");
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes rotate {
      from {
        transform: rotate(0deg);
      }
      to {
        transform: rotate(360deg);
      }
    }

    /* Responsive adjustments */
    @media (max-width: 480px) {
      .login-card {
        padding: 1.75rem;
      }

      .login-header h2 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>

  {{-- Toasts --}}
  @if (session('status'))
    <div class="toast toast-success" id="toast">{{ session('status') }}</div>
  @elseif ($errors->any())
    <div class="toast toast-error" id="toast">{{ $errors->first() }}</div>
  @endif

  <div class="login-card">
    <div class="login-header">
      <h2>Create Account</h2>
      <p>Join us today and start your journey</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
      @csrf

      {{-- Name --}}
      <div class="input-group">
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus placeholder=" " class="form-input" />
        <label for="name" class="floating-label">Full Name</label>
      </div>

      {{-- Email --}}
      <div class="input-group">
        <input id="email" name="email" type="email" value="{{ old('email') }}" required placeholder=" " class="form-input" />
        <label for="email" class="floating-label">Email Address</label>
      </div>

      {{-- Password --}}
      <div class="input-group">
        <input id="password" name="password" type="password" required placeholder=" " class="form-input" />
        <label for="password" class="floating-label">Password</label>
        <span class="toggle-password" onclick="togglePassword('password')">👁</span>
      </div>

      {{-- Confirm Password --}}
      <div class="input-group">
        <input id="password_confirmation" name="password_confirmation" type="password" required placeholder=" " class="form-input" />
        <label for="password_confirmation" class="floating-label">Confirm Password</label>
        <span class="toggle-password" onclick="togglePassword('password_confirmation')">👁</span>
      </div>

      {{-- Submit --}}
      <button type="submit" class="submit-btn">Register</button>
    </form>

    {{-- Login Link --}}
    @if (Route::has('login'))
      <div class="login-link">
        Already have an account?
        <a href="{{ route('login') }}">Sign in</a>
      </div>
    @endif
  </div>

  <script>
    function togglePassword(fieldId) {
      const passwordInput = document.getElementById(fieldId);
      const toggleIcon = passwordInput.nextElementSibling.nextElementSibling;

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '👁️';
      } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁';
      }
    }

    // Auto-hide toast
    const toast = document.getElementById('toast');
    if (toast) {
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => {
          toast.style.display = 'none';
        }, 300);
      }, 5000);
    }

    // Add focus styles for better accessibility
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Tab') {
        document.body.classList.add('user-tabbing');
      }
    });

    document.addEventListener('mousedown', function() {
      document.body.classList.remove('user-tabbing');
    });
  </script>
</body>
</html>
