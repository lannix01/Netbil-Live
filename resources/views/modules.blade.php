<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Netbil — Platform</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f5f1e8;
      --bg-ink: #e8dfcf;
      --surface: #fffcf6;
      --surface-tint: #f0f6f1;
      --border: rgba(24, 52, 39, .14);
      --border-dark: rgba(24, 52, 39, .45);
      --text: #14241d;
      --text-soft: #3f4f47;
      --text-muted: #6b756f;
      --accent: #1b7a57;
      --accent-ink: #0b3f2b;
      --accent-soft: #e1f1e8;
      --logo-plate: #0e2b21;
      --logo-plate-edge: rgba(14, 43, 33, .55);
      --shadow: 0 26px 60px rgba(18, 34, 26, .14);
      --radius-lg: 22px;
      --radius-xl: 28px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background:
        radial-gradient(circle at top left, rgba(31, 122, 88, .15), transparent 40%),
        radial-gradient(circle at top right, rgba(176, 140, 86, .12), transparent 45%),
        linear-gradient(180deg, #fdfaf4 0%, var(--bg) 55%, var(--bg-ink) 100%);
      color: var(--text);
      font-family: "Manrope", system-ui, sans-serif;
      font-size: 14.5px;
      min-height: 100vh;
    }

    .page {
      max-width: 1160px;
      margin: 0 auto;
      padding: 52px 24px 68px;
    }

    /* ── HEADER ─────────────────────────── */
    .header {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 30px 34px 32px;
      margin-bottom: 42px;
      opacity: 0;
      transform: translateY(14px);
      animation: rise .5s ease forwards;
      box-shadow: var(--shadow);
      display: grid;
      grid-template-columns: minmax(240px, 320px) 1fr;
      grid-template-areas:
        "brand hero"
        "brand sub";
      gap: 22px 28px;
      position: relative;
      overflow: hidden;
    }
    .header::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 85% 20%, rgba(31, 122, 88, .08), transparent 55%),
        radial-gradient(circle at 15% 85%, rgba(176, 140, 86, .12), transparent 60%);
      pointer-events: none;
    }

    .wordmark-row {
      grid-area: brand;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 14px;
      flex-wrap: nowrap;
      z-index: 1;
    }

    .logo-box {
      width: 300px; height: 180px;
      border: 1px solid var(--logo-plate-edge);
      display: grid;
      place-items: center;
      overflow: hidden;
      background:
        radial-gradient(circle at 30% 20%, rgba(255, 255, 255, .16), transparent 60%),
        linear-gradient(160deg, #1a4736 0%, var(--logo-plate) 60%, #071a13 100%);
      border-radius: 18px;
      box-shadow: 0 14px 24px rgba(10, 26, 19, .3);
    }
    .logo-box img {
      width: 100%; height: 100%;
      object-fit: contain;
      padding: 10px;
      filter: drop-shadow(0 8px 14px rgba(0, 0, 0, .35));
    }
    .logo-box-fallback {
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
    }

    .wordmark-text {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .wordmark-label {
      font-size: 10px;
      letter-spacing: .2em;
      text-transform: uppercase;
      color: var(--accent-ink);
      white-space: nowrap;
    }
    .wordmark-name {
      font-family: "Space Grotesk", system-ui, sans-serif;
      font-size: 18px;
      font-weight: 700;
      color: var(--text);
      white-space: nowrap;
    }

    .divider-line { display: none; }

    .status-pill {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 11px;
      letter-spacing: .1em;
      color: var(--accent-ink);
      text-transform: uppercase;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid rgba(31, 122, 88, .2);
      background: var(--accent-soft);
      white-space: nowrap;
    }
    .dot-live {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--accent);
      animation: blink 1.8s step-start infinite;
    }

    .headline {
      font-family: "Space Grotesk", system-ui, sans-serif;
      font-size: clamp(30px, 3.6vw, 46px);
      font-weight: 700;
      letter-spacing: -.03em;
      line-height: 1.1;
      margin-bottom: 12px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      grid-area: hero;
      z-index: 1;
    }
    .headline-accent {
      color: var(--accent-ink);
    }
    .tagline {
      font-size: 14px;
      color: var(--text-soft);
      max-width: 720px;
      line-height: 1.7;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      grid-area: sub;
      z-index: 1;
    }

    /* ── GRID ───────────────────────────── */
    .grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
    }

    .card {
      background: var(--surface);
      padding: 28px 24px;
      display: flex;
      flex-direction: column;
      gap: 18px;
      text-decoration: none;
      color: inherit;
      opacity: 0;
      transform: translateY(16px);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: 0 16px 30px rgba(18, 34, 26, .08);
      position: relative;
      overflow: hidden;
    }
    .card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--accent), rgba(176, 140, 86, .8));
      opacity: .7;
    }
    .card:hover {
      transform: translateY(-4px);
      border-color: rgba(31, 122, 88, .3);
      box-shadow: 0 22px 36px rgba(18, 34, 26, .12);
      background: var(--surface-tint);
    }
    .card:hover .card-action-arrow { transform: translate(3px, -3px); }
    .card:hover .card-title { color: var(--accent-ink); }
    .card:hover .card-icon { border-color: var(--accent); color: var(--accent-ink); }

    .card.anim-1 { animation: rise .45s ease .12s forwards; }
    .card.anim-2 { animation: rise .45s ease .22s forwards; }
    .card.anim-3 { animation: rise .45s ease .32s forwards; }

    .card-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
    }

    .card-icon {
      width: 40px; height: 40px;
      border: 1px solid rgba(31, 122, 88, .4);
      display: grid;
      place-items: center;
      background: var(--accent-soft);
      color: var(--accent-ink);
      border-radius: 12px;
    }

    .card-tag {
      font-size: 10px;
      letter-spacing: .16em;
      text-transform: uppercase;
      color: var(--accent-ink);
      padding-top: 2px;
      white-space: nowrap;
      background: var(--accent-soft);
      border: 1px solid rgba(31, 122, 88, .2);
      padding: 6px 10px;
      border-radius: 999px;
    }

    .card-title {
      font-family: "Space Grotesk", system-ui, sans-serif;
      font-size: 20px;
      font-weight: 700;
      letter-spacing: -.02em;
      margin-bottom: 6px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .card-desc {
      font-size: 13px;
      line-height: 1.65;
      color: var(--text-soft);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .card-features {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
      flex: 1;
    }
    .card-features li {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 12.5px;
      color: var(--text-soft);
      line-height: 1.5;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .feat-dash {
      flex-shrink: 0;
      color: var(--text-muted);
    }

    .card-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 16px;
      border-top: 1px solid rgba(31, 122, 88, .18);
    }
    .card-cta {
      font-size: 12px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--accent-ink);
      white-space: nowrap;
    }
    .card-action-arrow {
      transition: transform .18s ease;
      color: var(--accent-ink);
    }

    /* ── FOOTER ─────────────────────────── */
    .footer {
      margin-top: 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 20px;
      border-top: 1px solid rgba(31, 122, 88, .18);
      opacity: 0;
      animation: rise .4s ease .5s forwards;
    }
    .footer-left {
      font-size: 11px;
      color: var(--text-muted);
      letter-spacing: .1em;
      text-transform: uppercase;
    }
    .footer-right {
      font-size: 11px;
      color: var(--text-muted);
      letter-spacing: .1em;
      text-transform: uppercase;
    }

    /* ── ANIMATIONS ─────────────────────── */
    @keyframes rise {
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0; }
    }

    /* ── RESPONSIVE ─────────────────────── */
    @media (max-width: 860px) {
      .grid { grid-template-columns: 1fr 1fr; }
      .header { grid-template-columns: 1fr; grid-template-areas: "brand" "hero" "sub"; }
      .headline { font-size: 28px; }
      .logo-box { width: 104px; height: 104px; }
    }
    @media (max-width: 540px) {
      .grid { grid-template-columns: 1fr; }
      .page { padding: 32px 16px 52px; }
      .headline { font-size: 26px; }
      .footer { flex-direction: column; align-items: flex-start; gap: 8px; }
      .logo-box { width: 96px; height: 96px; }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- HEADER -->
  <div class="header">
    <div class="wordmark-row">
      <div class="logo-box">
        <img src="/assets/images/marcepagency transparent.png" alt="Marcep"
             onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
        <div class="logo-box-fallback" style="display:none">M</div>
      </div>
      <div class="wordmark-text">
        <span class="wordmark-label">Marcep Platform</span>
        <span class="wordmark-name">Marcep Agency</span>
      </div>
      <div class="divider-line"></div>
      <div class="status-pill">
        <span class="dot-live"></span>
        All systems live
      </div>
    </div>

    <h1 class="headline">
      <span>Choose a module</span>
      <span class="headline-accent">to continue.</span>
    </h1>
    <p class="tagline">Three active systems under Netbil. Each opens its own workspace</p>
  </div>

  <!-- CARDS -->
  <div class="grid">

    <!-- Billing -->
    <a class="card anim-1" href="/login">
      <div class="card-head">
        <div class="card-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
          </svg>
        </div>
        <span class="card-tag">Billing / ISP</span>
      </div>
      <div>
        <h2 class="card-title">Netbil Billing</h2>
        <p class="card-desc">Run customer accounts, invoice cycles, and payment collection from one control room built for daily ops.</p>
      </div>
      <ul class="card-features">
        <li><span class="feat-dash">—</span> Customer onboarding, plans, and service status</li>
        <li><span class="feat-dash">—</span> Invoices, receipts, reminders, and payment tracking</li>
        <li><span class="feat-dash">—</span> Reports, cash flow, and revenue visibility</li>
      </ul>
      <div class="card-footer">
        <span class="card-cta">Open Billing</span>
        <span class="card-action-arrow">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 17L17 7M17 7H7M17 7v10"/>
          </svg>
        </span>
      </div>
    </a>

    <!-- PettyCash -->
    <a class="card anim-2" href="/pettycash">
      <div class="card-head">
        <div class="card-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 6v2m0 8v2M9.5 9.5A2.5 2.5 0 0112 8h.5a2.5 2.5 0 010 5h-1a2.5 2.5 0 000 5H12a2.5 2.5 0 002.5-2"/>
          </svg>
        </div>
        <span class="card-tag">Petty Cash</span>
      </div>
      <div>
        <h2 class="card-title">PettyCash</h2>
        <p class="card-desc">Keep field spend controlled with fast approvals, clear accountability, and clean reconciliations.</p>
      </div>
      <ul class="card-features">
        <li><span class="feat-dash">—</span> Requests, approvals, and disbursement tracking</li>
        <li><span class="feat-dash">—</span> Receipts capture, settlement, and audit trail</li>
        <li><span class="feat-dash">—</span> Budget visibility for teams and supervisors</li>
      </ul>
      <div class="card-footer">
        <span class="card-cta">Open PettyCash</span>
        <span class="card-action-arrow">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 17L17 7M17 7H7M17 7v10"/>
          </svg>
        </span>
      </div>
    </a>

    <!-- Inventory -->
    <a class="card anim-3" href="/inventory">
      <div class="card-head">
        <div class="card-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="7" height="7" rx="1"/>
            <rect x="15" y="3" width="7" height="7" rx="1"/>
            <rect x="2" y="14" width="7" height="7" rx="1"/>
            <path d="M15 17h7M18.5 13.5v7"/>
          </svg>
        </div>
        <span class="card-tag">Inventory</span>
      </div>
      <div>
        <h2 class="card-title">Inventory</h2>
        <p class="card-desc">Track routers, stock, deployments, and technician activity with live site visibility.</p>
      </div>
      <ul class="card-features">
        <li><span class="feat-dash">—</span> Stock batches, in-store units, and faulty tracking</li>
        <li><span class="feat-dash">—</span> Deployments by site, tech assignments, and returns</li>
        <li><span class="feat-dash">—</span> Quick search across serials, sites, and batches</li>
      </ul>
      <div class="card-footer">
        <span class="card-cta">Open Inventory</span>
        <span class="card-action-arrow">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 17L17 7M17 7H7M17 7v10"/>
          </svg>
        </span>
      </div>
    </a>

  </div>

  <!-- FOOTER -->
  <div class="footer">
    <span class="footer-left">Netbil Platform &nbsp;·&nbsp; 3 active modules</span>
    <span class="footer-right">Marcep Agency</span>
  </div>

</div>
</body>
</html>
