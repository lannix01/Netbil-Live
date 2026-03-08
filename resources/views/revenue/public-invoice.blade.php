<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg: #f7f8fa;
            --surface: #ffffff;
            --border: #e4e7ec;
            --text: #111827;
            --muted: #6b7280;
            --primary: #2563eb;
            --success: #16a34a;
            --danger: #dc2626;
            --radius: 10px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 16px;
        }
        .head h1 {
            margin: 0;
            font-size: 1.4rem;
        }
        .head-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .muted {
            color: var(--muted);
            font-size: 0.88rem;
        }
        .card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            padding: 16px;
            margin-bottom: 14px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .k {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .v {
            font-weight: 700;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border-bottom: 1px solid var(--border);
            padding: 10px 8px;
            text-align: left;
            font-size: 0.9rem;
        }
        th {
            font-size: 0.76rem;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: .04em;
        }
        .input, .btn {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.9rem;
        }
        .input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        }
        .btn {
            cursor: pointer;
            font-weight: 700;
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }
        .btn:disabled {
            opacity: .7;
            cursor: not-allowed;
        }
        .btn-alt {
            background: #fff;
            color: var(--text);
            border-color: var(--border);
        }
        .row {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 10px;
        }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }
        .col-3 { grid-column: span 3; }
        .col-2 { grid-column: span 2; }
        .col-12 { grid-column: span 12; }
        .mono {
            font-family: Consolas, monospace;
            font-size: 0.83rem;
        }
        .toast-stack {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 3000;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 360px;
        }
        .toast {
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            border-radius: 10px;
            background: #fff;
            padding: 10px 12px;
            box-shadow: 0 10px 28px rgba(17, 24, 39, .16);
            animation: toast-in .2s ease;
        }
        .toast.success { border-left-color: var(--success); }
        .toast.error { border-left-color: var(--danger); }
        .toast-title { font-weight: 700; font-size: .88rem; margin-bottom: 2px; }
        .toast-msg { color: var(--muted); font-size: .84rem; white-space: pre-wrap; }
        @keyframes toast-in {
            from { transform: translateY(8px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .pay-modal {
            position: fixed;
            inset: 0;
            z-index: 2500;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .pay-modal.is-open { display: flex; }
        .pay-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .55);
        }
        .pay-modal-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 560px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 20px 44px rgba(15, 23, 42, .32);
        }
        .pay-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .pay-modal-title {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }
        .close-x {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 8px;
            background: #fff;
            cursor: pointer;
        }
        @media (max-width: 760px) {
            .grid-2 { grid-template-columns: 1fr; }
            .col-6, .col-4, .col-3, .col-2 { grid-column: span 12; }
        }
    </style>
</head>
<body>
@php
    $status = $invoice->invoice_status ?: $invoice->status ?: 'unpaid';
    $statusColors = [
        'paid' => ['bg' => '#e8f5e9', 'text' => '#2e7d32', 'icon' => 'bi-check-circle-fill'],
        'partial' => ['bg' => '#e3f2fd', 'text' => '#1565c0', 'icon' => 'bi-pie-chart-fill'],
        'overdue' => ['bg' => '#fff3e0', 'text' => '#ef6c00', 'icon' => 'bi-exclamation-triangle-fill'],
        'due' => ['bg' => '#ede9fe', 'text' => '#5b21b6', 'icon' => 'bi-calendar-event-fill'],
        'unpaid' => ['bg' => '#fce4ec', 'text' => '#c62828', 'icon' => 'bi-x-circle-fill'],
        'cancelled' => ['bg' => '#f5f5f5', 'text' => '#424242', 'icon' => 'bi-slash-circle'],
    ];
    $sc = $statusColors[$status] ?? ['bg' => '#f5f5f5', 'text' => '#424242', 'icon' => 'bi-question-circle'];
@endphp

<div class="wrap">
    <div class="head">
        <div>
            <h1><i class="bi bi-receipt"></i> Invoice Payment</h1>
            <div class="muted">Reference: <span class="mono">{{ $invoice->invoice_number }}</span></div>
        </div>
        <div class="head-actions">
            <span id="public-status-badge" class="badge" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }};">
                <i class="bi {{ $sc['icon'] }}"></i> {{ strtoupper($status) }}
            </span>
            <button id="open-public-pay-modal-top" class="btn" type="button" style="width:auto;">
                <i class="bi bi-phone"></i> Pay Now
            </button>
        </div>
    </div>

    <div class="card">
        <div class="grid-2">
            <div>
                <div class="k">Customer</div>
                <div class="v">{{ $customer?->name ?? ($customer?->username ?? 'N/A') }}</div>
            </div>
            <div>
                <div class="k">Phone</div>
                <div class="v" id="public-customer-phone">{{ $customer?->phone ?? 'N/A' }}</div>
            </div>
            <div>
                <div class="k">Issued</div>
                <div class="v" id="public-issued-date">{{ optional($invoice->issued_at ?: $invoice->created_at)->format('Y-m-d') }}</div>
            </div>
            <div>
                <div class="k">Due Date</div>
                <div class="v" id="public-due-date">{{ optional($invoice->due_date)->format('Y-m-d') ?: 'N/A' }}</div>
            </div>
            <div>
                <div class="k">Total Amount</div>
                <div class="v" id="public-total-amount">{{ $invoice->currency ?? 'KES' }} {{ number_format((float)($invoice->total_amount ?: $invoice->amount ?: 0), 2) }}</div>
            </div>
            <div>
                <div class="k">Balance</div>
                <div class="v" id="public-balance-amount">{{ $invoice->currency ?? 'KES' }} {{ number_format((float)($invoice->balance_amount ?: 0), 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="row">
            <div class="col-4">
                <div class="k">Selected Invoices</div>
                <div class="v" id="public-selected-count">0 invoice(s)</div>
            </div>
            <div class="col-4">
                <div class="k">Selected Total</div>
                <div class="v" id="public-selected-total">{{ $invoice->currency ?? 'KES' }} 0.00</div>
            </div>
            <div class="col-4">
                <div class="k">Open Invoices</div>
                <div class="v"><span id="public-open-count">{{ number_format((int)($summary['open_count'] ?? 0)) }}</span> invoice(s)</div>
                <div class="muted">Open total: <span id="public-open-total">{{ $invoice->currency ?? 'KES' }} {{ number_format((float)($summary['open_total'] ?? 0), 2) }}</span></div>
            </div>
            <div class="col-12">
                <button id="open-public-pay-modal" class="btn" type="button">
                    <i class="bi bi-phone"></i> Pay Selected Invoice(s)
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="k">Select Invoice(s) To Pay</div>
        <table>
            <thead>
                <tr>
                    <th style="width:26px;"><input type="checkbox" id="public-master"></th>
                    <th>Invoice</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody id="public-due-body">
                @forelse($dueInvoices as $due)
                    @php
                        $dueStatus = $due->invoice_status ?: $due->status ?: 'unpaid';
                        $bal = (float)($due->balance_amount ?: ($due->total_amount ?: $due->amount ?: 0));
                    @endphp
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                class="public-invoice-select"
                                value="{{ $due->id }}"
                                data-balance="{{ number_format($bal, 2, '.', '') }}"
                                checked
                            >
                        </td>
                        <td>
                            <div>{{ $due->invoice_number }}</div>
                            <div class="mono">#{{ $due->id }}</div>
                        </td>
                        <td>{{ optional($due->due_date)->format('Y-m-d') ?: 'N/A' }}</td>
                        <td>{{ strtoupper($dueStatus) }}</td>
                        <td>{{ $due->currency ?? 'KES' }} {{ number_format($bal, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No open invoices for this account.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="publicPayModal" class="pay-modal" aria-hidden="true">
    <div class="pay-modal-backdrop" data-close-public-modal="1"></div>
    <div class="pay-modal-card">
        <div class="pay-modal-head">
            <h3 class="pay-modal-title"><i class="bi bi-phone"></i> Request STK Payment</h3>
            <button type="button" class="close-x" data-close-public-modal="1"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="row">
            <div class="col-6">
                <label class="k" for="public-msisdn-modal">Phone Number</label>
                <input id="public-msisdn-modal" class="input" value="{{ $customer?->phone }}" placeholder="07XXXXXXXX or 2547XXXXXXXX">
            </div>
            <div class="col-3">
                <label class="k" for="public-amount-modal">Amount</label>
                <input id="public-amount-modal" class="input" type="number" min="1" step="0.01" value="0">
            </div>
            <div class="col-3">
                <label class="k" for="public-reference-modal">Reference</label>
                <input id="public-reference-modal" class="input mono" readonly value="INVREQ_AUTO">
            </div>
            <div class="col-12 d-flex" style="gap:8px;">
                <button id="public-pay-btn-modal" class="btn" type="button">Pay Now</button>
                <button id="public-select-all" class="btn btn-alt" type="button">Select All</button>
            </div>
        </div>
    </div>
</div>

<div id="public-toasts" class="toast-stack"></div>

<script>
const publicPayUrl = @json('/pay/invoices/' . rawurlencode((string)$publicToken) . '/request-payment');
const publicSnapshotUrl = @json('/pay/invoices/' . rawurlencode((string)$publicToken) . '/snapshot');
const megaPayStatusBase = '/api/megapay/status/';
const defaultCurrency = @json($invoice->currency ?: 'KES');
let publicSnapshotTimer = null;
let publicStatusPollTimer = null;

const PUBLIC_STATUS_STYLE = {
    paid:      { bg: '#e8f5e9', text: '#2e7d32', icon: 'bi-check-circle-fill', label: 'PAID' },
    partial:   { bg: '#e3f2fd', text: '#1565c0', icon: 'bi-pie-chart-fill', label: 'PARTIAL' },
    overdue:   { bg: '#fff3e0', text: '#ef6c00', icon: 'bi-exclamation-triangle-fill', label: 'OVERDUE' },
    due:       { bg: '#ede9fe', text: '#5b21b6', icon: 'bi-calendar-event-fill', label: 'DUE' },
    unpaid:    { bg: '#fce4ec', text: '#c62828', icon: 'bi-x-circle-fill', label: 'UNPAID' },
    cancelled: { bg: '#f5f5f5', text: '#424242', icon: 'bi-slash-circle', label: 'CANCELLED' },
};

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

function escapeHtml(value) {
    return (value ?? '').toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatMoney(currency, amount) {
    const n = Number(amount || 0);
    return `${(currency || defaultCurrency || 'KES').toUpperCase()} ${n.toFixed(2)}`;
}

function showToast(title, message, type = 'info') {
    const stack = document.getElementById('public-toasts');
    if (!stack) return;

    const el = document.createElement('div');
    el.className = `toast ${type === 'success' ? 'success' : (type === 'error' ? 'error' : '')}`.trim();
    el.innerHTML = `<div class="toast-title">${escapeHtml(title)}</div><div class="toast-msg">${escapeHtml(message)}</div>`;
    stack.appendChild(el);
    setTimeout(() => el.remove(), 2000);
}

function readSelections() {
    return Array.from(document.querySelectorAll('.public-invoice-select:checked')).map((el) => ({
        id: Number(el.value),
        balance: Number(el.dataset.balance || 0),
    }));
}

function makeRef(ids) {
    const d = new Date();
    const stamp = [
        d.getFullYear(),
        String(d.getMonth() + 1).padStart(2, '0'),
        String(d.getDate()).padStart(2, '0'),
        String(d.getHours()).padStart(2, '0'),
        String(d.getMinutes()).padStart(2, '0'),
        String(d.getSeconds()).padStart(2, '0'),
    ].join('');
    return `INVREQ_${stamp}_${ids.length || 0}INV`;
}

function syncTotal() {
    const rows = readSelections();
    const total = rows.reduce((sum, row) => sum + row.balance, 0);

    const selectedCount = document.getElementById('public-selected-count');
    const selectedTotal = document.getElementById('public-selected-total');
    const amountEl = document.getElementById('public-amount-modal');
    const referenceEl = document.getElementById('public-reference-modal');

    if (selectedCount) selectedCount.textContent = `${rows.length} invoice(s)`;
    if (selectedTotal) selectedTotal.textContent = formatMoney(defaultCurrency, total);
    if (amountEl && amountEl.dataset.edited !== '1') {
        amountEl.value = total.toFixed(2);
    }
    if (referenceEl) {
        referenceEl.value = makeRef(rows.map((r) => r.id));
    }

    const master = document.getElementById('public-master');
    const all = document.querySelectorAll('.public-invoice-select');
    if (master) {
        master.checked = all.length > 0 && Array.from(all).every((el) => el.checked);
    }
}

function setStatusBadge(status) {
    const badge = document.getElementById('public-status-badge');
    if (!badge) return;

    const s = PUBLIC_STATUS_STYLE[status] || {
        bg: '#f5f5f5',
        text: '#424242',
        icon: 'bi-question-circle',
        label: (status || 'UNKNOWN').toUpperCase(),
    };

    badge.style.background = s.bg;
    badge.style.color = s.text;
    badge.innerHTML = `<i class="bi ${s.icon}"></i> ${escapeHtml(s.label)}`;
}

function renderDueInvoices(rows) {
    const body = document.getElementById('public-due-body');
    if (!body) return;
    const selectedBefore = new Set(readSelections().map((row) => row.id));

    if (!Array.isArray(rows) || rows.length === 0) {
        body.innerHTML = '<tr><td colspan="5" class="muted">No open invoices for this account.</td></tr>';
        syncTotal();
        return;
    }

    body.innerHTML = rows.map((row) => {
        const id = Number(row.id || 0);
        const invoiceNumber = escapeHtml(row.invoice_number || '-');
        const dueDate = escapeHtml(row.due_date || 'N/A');
        const status = escapeHtml(String(row.status || 'unpaid').toUpperCase());
        const balance = Number(row.balance_amount || 0);
        const balanceStr = formatMoney(row.currency || defaultCurrency, balance);
        const checked = selectedBefore.size === 0 || selectedBefore.has(id);
        return `
            <tr>
                <td>
                    <input
                        type="checkbox"
                        class="public-invoice-select"
                        value="${id}"
                        data-balance="${balance.toFixed(2)}"
                        ${checked ? 'checked' : ''}
                    >
                </td>
                <td>
                    <div>${invoiceNumber}</div>
                    <div class="mono">#${id}</div>
                </td>
                <td>${dueDate}</td>
                <td>${status}</td>
                <td>${escapeHtml(balanceStr)}</td>
            </tr>
        `;
    }).join('');

    syncTotal();
}

function applySnapshot(data) {
    const invoice = data?.invoice || {};
    const summary = data?.summary || {};
    const dueInvoices = Array.isArray(data?.due_invoices) ? data.due_invoices : [];

    setStatusBadge(String(invoice.status || 'unpaid').toLowerCase());

    const totalEl = document.getElementById('public-total-amount');
    const balanceEl = document.getElementById('public-balance-amount');
    const issuedEl = document.getElementById('public-issued-date');
    const dueEl = document.getElementById('public-due-date');
    const openCountEl = document.getElementById('public-open-count');
    const openTotalEl = document.getElementById('public-open-total');
    const customerPhoneEl = document.getElementById('public-customer-phone');

    if (totalEl) totalEl.textContent = formatMoney(invoice.currency || defaultCurrency, invoice.total_amount || 0);
    if (balanceEl) balanceEl.textContent = formatMoney(invoice.currency || defaultCurrency, invoice.balance_amount || 0);
    if (issuedEl) issuedEl.textContent = invoice.issued_at || 'N/A';
    if (dueEl) dueEl.textContent = invoice.due_date || 'N/A';
    if (openCountEl) openCountEl.textContent = Number(summary.open_count || 0).toLocaleString();
    if (openTotalEl) openTotalEl.textContent = formatMoney(invoice.currency || defaultCurrency, summary.open_total || 0);
    if (customerPhoneEl && data?.customer?.phone) customerPhoneEl.textContent = data.customer.phone;

    renderDueInvoices(dueInvoices);
}

async function refreshSnapshot(silent = true) {
    try {
        const res = await fetch(publicSnapshotUrl, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });

        const text = await res.text();
        let json = null;
        try { json = JSON.parse(text); } catch (e) {}

        if (!res.ok || !json?.ok) {
            if (!silent) {
                showToast('Refresh failed', json?.message || 'Could not refresh invoice status.', 'error');
            }
            return;
        }

        applySnapshot(json);
    } catch (error) {
        if (!silent) {
            showToast('Network error', error.message || 'Could not refresh invoice status.', 'error');
        }
    }
}

function startSnapshotRefresh() {
    if (publicSnapshotTimer) {
        clearInterval(publicSnapshotTimer);
    }
    publicSnapshotTimer = setInterval(() => refreshSnapshot(true), 12000);
}

function openPayModal() {
    const rows = readSelections();
    if (!rows.length) {
        showToast('Selection required', 'Select at least one invoice before paying.', 'error');
        return;
    }

    const modal = document.getElementById('publicPayModal');
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
}

function closePayModal() {
    const modal = document.getElementById('publicPayModal');
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
}

async function pollMegaPaymentStatus(reference, attempts = 24) {
    if (!reference || attempts <= 0) {
        return;
    }

    if (publicStatusPollTimer) {
        clearTimeout(publicStatusPollTimer);
        publicStatusPollTimer = null;
    }

    try {
        const res = await fetch(`${megaPayStatusBase}${encodeURIComponent(reference)}`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        const text = await res.text();
        let json = null;
        try { json = JSON.parse(text); } catch (e) {}

        const status = String(json?.megapayment?.status || '').toLowerCase();
        if (status === 'completed') {
            showToast('Payment received', 'Your payment has been received and invoice balances are updating.', 'success');
            await refreshSnapshot(true);
            return;
        }

        if (['failed', 'cancelled', 'timeout', 'expired'].includes(status)) {
            showToast('Payment not completed', `Current payment status: ${status}.`, 'error');
            await refreshSnapshot(true);
            return;
        }
    } catch (_e) {
        // Continue retries silently.
    }

    publicStatusPollTimer = setTimeout(() => pollMegaPaymentStatus(reference, attempts - 1), 5000);
}

async function requestPayment() {
    const rows = readSelections();
    if (!rows.length) {
        showToast('Selection required', 'Select at least one invoice.', 'error');
        return;
    }

    const msisdn = (document.getElementById('public-msisdn-modal')?.value || '').trim();
    const amount = Number(document.getElementById('public-amount-modal')?.value || 0);

    if (!msisdn) {
        showToast('Phone required', 'Phone number is required.', 'error');
        return;
    }
    if (!Number.isFinite(amount) || amount <= 0) {
        showToast('Amount required', 'Amount must be above zero.', 'error');
        return;
    }

    const btn = document.getElementById('public-pay-btn-modal');
    if (btn) btn.disabled = true;

    try {
        const res = await fetch(publicPayUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(getCsrfToken() ? { 'X-CSRF-TOKEN': getCsrfToken() } : {}),
            },
            body: JSON.stringify({
                invoice_ids: rows.map((r) => r.id),
                msisdn,
                amount,
            }),
        });

        const text = await res.text();
        let json = null;
        try { json = JSON.parse(text); } catch (e) {}

        if (!res.ok || (json && json.ok === false)) {
            showToast('Payment request failed', json?.message || 'Could not request payment.', 'error');
            return;
        }

        const reference = json?.reference || '';
        const finalAmount = Number(json?.amount || amount);
        showToast('STK sent', `Reference: ${reference || 'AUTO'}\nAmount: ${formatMoney(defaultCurrency, finalAmount)}\nCheck your phone and enter M-Pesa PIN.`, 'success');
        closePayModal();
        setTimeout(() => refreshSnapshot(true), 2500);

        if (reference) {
            pollMegaPaymentStatus(reference, 24);
        }
    } catch (error) {
        showToast('Network error', error.message || 'Could not request payment.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

document.addEventListener('change', (event) => {
    if (event.target?.classList?.contains('public-invoice-select')) {
        syncTotal();
    }
});

document.getElementById('public-master')?.addEventListener('change', (event) => {
    const checked = !!event.target.checked;
    document.querySelectorAll('.public-invoice-select').forEach((el) => {
        el.checked = checked;
    });
    syncTotal();
});

document.getElementById('public-amount-modal')?.addEventListener('input', (event) => {
    event.target.dataset.edited = '1';
});

document.getElementById('public-select-all')?.addEventListener('click', () => {
    document.querySelectorAll('.public-invoice-select').forEach((el) => {
        el.checked = true;
    });
    const amountEl = document.getElementById('public-amount-modal');
    if (amountEl) {
        amountEl.dataset.edited = '0';
    }
    syncTotal();
});

document.getElementById('open-public-pay-modal')?.addEventListener('click', openPayModal);
document.getElementById('open-public-pay-modal-top')?.addEventListener('click', openPayModal);
document.getElementById('public-pay-btn-modal')?.addEventListener('click', requestPayment);

document.querySelectorAll('[data-close-public-modal="1"]').forEach((el) => {
    el.addEventListener('click', closePayModal);
});

syncTotal();
startSnapshotRefresh();
refreshSnapshot(true);
</script>
</body>
</html>
