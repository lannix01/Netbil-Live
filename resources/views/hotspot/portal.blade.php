<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Internet Access Portal</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary: #10b981;
        --primary-dark: #059669;
        --primary-light: #ecfdf5;
        --secondary: #3b82f6;
        --text: #1f2937;
        --text-light: #6b7280;
        --bg: #f0fdf4;
        --card: #ffffff;
        --border: #e5e7eb;
        --shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.12);
        --radius: 16px;
        --radius-sm: 10px;
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #f0fdf4 0%, #d1fae5 100%);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
        color: var(--text);
    }

    .container {
        width: 100%;
        max-width: 480px;
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .header {
        text-align: center;
        margin-bottom: 32px;
    }

    .logo {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 32px;
        box-shadow: var(--shadow);
    }

    .header h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 8px;
    }

    .header p {
        color: var(--text-light);
        font-size: 16px;
    }

    .card {
        background: var(--card);
        border-radius: var(--radius);
        padding: 32px;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }

    .tabs {
        display: flex;
        background: var(--primary-light);
        border-radius: var(--radius-sm);
        padding: 4px;
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
    }

    .tabs button {
        flex: 1;
        padding: 14px 20px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        color: var(--text-light);
        border-radius: var(--radius-sm);
        transition: var(--transition);
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .tabs button.active {
        color: white;
    }

    .slider {
        position: absolute;
        top: 4px;
        left: 4px;
        width: calc(50% - 4px);
        height: calc(100% - 8px);
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: var(--radius-sm);
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .tab-content {
        display: none;
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(10px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .tab-content.active {
        display: block;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
        transition: var(--transition);
    }

    input, select {
        width: 100%;
        padding: 14px 16px 14px 48px;
        border: 2px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 15px;
        transition: var(--transition);
        background: white;
        color: var(--text);
    }

    input:focus, select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    input:focus + i {
        color: var(--primary);
    }

    .password-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--text-light);
        cursor: pointer;
        font-size: 16px;
    }

    .submit-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: var(--radius-sm);
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 24px;
        position: relative;
        overflow: hidden;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .submit-btn:active {
        transform: translateY(0);
    }

    .submit-btn:after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
    }

    .submit-btn:focus:not(:active)::after {
        animation: ripple 1s ease-out;
    }

    @keyframes ripple {
        0% {
            transform: scale(0, 0);
            opacity: 0.5;
        }
        100% {
            transform: scale(20, 20);
            opacity: 0;
        }
    }

    .packages-container {
        margin-top: 20px;
        max-height: 300px;
        overflow-y: auto;
        padding-right: 8px;
    }

    .package-card {
        border: 2px solid var(--border);
        padding: 20px;
        border-radius: var(--radius-sm);
        margin-bottom: 12px;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        background: white;
    }

    .package-card:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .package-card.selected {
        border-color: var(--primary);
        background: var(--primary-light);
    }

    .package-radio {
        position: absolute;
        opacity: 0;
    }

    .package-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .package-info h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 4px;
    }

    .package-info p {
        font-size: 14px;
        color: var(--text-light);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .package-price {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        background: white;
        padding: 8px 16px;
        border-radius: var(--radius-sm);
        border: 2px solid var(--primary-light);
    }

    .package-card.selected .package-price {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
        font-size: 12px;
        font-weight: 600;
        border-radius: 20px;
        margin-left: 8px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .error {
        color: #dc2626;
        font-size: 14px;
        margin-top: 8px;
        padding: 12px;
        background: #fee2e2;
        border-radius: var(--radius-sm);
        border-left: 4px solid #dc2626;
        animation: shake 0.5s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-5px); }
        40%, 80% { transform: translateX(5px); }
    }

    .success {
        color: #10b981;
        font-size: 14px;
        margin-top: 8px;
        padding: 12px;
        background: #d1fae5;
        border-radius: var(--radius-sm);
        border-left: 4px solid #10b981;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .forgot-password {
        text-align: center;
        margin-top: 16px;
    }

    .forgot-password a {
        color: var(--primary);
        text-decoration: none;
        font-size: 14px;
        transition: var(--transition);
    }

    .forgot-password a:hover {
        text-decoration: underline;
    }

    .loader {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .powered-by {
        text-align: center;
        margin-top: 24px;
        font-size: 14px;
        color: var(--text-light);
    }

    .powered-by span {
        color: var(--primary);
        font-weight: 600;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }

    /* Responsive */
    @media (max-width: 480px) {
        .card {
            padding: 24px;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .tabs button {
            padding: 12px 16px;
            font-size: 14px;
        }
        
        .package-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        
        .package-price {
            align-self: flex-end;
        }
    }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="logo">
            <i class="fas fa-wifi"></i>
        </div>
        <h1>Internet Access Portal</h1>
        <p>Choose your preferred connection method</p>
    </div>

    <div class="card">
        <div class="tabs">
            <div class="slider" id="slider"></div>
            <button onclick="showTab('metered')" class="active" id="meteredTab">
                <i class="fas fa-user"></i> Metered
            </button>
            <button onclick="showTab('hotspot')" id="hotspotTab">
                <i class="fas fa-hotdog"></i> Hotspot
            </button>
        </div>

        <!-- Metered Login -->
        <div id="meteredContent" class="tab-content active">
            <form id="meteredForm" method="POST" action="{{ route('portal.metered') }}">
                @csrf
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user-circle"></i> Username
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               placeholder="Enter your username" 
                               required
                               autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Password (optional)"
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="showForgotPassword()">Forgot your password?</a>
                </div>

                @error('metered')
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                </div>
                @enderror

                <button type="submit" class="submit-btn" id="meteredBtn">
                    <span>Connect</span>
                    <i class="fas fa-sign-in-alt"></i>
                    <div class="loader"></div>
                </button>
            </form>
        </div>

        <!-- Hotspot Packages -->
        <div id="hotspotContent" class="tab-content">
            <div class="form-group">
                <label><i class="fas fa-bolt"></i> Select a Package</label>
                <p style="color: var(--text-light); font-size: 14px; margin-bottom: 16px;">
                    Choose your preferred internet package
                </p>
            </div>

            <form id="hotspotForm" method="POST" action="{{ route('portal.hotspot') }}">
                @csrf
                <div class="packages-container">
                    @foreach($packages as $index => $pkg)
                    <label class="package-card {{ $index === 1 ? 'selected' : '' }}">
                        <input type="radio" 
                               name="package_id" 
                               value="{{ $pkg->id }}" 
                               class="package-radio"
                               {{ $index === 1 ? 'checked' : '' }}
                               required>
                        <div class="package-content">
                            <div class="package-info">
                                <h3>
                                    {{ $pkg->name }}
                                    @if($index === 0)
                                    <span class="badge">Popular</span>
                                    @endif
                                </h3>
                                <p>
                                    <i class="fas fa-tachometer-alt"></i> {{ $pkg->speed }}
                                    <i class="fas fa-clock"></i> {{ $pkg->duration }} hours
                                </p>
                            </div>
                            <div class="package-price">
                                KES {{ $pkg->price }}
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>

                <button type="submit" class="submit-btn" id="hotspotBtn">
                    <span>Continue to Payment</span>
                    <i class="fas fa-arrow-right"></i>
                    <div class="loader"></div>
                </button>
            </form>
        </div>

        <div class="powered-by">
            Powered by <span>Marcep Agency</span>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:40px; border-radius:var(--radius); text-align:center; max-width:400px; animation:modalIn 0.3s ease;">
        <div style="width:80px; height:80px; background:linear-gradient(135deg, #10b981, #059669); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
            <i class="fas fa-check" style="color:white; font-size:36px;"></i>
        </div>
        <h2 style="margin-bottom:10px;">Connected Successfully!</h2>
        <p style="color:var(--text-light); margin-bottom:30px;">You are now connected to the internet.</p>
        <button onclick="closeModal()" style="background:var(--primary); color:white; border:none; padding:12px 32px; border-radius:var(--radius-sm); cursor:pointer; font-weight:600;">Continue Browsing</button>
    </div>
</div>

<script>
    function showTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.tabs button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabId + 'Content').classList.add('active');
        document.getElementById(tabId + 'Tab').classList.add('active');
        
        // Move slider
        const slider = document.getElementById('slider');
        if (tabId === 'metered') {
            slider.style.transform = 'translateX(0)';
        } else {
            slider.style.transform = 'translateX(100%)';
        }
    }

    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.querySelector('.password-toggle i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.classList.remove('fa-eye');
            toggleBtn.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleBtn.classList.remove('fa-eye-slash');
            toggleBtn.classList.add('fa-eye');
        }
    }

    function showForgotPassword() {
        alert('Please contact your network administrator to reset your password.');
    }

    // Package selection
    document.querySelectorAll('.package-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.classList.contains('password-toggle')) {
                // Remove selected class from all packages
                document.querySelectorAll('.package-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // Add selected class to clicked package
                this.classList.add('selected');
                
                // Update radio button
                const radio = this.querySelector('.package-radio');
                radio.checked = true;
            }
        });
    });

    // Form submission with loading state
    document.getElementById('meteredForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('meteredBtn');
        const span = btn.querySelector('span');
        const icon = btn.querySelector('.fa-sign-in-alt');
        const loader = btn.querySelector('.loader');
        
        span.textContent = 'Connecting...';
        icon.style.display = 'none';
        loader.style.display = 'block';
        btn.disabled = true;
        
        // Simulate loading for demo
        setTimeout(() => {
            // Show success modal (in real app, this would be after successful auth)
            // document.getElementById('successModal').style.display = 'flex';
            
            // Reset button
            span.textContent = 'Connect';
            icon.style.display = 'inline-block';
            loader.style.display = 'none';
            btn.disabled = false;
        }, 2000);
    });

    document.getElementById('hotspotForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('hotspotBtn');
        const span = btn.querySelector('span');
        const icon = btn.querySelector('.fa-arrow-right');
        const loader = btn.querySelector('.loader');
        
        span.textContent = 'Processing...';
        icon.style.display = 'none';
        loader.style.display = 'block';
        btn.disabled = true;
        
        // Simulate loading for demo
        setTimeout(() => {
            // Show success modal (in real app, this would be after payment)
            // document.getElementById('successModal').style.display = 'flex';
            
            // Reset button
            span.textContent = 'Continue to Payment';
            icon.style.display = 'inline-block';
            loader.style.display = 'none';
            btn.disabled = false;
        }, 2000);
    });

    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
    }

    // Add animation to page load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.container').style.opacity = '0';
        document.querySelector('.container').style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            document.querySelector('.container').style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            document.querySelector('.container').style.opacity = '1';
            document.querySelector('.container').style.transform = 'translateY(0)';
        }, 100);
    });

    // Auto-focus username field on metered tab
    showTab('metered');
    document.getElementById('username').focus();
</script>

</body>
</html>