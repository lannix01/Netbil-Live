<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NetBil Login</title>
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

  @vite('resources/css/app.css')
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --primary:#10b981;
      --primary-dark:#059669;
      --primary-light:#d1fae5;
      --text:#111827;
      --text-light:#6b7280;
      --bg:#f9fafb;
      --card-bg:rgba(255,255,255,.96);
      --error:#ef4444;
      --success:#10b981;

      --ring: rgba(16,185,129,.16);
      --shadow: 0 10px 25px -5px rgba(0,0,0,.10), 0 10px 10px -5px rgba(0,0,0,.04);
    }

    *{box-sizing:border-box}
    body{
      font-family:'Inter',sans-serif;
      background: radial-gradient(1200px 800px at 10% 10%, rgba(16,185,129,.18), transparent 60%),
                  radial-gradient(900px 700px at 90% 20%, rgba(59,130,246,.14), transparent 55%),
                  linear-gradient(135deg,#f3f4f6 0%,#e5e7eb 100%);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:1rem;
      color:var(--text);
      overflow-x:hidden;
    }

    /* subtle drifting grid/noise vibe */
    .bg-grid{
      position:fixed; inset:0;
      pointer-events:none;
      opacity:.28;
      background:
        linear-gradient(to right, rgba(17,24,39,.06) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(17,24,39,.06) 1px, transparent 1px);
      background-size: 56px 56px;
      mask-image: radial-gradient(closest-side at 50% 30%, rgba(0,0,0,.85), transparent 72%);
      animation: gridFloat 10s ease-in-out infinite;
    }
    @keyframes gridFloat{
      0%,100%{transform:translateY(0)}
      50%{transform:translateY(-10px)}
    }

    .login-card{
      max-width:460px;
      width:100%;
      background:var(--card-bg);
      border-radius:1.25rem;
      padding:2.4rem;
      box-shadow:var(--shadow);
      border:1px solid rgba(229,231,235,.9);
      position:relative;
      animation: fadeInUp .6s cubic-bezier(.22,1,.36,1);
      overflow:hidden;
      isolation:isolate;
    }

    /* top accent */
    .login-card::before{
      content:"";
      position:absolute;
      inset:-2px -2px auto -2px;
      height:78px;
      background: linear-gradient(90deg, rgba(16,185,129,.18), rgba(59,130,246,.14));
      filter: blur(0px);
      z-index:-1;
    }

    .login-header h2{
      font-size:1.85rem;
      font-weight:800;
      letter-spacing:-.02em;
      background:linear-gradient(90deg,var(--primary),#3b82f6);
      -webkit-background-clip:text;
      color:transparent;
      margin:0 0 .25rem 0;
    }

    .login-header p{
      color:var(--text-light);
      margin:0 0 1.6rem 0;
      line-height:1.4;
    }

    .input-group{position:relative;margin-bottom:1.25rem}

    .form-input{
      width:100%;
      padding:1rem;
      padding-top:1.55rem;
      border:1px solid #e5e7eb;
      border-radius:.65rem;
      background:#f9fafb;
      transition: border-color .2s ease, box-shadow .2s ease, background .2s ease, transform .15s ease;
      font-size:.95rem;
    }

    .form-input:focus{
      outline:none;
      border-color:var(--primary);
      box-shadow:0 0 0 4px var(--ring);
      background:#fff;
      transform: translateY(-1px);
    }

    .floating-label{
      position:absolute;
      top:1.02rem;
      left:1rem;
      font-size:.875rem;
      color:var(--text-light);
      pointer-events:none;
      transition: all .18s ease;
      transform-origin:left top;
    }

    .form-input:focus + .floating-label,
    .form-input:not(:placeholder-shown) + .floating-label{
      transform: translateY(-.78rem) scale(.85);
      color:var(--primary-dark);
      font-weight:600;
    }

    .toggle-password{
      position:absolute;
      right:.9rem;
      top:50%;
      transform:translateY(-50%);
      cursor:pointer;
      color:#9ca3af;
      user-select:none;
      font-size:1rem;
      padding:.35rem .45rem;
      border-radius:.5rem;
      transition: background .15s ease, transform .15s ease, color .15s ease;
    }

    .toggle-password:hover{
      background: rgba(17,24,39,.05);
      color:#6b7280;
      transform: translateY(-50%) scale(1.03);
    }

    /* feedback row under inputs */
    .hint-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:.75rem;
      margin-top:-.25rem;
      margin-bottom:1rem;
      font-size:.82rem;
      color:var(--text-light);
    }

    .caps-badge{
      display:inline-flex;
      align-items:center;
      gap:.4rem;
      padding:.35rem .55rem;
      border-radius:999px;
      border:1px solid rgba(229,231,235,.9);
      background: rgba(249,250,251,.9);
      opacity:0;
      transform: translateY(-4px);
      transition: opacity .2s ease, transform .2s ease;
      white-space:nowrap;
    }
    .caps-badge.on{
      opacity:1;
      transform: translateY(0);
    }
    .caps-dot{
      width:.5rem;height:.5rem;border-radius:999px;
      background: var(--error);
      box-shadow: 0 0 0 4px rgba(239,68,68,.10);
    }

    .submit-btn{
      width:100%;
      padding:.95rem 1rem;
      background: linear-gradient(90deg, var(--primary), #22c55e);
      color:#fff;
      border:none;
      border-radius:.75rem;
      font-weight:800;
      cursor:pointer;
      transition: transform .12s ease, filter .2s ease, box-shadow .2s ease;
      box-shadow: 0 10px 18px rgba(16,185,129,.18);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:.6rem;
      position:relative;
      overflow:hidden;
    }
    .submit-btn:hover{filter:saturate(1.05) brightness(1.02); transform: translateY(-1px);}
    .submit-btn:active{transform: translateY(0);}
    .submit-btn:disabled{
      cursor:not-allowed;
      filter: grayscale(.1) brightness(.95);
      opacity:.88;
      box-shadow:none;
      transform:none;
    }

    /* button shine */
    .submit-btn::before{
      content:"";
      position:absolute;
      inset:-40% auto -40% -60%;
      width:50%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.45), transparent);
      transform: rotate(12deg);
      opacity:0;
    }
    .submit-btn:hover::before{
      opacity:1;
      animation: shine 1.2s ease;
    }
    @keyframes shine{
      from{transform: translateX(-10%) rotate(12deg)}
      to{transform: translateX(260%) rotate(12deg)}
    }

    /* progress / status area */
    .status-wrap{
      margin-top:1rem;
      border-radius:.9rem;
      border:1px solid rgba(229,231,235,.9);
      background: rgba(249,250,251,.8);
      padding:.85rem .9rem;
      display:none;
      gap:.75rem;
      align-items:flex-start;
    }
    .status-wrap.show{display:flex; animation: popIn .25s ease;}
    @keyframes popIn{from{opacity:0; transform:translateY(-6px)} to{opacity:1; transform:translateY(0)}}

    .spinner{
      width:18px;height:18px;border-radius:999px;
      border:2px solid rgba(16,185,129,.25);
      border-top-color: rgba(16,185,129,.95);
      animation: spin .75s linear infinite;
      margin-top:.1rem;
      flex:0 0 auto;
    }
    @keyframes spin{to{transform:rotate(360deg)}}

    .status-text{
      flex:1 1 auto;
      min-width:0;
    }
    .status-title{
      font-weight:800;
      font-size:.9rem;
      margin:0;
      color:#0f172a;
    }
    .status-sub{
      margin:.2rem 0 0 0;
      font-size:.82rem;
      color:var(--text-light);
      line-height:1.35;
    }

    .progress{
      margin-top:.65rem;
      height:10px;
      border-radius:999px;
      background: rgba(17,24,39,.07);
      overflow:hidden;
      position:relative;
    }
    .bar{
      height:100%;
      width:0%;
      border-radius:999px;
      background: linear-gradient(90deg, rgba(16,185,129,.95), rgba(59,130,246,.85));
      transition: width .22s ease;
    }
    .bar.indeterminate{
      width:40%;
      animation: indet 1.05s ease-in-out infinite;
    }
    @keyframes indet{
      0%{transform:translateX(-40%)}
      50%{transform:translateX(90%)}
      100%{transform:translateX(220%)}
    }

    .mini{
      font-size:.82rem;
      color:var(--text-light);
      margin-top:.75rem;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:.35rem;
      opacity:.95;
    }
    .mini kbd{
      font-family: inherit;
      font-weight:800;
      padding:.1rem .45rem;
      border-radius:.45rem;
      border:1px solid rgba(229,231,235,.95);
      background:#fff;
      box-shadow: 0 2px 8px rgba(16,24,40,.06);
    }

    .auth-footer{
      margin-top:1.55rem;
      text-align:center;
      font-size:.9rem;
      color:var(--text-light);
    }
    .auth-footer a{
      color:var(--primary-dark);
      font-weight:800;
      text-decoration:none;
    }
    .auth-footer a:hover{text-decoration:underline}

    /* fun micro animation on header icon */
    .header-row{
      display:flex;
      align-items:center;
      gap:.75rem;
      margin-bottom:.65rem;
    }
    .logo-bubble{
      width:44px;height:44px;border-radius:14px;
      background: linear-gradient(135deg, rgba(16,185,129,.18), rgba(59,130,246,.14));
      display:flex;align-items:center;justify-content:center;
      box-shadow: 0 10px 18px rgba(16,185,129,.10);
      border: 1px solid rgba(229,231,235,.9);
      position:relative;
      overflow:hidden;
    }
    .logo-bubble span{
      font-size:1.25rem;
      filter: drop-shadow(0 6px 10px rgba(0,0,0,.10));
      animation: bob 2.8s ease-in-out infinite;
    }
    @keyframes bob{
      0%,100%{transform:translateY(0)}
      50%{transform:translateY(-2px)}
    }

    /* shake invalid */
    .shake{
      animation: shake .35s ease;
    }
    @keyframes shake{
      0%{transform:translateX(0)}
      25%{transform:translateX(-7px)}
      50%{transform:translateX(7px)}
      75%{transform:translateX(-5px)}
      100%{transform:translateX(0)}
    }

    /* reduces motion */
    @media (prefers-reduced-motion: reduce){
      *{animation:none !important; transition:none !important}
    }

    @keyframes fadeInUp{
      from{opacity:0; transform:translateY(18px)}
      to{opacity:1; transform:translateY(0)}
    }
  </style>
</head>

<body>
  <div class="bg-grid" aria-hidden="true"></div>

  <div class="login-card" id="loginCard">
    <div class="login-header">
      <div class="header-row">
        {{-- <div class="logo-bubble" aria-hidden="true"><span>🔐</span></div> --}}
        <div>
          <h2>Log In</h2>
          <p>Sign in to your account</p>
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
      @csrf

      <div class="input-group">
        <input
          id="email"
          name="email"
          type="email"
          value="{{ old('email') }}"
          required
          autofocus
          placeholder=" "
          class="form-input"
          autocomplete="username"
        >
        <label class="floating-label" for="email">Email Address</label>
      </div>

      <div class="input-group">
        <input
          id="password"
          name="password"
          type="password"
          required
          placeholder=" "
          class="form-input"
          autocomplete="current-password"
        >
        <label class="floating-label" for="password">Password</label>
        <span class="toggle-password" id="togglePasswordBtn" title="Show/Hide password" aria-label="Toggle password visibility">👁</span>
      </div>

      <div class="hint-row">
        <div style="display:flex; align-items:center; gap:.5rem;">
          <span id="strengthText">Password: <strong style="font-weight:800;">—</strong></span>
        </div>
        <div class="caps-badge" id="capsBadge" role="status" aria-live="polite">
          <span class="caps-dot"></span>
          Caps Lock is ON
        </div>
      </div>

      <button type="submit" class="submit-btn" id="submitBtn">
        {{-- <span id="btnIcon" aria-hidden="true">➡️</span> --}}
        <span id="btnText">Sign In</span>
      </button>

      <!-- Animated status area (shown after submit) -->
      <div class="status-wrap" id="statusWrap" aria-live="polite" aria-atomic="true">
        <div class="spinner" id="spinner" aria-hidden="true"></div>
        <div class="status-text">
          <p class="status-title" id="statusTitle">Logging you in…</p>
          <p class="status-sub" id="statusSub">Preparing a secure session.</p>
          <div class="progress" aria-hidden="true">
            <div class="bar indeterminate" id="progressBar"></div>
          </div>
        </div>
      </div>

      {{-- <div class="mini" aria-hidden="true">
        Tip: Press <kbd>Enter</kbd> to submit
      </div> --}}
    </form>

    {{-- REGISTER + FORGOT --}}
    <div class="auth-footer">
      @if (Route::has('password.request'))
        <div>
          <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>
      @endif

      @if (Route::has('register'))
        <div style="margin-top: 0.75rem;">
          Don’t have an account?
          <a href="{{ route('register') }}">Register</a>
        </div>
      @endif
    </div>
  </div>

  <script>
    // ---------- utilities ----------
    function $(id){ return document.getElementById(id); }

    function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }

    // ---------- password toggle ----------
    function togglePassword() {
      const input = $('password');
      const btn = $('togglePasswordBtn');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.textContent = isHidden ? '🙈' : '👁';
      btn.title = isHidden ? 'Hide password' : 'Show password';
      btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    }

    $('togglePasswordBtn').addEventListener('click', togglePassword);

    // ---------- caps lock detection ----------
    const capsBadge = $('capsBadge');
    function updateCapsBadge(e){
      const caps = e.getModifierState && e.getModifierState('CapsLock');
      if (caps) capsBadge.classList.add('on');
      else capsBadge.classList.remove('on');
    }

    $('password').addEventListener('keydown', updateCapsBadge);
    $('password').addEventListener('keyup', updateCapsBadge);

    // ---------- playful password strength - UI hint) ----------
    const strengthText = $('strengthText');

    function strengthScore(pw){
      // super simple heuristic: length + variety
      let s = 0;
      if (!pw) return 0;
      s += clamp(pw.length, 0, 14);
      if (/[a-z]/.test(pw)) s += 3;
      if (/[A-Z]/.test(pw)) s += 3;
      if (/\d/.test(pw)) s += 3;
      if (/[^A-Za-z0-9]/.test(pw)) s += 3;
      // penalize very short
      if (pw.length < 8) s -= 4;
      return clamp(s, 0, 26);
    }

    function strengthLabel(score){
      if (score === 0) return { label:'—', emoji:'🫥' };
      if (score <= 8) return { label:'Weak', emoji:'😬' };
      if (score <= 15) return { label:'Okay', emoji:'🙂' };
      if (score <= 21) return { label:'Strong', emoji:'😎' };
      return { label:'Beast', emoji:'🦾' };
    }

    $('password').addEventListener('input', () => {
      const pw = $('password').value;
      const sc = strengthScore(pw);
      const {label, emoji} = strengthLabel(sc);
      strengthText.innerHTML = `Password: <strong style="font-weight:800;">${label}</strong> <span aria-hidden="true">${emoji}</span>`;
    });

    // ---------- button micro-interactions ----------
    const submitBtn = $('submitBtn');
    const btnText = $('btnText');
    const btnIcon = $('btnIcon');

    function setBtnState(state){
      // states: idle, working
      if (state === 'working'){
        submitBtn.disabled = true;
        btnText.textContent = 'Signing in…';
        btnIcon.textContent = '⏳';
      } else {
        submitBtn.disabled = false;
        btnText.textContent = 'Sign In';
        btnIcon.textContent = '➡️';
      }
    }

    // ---------- animated status flow ----------
    const statusWrap = $('statusWrap');
    const statusTitle = $('statusTitle');
    const statusSub = $('statusSub');
    const progressBar = $('progressBar');
    const loginCard = $('loginCard');

    let stageTimers = [];

    function clearStageTimers(){
      stageTimers.forEach(t => clearTimeout(t));
      stageTimers = [];
    }

    function showStatus(){
      statusWrap.classList.add('show');
    }

    function setStage(title, sub, opts = {}){
      statusTitle.textContent = title;
      statusSub.textContent = sub;

      if (opts.determinate){
        progressBar.classList.remove('indeterminate');
        progressBar.style.transform = 'translateX(0)';
        progressBar.style.width = (opts.percent ?? 0) + '%';
      } else {
        progressBar.classList.add('indeterminate');
        progressBar.style.width = '40%';
      }
    }

    function runFakeLoginFlow(){
      //  auth happens server-side after submit.
      //  form submit to proceed.
      showStatus();
      setStage('Logging you in…', 'Preparing a secure session.', { determinate:false });

      // determinately fill a little to look purposeful, then indeterminate again
      stageTimers.push(setTimeout(() => {
        setStage('Verifying credentials…', 'Checking your email and password.', { determinate:true, percent: 35 });
      }, 380));

      stageTimers.push(setTimeout(() => {
        setStage('Verifying credentials…', 'Confirming access level (admin/customer).', { determinate:true, percent: 62 });
      }, 820));

      stageTimers.push(setTimeout(() => {
        setStage('Almost there…', 'Fetching your dashboard and permissions.', { determinate:false });
      }, 1200));
    }

    // ---------- form submit handling ----------
    const form = $('loginForm');

    form.addEventListener('submit', (e) => {
      // Basic front-end validation before we do UI flow
      const email = $('email');
      const password = $('password');

      const emailOk = email.value && email.checkValidity();
      const passOk = password.value && password.value.length > 0;

      if (!emailOk || !passOk){
        e.preventDefault();
        loginCard.classList.remove('shake');
        // restart animation
        void loginCard.offsetWidth;
        loginCard.classList.add('shake');

        // Show a helpful status message
        showStatus();
        setStage('Fix the fields 🧰', !emailOk ? 'Enter a valid email address.' : 'Enter your password to continue.', { determinate:true, percent: 18 });
        setBtnState('idle');
        return;
      }

      // At this point we allow the form to submit for real.
      // We still run a quick “calming” flow immediately before navigation.
      clearStageTimers();
      setBtnState('working');
      runFakeLoginFlow();

      // Don’t prevent default: the POST will continue.
      // If the server returns validation errors and re-renders,
      // user will see normal errors (add them in Blade if needed).
    });

    // ---------- extra: keyboard shortcut to toggle pw visibility ----------
    // Ctrl+Shift+P toggles password visibility (nice nerdy power-user move)
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey && e.shiftKey && (e.key === 'P' || e.key === 'p')){
        e.preventDefault();
        togglePassword();
      }
    });
  </script>
</body>
</html>
