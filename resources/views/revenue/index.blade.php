@extends('layouts.app')

@section('content')
@php
    $q = $filters['q'] ?? '';
    $status = $filters['status'] ?? '';
    $purpose = $filters['purpose'] ?? '';
    $channel = $filters['channel'] ?? '';
    $from = $filters['from'] ?? '';
    $to = $filters['to'] ?? '';
    $invoiceQ = $filters['invoiceQ'] ?? '';
    $invoiceStatus = $filters['invoiceStatus'] ?? '';
    $hasTransactionFilters = $q !== '' || $status !== '' || $purpose !== '' || $channel !== '' || $from !== '' || $to !== '';

    $statuses = ['pending','completed','failed','cancelled','timeout','expired'];
    $invoiceStatuses = ['unpaid','due','overdue','partial','paid','cancelled'];

    $statusConfig = [
        'completed' => ['bg' => '#e8f5e9', 'text' => '#2e7d32', 'icon' => 'bi-check-circle-fill', 'label' => 'Completed'],
        'pending'   => ['bg' => '#fff8e1', 'text' => '#f57f17', 'icon' => 'bi-hourglass-split',   'label' => 'Pending'],
        'failed'    => ['bg' => '#fce4ec', 'text' => '#c62828', 'icon' => 'bi-x-circle-fill',     'label' => 'Failed'],
        'cancelled' => ['bg' => '#f5f5f5', 'text' => '#424242', 'icon' => 'bi-slash-circle',      'label' => 'Cancelled'],
        'timeout'   => ['bg' => '#fff3e0', 'text' => '#e65100', 'icon' => 'bi-clock-history',     'label' => 'Timeout'],
        'expired'   => ['bg' => '#f3e5f5', 'text' => '#6a1b9a', 'icon' => 'bi-calendar-x',       'label' => 'Expired'],
    ];

    $invoiceStatusConfig = [
        'paid' => ['bg' => '#e8f5e9', 'text' => '#2e7d32', 'icon' => 'bi-check-circle-fill', 'label' => 'Paid'],
        'partial' => ['bg' => '#e3f2fd', 'text' => '#1565c0', 'icon' => 'bi-pie-chart-fill', 'label' => 'Partial'],
        'overdue' => ['bg' => '#fff3e0', 'text' => '#ef6c00', 'icon' => 'bi-exclamation-triangle-fill', 'label' => 'Overdue'],
        'due' => ['bg' => '#ede9fe', 'text' => '#5b21b6', 'icon' => 'bi-calendar-event-fill', 'label' => 'Due'],
        'unpaid' => ['bg' => '#fce4ec', 'text' => '#c62828', 'icon' => 'bi-x-circle-fill', 'label' => 'Unpaid'],
        'cancelled' => ['bg' => '#f5f5f5', 'text' => '#424242', 'icon' => 'bi-slash-circle', 'label' => 'Cancelled'],
    ];
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --rc-bg:        #f7f8fa;
        --rc-surface:   #ffffff;
        --rc-border:    #e4e7ec;
        --rc-text:      #1a1d23;
        --rc-muted:     #6b7280;
        --rc-accent:    #2563eb;
        --rc-radius:    6px;
    }

    body { background: var(--rc-bg); }

    /* ── Layout ─────────────────────────────────── */
    .rc-wrap { max-width: 1400px; margin: 0 auto; padding: 28px 20px; }

    /* ── Page header ─────────────────────────────── */
    .rc-header { border-bottom: 1px solid var(--rc-border); padding-bottom: 18px; margin-bottom: 24px; }
    .rc-header h1 { font-size: 1.35rem; font-weight: 700; color: var(--rc-text); margin: 0; }
    .rc-header p  { font-size: 0.85rem; color: var(--rc-muted); margin: 4px 0 0; }

    /* ── Stat cards ─────────────────────────────── */
    .rc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .rc-stat {
        background: var(--rc-surface);
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        padding: 18px 20px;
    }
    .rc-stat-label { font-size: 0.78rem; color: var(--rc-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
    .rc-stat-value { font-size: 1.65rem; font-weight: 700; color: var(--rc-text); line-height: 1.1; }
    .rc-stat-sub   { font-size: 0.78rem; color: var(--rc-muted); margin-top: 4px; }

    /* ── Main panel ──────────────────────────────── */
    .rc-panel {
        background: var(--rc-surface);
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
    }

    /* ── Tab nav ─────────────────────────────────── */
    .rc-tabs { display: flex; border-bottom: 1px solid var(--rc-border); overflow-x: auto; }
    .rc-tab {
        padding: 12px 18px;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--rc-muted);
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: color .15s, border-color .15s;
        margin-bottom: -1px;
    }
    .rc-tab:hover   { color: var(--rc-text); }
    .rc-tab.active  { color: var(--rc-accent); border-bottom-color: var(--rc-accent); font-weight: 600; }

    /* ── Tab body ─────────────────────────────────── */
    .rc-tab-body { padding: 24px; }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    /* ── Filter row ──────────────────────────────── */
    .rc-filter {
        background: var(--rc-bg);
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        padding: 16px;
        margin-bottom: 20px;
    }
    .rc-transactions-tools {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }
    .rc-active-filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e40af;
        border-radius: 999px;
        padding: 5px 11px;
        font-size: .78rem;
        font-weight: 700;
    }
    .rc-filter-modal .modal-dialog {
        max-width: 620px;
    }
    .rc-filter-modal .modal-body {
        background: #f8fafc;
    }
    @media (max-width: 767.98px) {
        .rc-transactions-tools {
            justify-content: stretch;
            flex-direction: column;
            align-items: stretch;
        }
        .rc-transactions-tools .rc-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* ── Form controls ───────────────────────────── */
    .rc-input, .rc-select {
        width: 100%;
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        padding: 7px 12px;
        font-size: 0.875rem;
        color: var(--rc-text);
        background: var(--rc-surface);
        transition: border-color .15s, box-shadow .15s;
    }
    .rc-input:focus, .rc-select:focus {
        border-color: var(--rc-accent);
        box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        outline: none;
    }
    .rc-label { font-size: 0.78rem; font-weight: 600; color: var(--rc-muted); margin-bottom: 5px; display: block; }

    /* ── Buttons ─────────────────────────────────── */
    .rc-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: var(--rc-radius);
        font-size: 0.875rem; font-weight: 500; cursor: pointer;
        border: 1px solid transparent; transition: all .15s;
        text-decoration: none;
    }
    .rc-btn-primary  { background: var(--rc-accent); color: #fff; border-color: var(--rc-accent); }
    .rc-btn-primary:hover  { background: #1d4ed8; color: #fff; }
    .rc-btn-ghost    { background: transparent; color: var(--rc-text); border-color: var(--rc-border); }
    .rc-btn-ghost:hover    { background: var(--rc-bg); }
    .rc-btn-sm { padding: 5px 10px; font-size: 0.8rem; }

    /* ── Purpose totals ──────────────────────────── */
    .rc-purpose-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
    .rc-purpose-chip {
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        padding: 10px 14px;
        background: var(--rc-surface);
        min-width: 160px;
    }
    .rc-purpose-chip-name { font-size: 0.78rem; font-weight: 600; color: var(--rc-muted); text-transform: uppercase; letter-spacing: .04em; }
    .rc-purpose-chip-val  { font-size: 1.05rem; font-weight: 700; color: var(--rc-text); margin-top: 2px; }
    .rc-purpose-chip-cnt  { font-size: 0.75rem; color: var(--rc-muted); margin-top: 1px; }

    /* ── Table ───────────────────────────────────── */
    .rc-table-wrap { overflow-x: auto; }
    .rc-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .rc-table th {
        padding: 10px 12px;
        text-align: left;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--rc-muted);
        border-bottom: 1px solid var(--rc-border);
        background: var(--rc-bg);
        white-space: nowrap;
    }
    .rc-table td {
        padding: 11px 12px;
        border-bottom: 1px solid var(--rc-border);
        vertical-align: middle;
        color: var(--rc-text);
    }
    .rc-table tbody tr:hover { background: #f9fafb; }
    .rc-table tbody tr:last-child td { border-bottom: none; }

    /* ── Status badge ─────────────────────────────── */
    .rc-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 100px;
        font-size: 0.75rem; font-weight: 600; white-space: nowrap;
    }

    /* ── Pill tag ─────────────────────────────────── */
    .rc-tag {
        display: inline-block;
        background: var(--rc-bg);
        border: 1px solid var(--rc-border);
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.78rem;
        color: var(--rc-muted);
    }

    /* ── Mono text ────────────────────────────────── */
    .rc-mono { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.8rem; }

    /* ── Section title ────────────────────────────── */
    .rc-section-title { font-size: 0.78rem; font-weight: 700; color: var(--rc-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

    /* ── Empty state ──────────────────────────────── */
    .rc-empty { text-align: center; padding: 56px 24px; color: var(--rc-muted); }
    .rc-empty i { font-size: 2.5rem; opacity: .35; display: block; margin-bottom: 12px; }
    .rc-empty h5 { font-size: 1rem; color: var(--rc-text); margin-bottom: 4px; }

    /* ── Form section (admin / hotspot / etc.) ────── */
    .rc-form-section { max-width: 860px; }
    .rc-form-desc { font-size: 0.85rem; color: var(--rc-muted); margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--rc-border); }
    .rc-form-desc code { background: var(--rc-bg); padding: 1px 5px; border-radius: 3px; font-size: .8rem; }
    .rc-request-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .rc-request-panel {
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        background: #fff;
        padding: 14px;
    }

    /* ── Modal ────────────────────────────────────── */
    .modal-content   { border: 1px solid var(--rc-border); border-radius: var(--rc-radius); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    .modal-header    { border-bottom: 1px solid var(--rc-border); padding: 16px 20px; }
    .modal-footer    { border-top: 1px solid var(--rc-border); padding: 12px 20px; }
    .modal-title     { font-size: 1rem; font-weight: 700; }

    .rc-detail-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media(max-width: 600px){ .rc-detail-grid { grid-template-columns: 1fr; } }

    .rc-detail-block { border: 1px solid var(--rc-border); border-radius: var(--rc-radius); padding: 14px 16px; background: var(--rc-bg); }
    .rc-detail-row   { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; font-size: 0.85rem; gap: 12px; }
    .rc-detail-row:last-child { margin-bottom: 0; }
    .rc-detail-key   { color: var(--rc-muted); font-size: 0.78rem; white-space: nowrap; }
    .rc-detail-val   { text-align: right; word-break: break-all; }
    .rc-detail-full  { border: 1px solid var(--rc-border); border-radius: var(--rc-radius); padding: 14px 16px; background: var(--rc-bg); margin-top: 12px; }
    .rc-modal-head-meta {
        margin-top: 2px;
        color: var(--rc-muted);
        font-size: .8rem;
    }
    .rc-insight-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }
    .rc-insight-card {
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        background: #fff;
        padding: 10px 12px;
    }
    .rc-insight-card .k {
        color: var(--rc-muted);
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 700;
    }
    .rc-insight-card .v {
        margin-top: 3px;
        font-size: 1.02rem;
        font-weight: 700;
        color: var(--rc-text);
        line-height: 1.2;
    }
    .rc-tech-wrap { margin-top: 12px; }
    .rc-tech-collapse {
        border: 1px dashed var(--rc-border);
        border-radius: var(--rc-radius);
        background: #fff;
    }
    .rc-tech-collapse summary {
        cursor: pointer;
        padding: 10px 12px;
        font-size: .85rem;
        font-weight: 700;
        color: #334155;
        list-style: none;
    }
    .rc-tech-collapse[open] summary {
        border-bottom: 1px dashed var(--rc-border);
    }
    .rc-tech-body {
        padding: 12px;
    }
    @media(max-width: 900px){
        .rc-insight-grid { grid-template-columns: 1fr; }
    }

    pre.rc-pre { background: #1e1e2e; color: #cdd6f4; border-radius: var(--rc-radius); padding: 14px; font-size: 0.78rem; overflow-x: auto; margin: 0; }

    /* ── Toast ────────────────────────────────────── */
    .rc-toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
    .rc-toast {
        background: var(--rc-surface);
        border: 1px solid var(--rc-border);
        border-left: 3px solid var(--rc-accent);
        border-radius: var(--rc-radius);
        padding: 12px 16px;
        min-width: 280px;
        max-width: 380px;
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
        font-size: 0.875rem;
        animation: toast-in .2s ease;
    }
    .rc-toast-title { font-weight: 700; margin-bottom: 3px; font-size: .85rem; }
    .rc-toast-msg   { color: var(--rc-muted); white-space: pre-wrap; font-size: .82rem; }
    @keyframes toast-in { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: translateY(0); } }

    /* ── Pagination ───────────────────────────────── */
    .rc-pager { padding: 14px 16px; border-top: 1px solid var(--rc-border); font-size: .85rem; }

    .rc-grid-4 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }
    .rc-mini-stat {
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        background: var(--rc-bg);
        padding: 12px 14px;
    }
    .rc-mini-stat .k {
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--rc-muted);
        font-weight: 700;
        letter-spacing: .05em;
    }
    .rc-mini-stat .v {
        margin-top: 4px;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--rc-text);
    }

    .rc-invoice-actions {
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        padding: 14px;
        background: var(--rc-bg);
        margin-bottom: 14px;
    }
    .rc-checkbox {
        width: 16px;
        height: 16px;
        border: 1px solid var(--rc-border);
        border-radius: 3px;
        vertical-align: middle;
    }
    .rc-table td.actions-tight {
        white-space: nowrap;
        width: 1%;
    }
    .rc-status-select {
        width: 120px;
        display: inline-block;
        margin-right: 6px;
    }
    .rc-hotspot-history {
        margin-top: 18px;
        border: 1px solid var(--rc-border);
        border-radius: var(--rc-radius);
        background: #fff;
        overflow: hidden;
    }
    .rc-hotspot-history-head {
        padding: 12px 14px;
        border-bottom: 1px solid var(--rc-border);
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .rc-hotspot-history-head .meta {
        color: var(--rc-muted);
        font-size: .8rem;
    }
    .rc-user-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #1d4ed8;
        text-decoration: none;
        font-weight: 700;
    }
    .rc-user-link:hover {
        color: #1e40af;
        text-decoration: underline;
    }
    .rc-account-pill {
        display: inline-flex;
        align-items: center;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1e3a8a;
        border-radius: 999px;
        padding: 3px 10px;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        max-width: 210px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rc-account-pill:hover {
        color: #1e40af;
        border-color: #bfdbfe;
    }
    .rc-account-pill.is-muted {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #475569;
        text-decoration: none;
    }
</style>

<div class="rc-wrap">

    {{-- Page Header --}}
    <div class="rc-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1><i class="bi bi-cash-stack me-2"></i>Revenue Center</h1>
            <p>MegaPay transactions — hotspot, invoices, store and admin pushes</p>
        </div>
        <a href="{{ url('/dashboard') }}" class="rc-btn rc-btn-ghost">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>

    {{-- Stats --}}
    <div class="rc-stats">
        <div class="rc-stat">
            <div class="rc-stat-label"><i class="bi bi-check2-circle"></i> Completed</div>
            <div class="rc-stat-value">{{ number_format((int)($totals->count_all ?? 0)) }}</div>
            <div class="rc-stat-sub">transactions in range</div>
        </div>
        <div class="rc-stat">
            <div class="rc-stat-label"><i class="bi bi-currency-dollar"></i> Total Revenue</div>
            <div class="rc-stat-value">KES {{ number_format((int)($totals->sum_amount ?? 0)) }}</div>
            <div class="rc-stat-sub">sum of completed payments</div>
        </div>
        <div class="rc-stat">
            <div class="rc-stat-label"><i class="bi bi-calendar-range"></i> Time Window</div>
            <div class="rc-stat-value" style="font-size:1rem; padding-top:4px;">
                {{ $from ?: 'All time' }}
                @if($from && $to) &rarr; {{ $to }} @endif
            </div>
            <div class="rc-stat-sub">selected date range</div>
        </div>
    </div>

    {{-- Main Panel --}}
    <div class="rc-panel">

        {{-- Tab Nav --}}
        <div class="rc-tabs">
            <button class="rc-tab active" data-tab="transactions"><i class="bi bi-list-ul"></i> Transactions</button>
            <button class="rc-tab" data-tab="admin"><i class="bi bi-shield-lock"></i> Admin STK</button>
            <button class="rc-tab" data-tab="hotspot"><i class="bi bi-wifi"></i> Hotspot</button>
            <button class="rc-tab" data-tab="invoice"><i class="bi bi-receipt"></i> Invoice</button>
            <button class="rc-tab" data-tab="store"><i class="bi bi-bag"></i> Store</button>
        </div>

        {{-- Tab Bodies --}}
        <div class="rc-tab-body">

            {{-- ── Transactions ── --}}
            <div class="tab-pane active" id="transactions-tab">

                {{-- Filters --}}
                <div class="rc-transactions-tools">
                    @if($hasTransactionFilters)
                        <span class="rc-active-filter-pill">
                            <i class="bi bi-funnel-fill"></i> Filters applied
                        </span>
                        <a href="{{ route('revenue.index') }}" class="rc-btn rc-btn-primary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                        </a>
                    @else
                        <button type="button" class="rc-btn rc-btn-primary" data-bs-toggle="modal" data-bs-target="#transactionsFilterModal">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    @endif
                </div>

                {{-- By-Purpose Totals --}}
                @if(($byPurpose ?? collect())->count() > 0)
                    <div class="rc-section-title"><i class="bi bi-bar-chart-line"></i> Totals by Purpose</div>
                    <div class="rc-purpose-grid mb-4">
                        @foreach($byPurpose as $p)
                            @php $pname = $p->purpose ?: 'unknown'; @endphp
                            <div class="rc-purpose-chip">
                                <div class="rc-purpose-chip-name">{{ $pname }}</div>
                                <div class="rc-purpose-chip-val">KES {{ number_format((int)$p->total) }}</div>
                                <div class="rc-purpose-chip-cnt">{{ number_format((int)$p->cnt) }} transactions</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Table --}}
                @if($rows->count() === 0)
                    <div class="rc-empty">
                        <i class="bi bi-inbox"></i>
                        <h5>No transactions found</h5>
                        <p style="font-size:.85rem;">Try adjusting your filters or widening the date range.</p>
                    </div>
                @else
                    <div class="rc-table-wrap">
                        <table class="rc-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Reference</th>
                                    <th>MSISDN</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Purpose</th>
                                    <th>Channel</th>
                                    <th>Receipt</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $r)
                                    @php
                                        $si = $statusConfig[$r->status] ?? ['bg'=>'#f5f5f5','text'=>'#555','icon'=>'bi-question-circle','label'=>$r->status];
                                        $mid = 'mpModal_' . $r->id;
                                        $metaData = is_array($r->meta ?? null) ? $r->meta : [];
                                        $webhookData = is_array($r->raw_webhook ?? null) ? $r->raw_webhook : [];
                                        $displayWebhook = $webhookData;
                                        if (array_key_exists('token', $displayWebhook)) {
                                            $displayWebhook['token'] = '[hidden]';
                                        }
                                        $purposeLabel = ucfirst(str_replace('_', ' ', (string)($r->purpose ?? 'unknown')));
                                        $channelLabel = ucfirst(str_replace('_', ' ', (string)($r->channel ?? 'unknown')));
                                        $packageName = (string)($metaData['package_name'] ?? $metaData['plan'] ?? $metaData['package'] ?? 'Not provided');
                                        $durationLabel = (string)($metaData['time_label'] ?? $metaData['duration_label'] ?? '—');
                                        $speedLabel = (string)($metaData['speed'] ?? '');
                                        $flowLabel = (string)($metaData['flow'] ?? '');
                                        $connectionId = (string)($metaData['connection_id'] ?? '');
                                        $connectedAt = (string)($metaData['connected_at'] ?? '');
                                        $ipLabel = (string)($metaData['ip'] ?? '');
                                        $macLabel = (string)($metaData['mac'] ?? '');
                                        $dataLimit = $metaData['data_limit'] ?? null;
                                        $providerMessage = (string)($displayWebhook['ResponseDescription'] ?? $r->response_description ?? '—');
                                        $providerCode = (string)($displayWebhook['ResponseCode'] ?? $r->response_code ?? '—');
                                        $transactionDateRaw = trim((string)($displayWebhook['TransactionDate'] ?? $r->transaction_date ?? ''));
                                        $transactionDateLabel = '—';
                                        if ($transactionDateRaw !== '') {
                                            if (preg_match('/^\d{14}$/', $transactionDateRaw) === 1) {
                                                $transactionDateLabel = substr($transactionDateRaw, 0, 4) . '-' .
                                                    substr($transactionDateRaw, 4, 2) . '-' .
                                                    substr($transactionDateRaw, 6, 2) . ' ' .
                                                    substr($transactionDateRaw, 8, 2) . ':' .
                                                    substr($transactionDateRaw, 10, 2) . ':' .
                                                    substr($transactionDateRaw, 12, 2);
                                            } else {
                                                $transactionDateLabel = $transactionDateRaw;
                                            }
                                        }
                                        $initiatedAtLabel = optional($r->initiated_at ?: $r->created_at)?->format('Y-m-d H:i:s') ?? '—';
                                        $completedAtLabel = optional($r->completed_at)?->format('Y-m-d H:i:s');
                                        if (!$completedAtLabel) {
                                            $completedAtLabel = $transactionDateLabel !== '—' ? $transactionDateLabel : 'Pending';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="rc-mono" style="color:var(--rc-muted);">#{{ $r->id }}</td>
                                        <td>
                                            <div style="font-weight:600;">{{ $r->reference }}</div>
                                            @if($r->transaction_request_id)
                                                <div class="rc-mono" style="color:var(--rc-muted);font-size:.75rem;">{{ $r->transaction_request_id }}</div>
                                            @endif
                                        </td>
                                        <td class="rc-mono">{{ $r->msisdn }}</td>
                                        <td style="font-weight:600;">KES {{ number_format((int)$r->amount) }}</td>
                                        <td>
                                            <span class="rc-badge" style="background:{{ $si['bg'] }};color:{{ $si['text'] }};">
                                                <i class="bi {{ $si['icon'] }}"></i> {{ $si['label'] }}
                                            </span>
                                        </td>
                                        <td><span class="rc-tag">{{ $r->purpose ?? '—' }}</span></td>
                                        <td><span class="rc-tag">{{ $r->channel ?? '—' }}</span></td>
                                        <td class="rc-mono" style="color:var(--rc-muted);">{{ $r->mpesa_receipt ?? '—' }}</td>
                                        <td style="white-space:nowrap;">
                                            <div style="font-size:.83rem;">{{ $r->created_at?->format('M d, H:i') }}</div>
                                            @if($r->completed_at)
                                                <div style="font-size:.75rem;color:#2e7d32;"><i class="bi bi-check"></i> {{ $r->completed_at->format('M d, H:i:s') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="rc-btn rc-btn-ghost rc-btn-sm" onclick="openModal('{{ $mid }}')">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Detail Modal --}}
                                    <div class="modal fade" id="{{ $mid }}" tabindex="-1">
                                        <div class="modal-dialog modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <div>
                                                        <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Transaction #{{ $r->id }}</h5>
                                                        <div class="rc-modal-head-meta">
                                                            Ref {{ $r->reference }} · {{ $r->created_at?->format('M d, Y H:i:s') ?? '—' }}
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <div class="rc-insight-grid">
                                                        <div class="rc-insight-card">
                                                            <div class="k">Amount</div>
                                                            <div class="v">KES {{ number_format((float)($r->amount ?? 0), 2) }}</div>
                                                        </div>
                                                        <div class="rc-insight-card">
                                                            <div class="k">Receipt</div>
                                                            <div class="v rc-mono">{{ $r->mpesa_receipt ?: 'Pending' }}</div>
                                                        </div>
                                                        <div class="rc-insight-card">
                                                            <div class="k">Status</div>
                                                            <div class="v">
                                                                <span class="rc-badge" style="background:{{ $si['bg'] }};color:{{ $si['text'] }};">
                                                                    <i class="bi {{ $si['icon'] }}"></i> {{ $si['label'] }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="rc-insight-card">
                                                            <div class="k">Payment Done At</div>
                                                            <div class="v">{{ $completedAtLabel }}</div>
                                                        </div>
                                                    </div>

                                                    <div class="rc-detail-grid">
                                                        <div class="rc-detail-block">
                                                            <div class="rc-section-title mb-2"><i class="bi bi-info-circle"></i> Payment Details</div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">MSISDN</span>
                                                                <span class="rc-detail-val rc-mono">{{ $r->msisdn ?: ($displayWebhook['Msisdn'] ?? '—') }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Purpose</span>
                                                                <span class="rc-detail-val"><span class="rc-tag">{{ $purposeLabel }}</span></span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Channel</span>
                                                                <span class="rc-detail-val"><span class="rc-tag">{{ $channelLabel }}</span></span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Provider Code</span>
                                                                <span class="rc-detail-val rc-mono">{{ $providerCode !== '' ? $providerCode : '—' }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Provider Message</span>
                                                                <span class="rc-detail-val">{{ $providerMessage !== '' ? $providerMessage : '—' }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Payment Initiated At</span>
                                                                <span class="rc-detail-val">{{ $initiatedAtLabel }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Payment Completed At</span>
                                                                <span class="rc-detail-val">{{ $completedAtLabel }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Transaction Date</span>
                                                                <span class="rc-detail-val">{{ $transactionDateLabel }}</span>
                                                            </div>
                                                        </div>

                                                        <div class="rc-detail-block">
                                                            <div class="rc-section-title mb-2"><i class="bi bi-wifi"></i> Access Context</div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Package</span>
                                                                <span class="rc-detail-val">{{ $packageName !== '' ? $packageName : '—' }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Duration</span>
                                                                <span class="rc-detail-val">{{ $durationLabel !== '' ? $durationLabel : '—' }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Speed</span>
                                                                <span class="rc-detail-val">{{ $speedLabel !== '' ? $speedLabel : '—' }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">IP</span>
                                                                <span class="rc-detail-val rc-mono">{{ $ipLabel !== '' ? $ipLabel : '—' }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">MAC</span>
                                                                <span class="rc-detail-val rc-mono">{{ $macLabel !== '' ? $macLabel : '—' }}</span>
                                                            </div>
                                                            <div class="rc-detail-row">
                                                                <span class="rc-detail-key">Connected At</span>
                                                                <span class="rc-detail-val">{{ $connectedAt !== '' ? $connectedAt : '—' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="rc-detail-full">
                                                        <div class="rc-section-title mb-2"><i class="bi bi-fingerprint"></i> Identifiers</div>
                                                        @foreach([
                                                            'Transaction Request ID' => $r->transaction_request_id,
                                                            'Transaction ID'         => $r->transaction_id,
                                                            'Merchant Request ID'    => $r->merchant_request_id,
                                                            'Checkout Request ID'    => $r->checkout_request_id,
                                                            'Reference'              => $r->reference,
                                                            'Connection ID'          => $connectionId,
                                                            'Flow'                   => $flowLabel,
                                                            'Data Limit'             => ($dataLimit === null || $dataLimit === '') ? '—' : (string)$dataLimit,
                                                        ] as $label => $val)
                                                        <div class="rc-detail-row">
                                                            <span class="rc-detail-key">{{ $label }}</span>
                                                            <span class="rc-detail-val rc-mono">{{ $val ?: '—' }}</span>
                                                        </div>
                                                        @endforeach
                                                    </div>

                                                    @if(!empty($metaData) || !empty($displayWebhook))
                                                        <div class="rc-tech-wrap">
                                                            <details class="rc-tech-collapse">
                                                                <summary><i class="bi bi-code-square me-1"></i> Technical Payload (Support)</summary>
                                                                <div class="rc-tech-body">
                                                                    @if(!empty($metaData))
                                                                        <div class="rc-section-title mb-2"><i class="bi bi-braces"></i> Meta</div>
                                                                        <pre class="rc-pre">{{ json_encode($metaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                                    @endif
                                                                    @if(!empty($displayWebhook))
                                                                        <div class="rc-section-title mb-2 mt-3"><i class="bi bi-arrow-left-right"></i> Webhook Payload</div>
                                                                        <pre class="rc-pre">{{ json_encode($displayWebhook, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                                    @endif
                                                                </div>
                                                            </details>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="rc-btn rc-btn-ghost" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="rc-pager">{{ $rows->links() }}</div>
                @endif

                <div class="modal fade rc-filter-modal" id="transactionsFilterModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-funnel me-2"></i>Filter Transactions</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="GET" action="{{ route('revenue.index') }}">
                                <div class="modal-body">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="rc-label">Search</label>
                                            <input type="text" name="q" class="rc-input" value="{{ $q }}" placeholder="Reference, MSISDN, receipt…">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="rc-label">Status</label>
                                            <select name="status" class="rc-select">
                                                <option value="">All statuses</option>
                                                @foreach($statuses as $s)
                                                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="rc-label">Purpose</label>
                                            <input type="text" name="purpose" class="rc-input" value="{{ $purpose }}" placeholder="e.g. invoice">
                                        </div>
                                        <div class="col-12">
                                            <label class="rc-label">Channel</label>
                                            <input type="text" name="channel" class="rc-input" value="{{ $channel }}" placeholder="e.g. hotspot">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="rc-label">From</label>
                                            <input type="date" name="from" class="rc-input" value="{{ $from }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="rc-label">To</label>
                                            <input type="date" name="to" class="rc-input" value="{{ $to }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="rc-btn rc-btn-ghost" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="rc-btn rc-btn-primary">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Admin STK ── --}}
            <div class="tab-pane" id="admin-tab">
                <div class="rc-form-section">
                    <div class="rc-request-head">
                        <div class="rc-section-title mb-0"><i class="bi bi-shield-lock"></i> Admin STK Push</div>
                        <button class="rc-btn rc-btn-primary rc-btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#adminRequestCollapse" aria-expanded="false" aria-controls="adminRequestCollapse">
                            <i class="bi bi-phone"></i> Request Payment
                        </button>
                    </div>
                    <div class="collapse" id="adminRequestCollapse">
                        <div class="rc-request-panel">
                            <div class="rc-form-desc">
                                Push an STK prompt from the dashboard. Creates <code>purpose=admin_push</code>, <code>channel=dashboard</code>.
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="rc-label">Phone Number</label>
                                    <input id="admin_msisdn" class="rc-input" placeholder="07XXXXXXXX or 2547XXXXXXXX">
                                </div>
                                <div class="col-md-3">
                                    <label class="rc-label">Amount (KES)</label>
                                    <input id="admin_amount" type="number" class="rc-input" placeholder="200">
                                </div>
                                <div class="col-md-3">
                                    <label class="rc-label">Reason</label>
                                    <input id="admin_reason" class="rc-input" placeholder="e.g. Monthly internet">
                                </div>
                                <div class="col-md-6">
                                    <label class="rc-label">Reference <span style="font-weight:400;color:var(--rc-muted);">(optional)</span></label>
                                    <input id="admin_reference" class="rc-input" placeholder="Leave blank to auto-generate">
                                </div>
                                <div class="col-md-6">
                                    <label class="rc-label">Meta (JSON)</label>
                                    <input id="admin_meta" class="rc-input" placeholder='{"customer":"John"}'>
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button class="rc-btn rc-btn-primary" onclick="initiateMegaPay('admin')">
                                        <i class="bi bi-send"></i> Push STK
                                    </button>
                                    <button class="rc-btn rc-btn-ghost" onclick="fillDemo('admin')">
                                        <i class="bi bi-clipboard-data"></i> Fill Demo
                                    </button>
                                    <a href="{{ route('revenue.index') }}" class="rc-btn rc-btn-ghost">
                                        <i class="bi bi-arrow-clockwise"></i> Refresh
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Hotspot ── --}}
            <div class="tab-pane" id="hotspot-tab">
                <div class="rc-form-section">
                    <div class="rc-request-head">
                        <div class="rc-section-title mb-0"><i class="bi bi-wifi"></i> Hotspot Payments</div>
                        <button class="rc-btn rc-btn-primary rc-btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#hotspotRequestCollapse" aria-expanded="false" aria-controls="hotspotRequestCollapse">
                            <i class="bi bi-phone"></i> Request Payment
                        </button>
                    </div>
                    <div class="collapse" id="hotspotRequestCollapse">
                        <div class="rc-request-panel">
                            <div class="rc-form-desc">
                                Test hotspot payment intents. Creates <code>purpose=hotspot_package</code>, <code>channel=hotspot</code>.
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="rc-label">Phone Number</label>
                                    <input id="hotspot_msisdn" class="rc-input" placeholder="07XXXXXXXX">
                                </div>
                                <div class="col-md-2">
                                    <label class="rc-label">Amount (KES)</label>
                                    <input id="hotspot_amount" type="number" class="rc-input" placeholder="50">
                                </div>
                                <div class="col-md-6">
                                    <label class="rc-label">Package / Plan</label>
                                    <input id="hotspot_plan" class="rc-input" placeholder="e.g. 1hr_10mbps">
                                </div>
                                <div class="col-md-6">
                                    <label class="rc-label">Reference <span style="font-weight:400;color:var(--rc-muted);">(optional)</span></label>
                                    <input id="hotspot_reference" class="rc-input" placeholder="optional">
                                </div>
                                <div class="col-md-6">
                                    <label class="rc-label">Meta (JSON)</label>
                                    <input id="hotspot_meta" class="rc-input" placeholder='{"router":"Marcep-R1"}'>
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button class="rc-btn rc-btn-primary" onclick="initiateMegaPay('hotspot')">
                                        <i class="bi bi-send"></i> Initiate Payment
                                    </button>
                                    <button class="rc-btn rc-btn-ghost" onclick="fillDemo('hotspot')">
                                        <i class="bi bi-clipboard-data"></i> Fill Demo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rc-hotspot-history">
                    <div class="rc-hotspot-history-head">
                        <div class="rc-section-title mb-0"><i class="bi bi-journal-check"></i> Recent Hotspot Collections</div>
                        <div class="meta">{{ number_format(($hotspotRows ?? collect())->count()) }} payment(s)</div>
                    </div>
                    @if(($hotspotRows ?? collect())->isEmpty())
                        <div class="rc-empty" style="padding:28px 20px;">
                            <i class="bi bi-inbox"></i>
                            <h5>No completed hotspot payments</h5>
                            <p style="font-size:.85rem;">Completed hotspot STK payments will appear here.</p>
                        </div>
                    @else
                        <div class="rc-table-wrap">
                            <table class="rc-table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Number</th>
                                        <th>Account</th>
                                        <th>Package</th>
                                        <th>Amount</th>
                                        <th>Receipt</th>
                                        <th>Status</th>
                                        <th>Ref</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hotspotRows as $hs)
                                        @php
                                            $hsCfg = $statusConfig[$hs->status] ?? ['bg' => '#f5f5f5', 'text' => '#555', 'icon' => 'bi-question-circle', 'label' => ucfirst((string)$hs->status)];
                                        @endphp
                                        <tr>
                                            <td style="white-space:nowrap;">
                                                <div style="font-size:.83rem;">{{ optional($hs->attempted_at)->format('M d, H:i') ?? '—' }}</div>
                                                @if($hs->completed_at)
                                                    <div style="font-size:.75rem;color:#2e7d32;"><i class="bi bi-check"></i> {{ $hs->completed_at->format('H:i:s') }}</div>
                                                @endif
                                            </td>
                                            <td class="rc-mono">
                                                @if($hs->customer_url)
                                                    <a href="{{ $hs->customer_url }}" class="rc-user-link" title="Open customer account">
                                                        {{ $hs->msisdn }}
                                                    </a>
                                                @else
                                                    {{ $hs->msisdn }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($hs->customer_url)
                                                    <a href="{{ $hs->customer_url }}" class="rc-account-pill" title="Open account {{ $hs->customer_username }}">
                                                        {{ $hs->customer_username }}
                                                    </a>
                                                @elseif(!empty($hs->customer_username))
                                                    <span class="rc-account-pill is-muted">{{ $hs->customer_username }}</span>
                                                @else
                                                    <span class="rc-tag">Not linked</span>
                                                @endif
                                            </td>
                                            <td>{{ $hs->package_name }}</td>
                                            <td style="font-weight:700;">{{ $hs->currency }} {{ number_format((float)$hs->amount, 2) }}</td>
                                            <td class="rc-mono">{{ $hs->receipt ?: '—' }}</td>
                                            <td>
                                                <span class="rc-badge" style="background:{{ $hsCfg['bg'] }};color:{{ $hsCfg['text'] }};">
                                                    <i class="bi {{ $hsCfg['icon'] }}"></i> {{ $hsCfg['label'] }}
                                                </span>
                                            </td>
                                            <td class="rc-mono">{{ $hs->reference ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Invoice ── --}}
            <div class="tab-pane" id="invoice-tab">
                <div class="rc-section-title"><i class="bi bi-receipt"></i> Invoice Manager</div>

                <div class="rc-grid-4">
                    <div class="rc-mini-stat">
                        <div class="k">Invoices</div>
                        <div class="v" id="invoice_total_count">{{ number_format((int)($invoiceTotals['count_all'] ?? 0)) }}</div>
                    </div>
                    <div class="rc-mini-stat">
                        <div class="k">Total Billed</div>
                        <div class="v" id="invoice_total_billed">KES {{ number_format((float)($invoiceTotals['total_billed'] ?? 0), 2) }}</div>
                    </div>
                    <div class="rc-mini-stat">
                        <div class="k">Total Paid</div>
                        <div class="v" id="invoice_total_paid">KES {{ number_format((float)($invoiceTotals['total_paid'] ?? 0), 2) }}</div>
                    </div>
                    <div class="rc-mini-stat">
                        <div class="k">Open Balance</div>
                        <div class="v" id="invoice_total_balance">KES {{ number_format((float)($invoiceTotals['total_balance'] ?? 0), 2) }}</div>
                    </div>
                </div>

                <div class="rc-filter">
                    <form method="GET" action="{{ route('revenue.index') }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="purpose" value="{{ $purpose }}">
                        <input type="hidden" name="channel" value="{{ $channel }}">
                        <input type="hidden" name="from" value="{{ $from }}">
                        <input type="hidden" name="to" value="{{ $to }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="rc-label">Find Invoice / Customer</label>
                                <input type="text" name="invoice_q" class="rc-input" value="{{ $invoiceQ }}" placeholder="Invoice number, username, phone, email">
                            </div>
                            <div class="col-md-3">
                                <label class="rc-label">Invoice Status</label>
                                <select name="invoice_status" class="rc-select">
                                    <option value="">All statuses</option>
                                    @foreach($invoiceStatuses as $invStatus)
                                        <option value="{{ $invStatus }}" @selected($invoiceStatus === $invStatus)>{{ ucfirst($invStatus) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="rc-btn rc-btn-primary">
                                    <i class="bi bi-funnel"></i> Filter Invoices
                                </button>
                                <a href="{{ route('revenue.index') }}" class="rc-btn rc-btn-ghost">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="rc-invoice-actions">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="rc-label">Selected</label>
                            <div id="invoice_selected_count" style="font-weight:700;">0 invoice(s)</div>
                        </div>
                        <div class="col-md-3">
                            <label class="rc-label">Auto Total</label>
                            <div id="invoice_selected_total" style="font-weight:700;">KES 0.00</div>
                        </div>
                        <div class="col-md-2">
                            <label class="rc-label">Phone Number</label>
                            <input id="invoice_request_msisdn" class="rc-input" placeholder="07XXXXXXXX">
                        </div>
                        <div class="col-md-2">
                            <label class="rc-label">Amount (editable)</label>
                            <input id="invoice_request_amount" type="number" min="1" step="0.01" class="rc-input" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="rc-label">Reference (auto)</label>
                            <input id="invoice_request_reference" class="rc-input" readonly value="INVREQ_AUTO">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button class="rc-btn rc-btn-primary" id="invoiceRequestPaymentBtn">
                                <i class="bi bi-phone"></i> Request Payment
                            </button>
                            <button class="rc-btn rc-btn-ghost" id="invoiceSelectAllBtn">
                                <i class="bi bi-check2-square"></i> Select All On Page
                            </button>
                            <button class="rc-btn rc-btn-ghost" id="invoiceClearSelectionBtn">
                                <i class="bi bi-square"></i> Clear Selection
                            </button>
                        </div>
                    </div>
                </div>

                @if(($invoices ?? collect())->count() === 0)
                    <div class="rc-empty">
                        <i class="bi bi-inbox"></i>
                        <h5>No invoices found</h5>
                        <p style="font-size:.85rem;">Generate invoices from customers to populate this section.</p>
                    </div>
                @else
                    <div class="rc-table-wrap">
                        <table class="rc-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="invoice_master_checkbox" class="rc-checkbox"></th>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Amounts</th>
                                    <th>Status</th>
                                    <th>Issued / Due</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $inv)
                                    @php
                                        $invStatus = $inv->invoice_status ?: $inv->status ?: 'unpaid';
                                        $invCfg = $invoiceStatusConfig[$invStatus] ?? ['bg' => '#f5f5f5', 'text' => '#424242', 'icon' => 'bi-question-circle', 'label' => ucfirst((string)$invStatus)];
                                        $invAmount = (float)($inv->total_amount ?: $inv->amount ?: 0);
                                        $invBalance = (float)($inv->balance_amount ?: $invAmount);
                                    @endphp
                                    <tr id="invoice-row-{{ $inv->id }}">
                                        <td>
                                            <input
                                                type="checkbox"
                                                class="rc-checkbox invoice-select"
                                                value="{{ $inv->id }}"
                                                data-balance="{{ number_format($invBalance, 2, '.', '') }}"
                                                data-phone="{{ $inv->customer?->phone }}"
                                                data-customer-id="{{ $inv->customer_id }}"
                                            >
                                        </td>
                                        <td>
                                            <div style="font-weight:600;">{{ $inv->invoice_number }}</div>
                                            <div class="rc-mono" style="color:var(--rc-muted);font-size:.75rem;">#{{ $inv->id }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;">{{ $inv->customer?->name ?? ($inv->customer?->username ?? 'N/A') }}</div>
                                            <div class="rc-mono" style="color:var(--rc-muted);font-size:.75rem;">{{ $inv->customer?->phone ?? 'No phone' }}</div>
                                        </td>
                                        <td>
                                            <div id="invoice-total-{{ $inv->id }}" style="font-weight:700;">{{ $inv->currency ?? 'KES' }} {{ number_format($invAmount, 2) }}</div>
                                            <div id="invoice-balance-{{ $inv->id }}" class="rc-mono" style="color:var(--rc-muted);font-size:.75rem;">
                                                Balance: {{ $inv->currency ?? 'KES' }} {{ number_format($invBalance, 2) }}
                                            </div>
                                        </td>
                                        <td>
                                            <span id="invoice-status-badge-{{ $inv->id }}" class="rc-badge" style="background:{{ $invCfg['bg'] }};color:{{ $invCfg['text'] }};">
                                                <i class="bi {{ $invCfg['icon'] }}"></i> {{ $invCfg['label'] }}
                                            </span>
                                        </td>
                                        <td style="font-size:.82rem;">
                                            <div>{{ optional($inv->issued_at ?: $inv->created_at)->format('Y-m-d') }}</div>
                                            <div id="invoice-due-{{ $inv->id }}" style="color:var(--rc-muted);">Due: {{ optional($inv->due_date)->format('Y-m-d') ?: '—' }}</div>
                                        </td>
                                        <td class="actions-tight">
                                            <button type="button" class="rc-btn rc-btn-ghost rc-btn-sm js-invoice-view" data-id="{{ $inv->id }}">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="rc-btn rc-btn-ghost rc-btn-sm">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                            <button type="button" class="rc-btn rc-btn-ghost rc-btn-sm js-invoice-remind" data-id="{{ $inv->id }}">
                                                <i class="bi bi-bell"></i> Reminder
                                            </button>
                                            <button type="button" class="rc-btn rc-btn-ghost rc-btn-sm js-invoice-delete" data-id="{{ $inv->id }}">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                            <div style="margin-top:6px;">
                                                <select class="rc-select rc-select-sm rc-status-select" id="invoice-status-select-{{ $inv->id }}">
                                                    @foreach($invoiceStatuses as $selectStatus)
                                                        <option value="{{ $selectStatus }}" @selected($invStatus === $selectStatus)>{{ ucfirst($selectStatus) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="rc-btn rc-btn-ghost rc-btn-sm js-invoice-status-save" data-id="{{ $inv->id }}">
                                                    Update
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="rc-pager">{{ $invoices->links() }}</div>
                @endif
            </div>
            <div class="modal fade" id="invoiceDetailModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Invoice Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="invoiceDetailBody" class="rc-detail-full">
                                <div class="rc-section-title mb-2"><i class="bi bi-hourglass-split"></i> Loading</div>
                                <div>Fetching invoice details...</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="rc-btn rc-btn-ghost" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Store ── --}}
            <div class="tab-pane" id="store-tab">
                <div class="rc-form-section">
                    <div class="rc-request-head">
                        <div class="rc-section-title mb-0"><i class="bi bi-bag"></i> Store Checkout</div>
                        <button class="rc-btn rc-btn-primary rc-btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#storeRequestCollapse" aria-expanded="false" aria-controls="storeRequestCollapse">
                            <i class="bi bi-phone"></i> Request Payment
                        </button>
                    </div>
                    <div class="collapse" id="storeRequestCollapse">
                        <div class="rc-request-panel">
                            <div class="rc-form-desc">
                                Use a reference like <code>ORDER_ABC123</code>. Creates <code>purpose=store_order</code>, <code>channel=store</code>.
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="rc-label">Phone Number</label>
                                    <input id="store_msisdn" class="rc-input" placeholder="07XXXXXXXX">
                                </div>
                                <div class="col-md-2">
                                    <label class="rc-label">Amount (KES)</label>
                                    <input id="store_amount" type="number" class="rc-input" placeholder="3500">
                                </div>
                                <div class="col-md-6">
                                    <label class="rc-label">Order Reference</label>
                                    <input id="store_reference" class="rc-input" placeholder="ORDER_ABC123">
                                </div>
                                <div class="col-md-12">
                                    <label class="rc-label">Items / Meta (JSON)</label>
                                    <input id="store_meta" class="rc-input" placeholder='{"items":[{"sku":"RB4011"}]}'>
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button class="rc-btn rc-btn-primary" onclick="initiateMegaPay('store')">
                                        <i class="bi bi-send"></i> Initiate Payment
                                    </button>
                                    <button class="rc-btn rc-btn-ghost" onclick="fillDemo('store')">
                                        <i class="bi bi-clipboard-data"></i> Fill Demo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /.rc-tab-body --}}
    </div>{{-- /.rc-panel --}}
</div>{{-- /.rc-wrap --}}

{{-- Toast Container --}}
<div class="rc-toast-container" id="rcToasts"></div>

<script>
// ── Tabs ────────────────────────────────────────────────
document.querySelectorAll('.rc-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.rc-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(this.dataset.tab + '-tab').classList.add('active');
        localStorage.setItem('activeRevenueTab', this.dataset.tab);
    });
});
const lastTab = localStorage.getItem('activeRevenueTab');
if (lastTab) { const t = document.querySelector(`[data-tab="${lastTab}"]`); if (t) t.click(); }

// ── Toast ────────────────────────────────────────────────
function showToast(title, message, type = 'info') {
    const colors = { success: '#16a34a', error: '#dc2626', warning: '#d97706', info: '#2563eb' };
    const el = document.createElement('div');
    el.className = 'rc-toast';
    el.style.borderLeftColor = colors[type] || colors.info;
    el.innerHTML = `<div class="rc-toast-title">${title}</div><div class="rc-toast-msg">${message}</div>`;
    document.getElementById('rcToasts').appendChild(el);
    setTimeout(() => el.remove(), 2000);
}

// ── Demo fill ────────────────────────────────────────────
function fillDemo(which) {
    const demos = {
        admin:   { msisdn:'0745936445', amount:'11',   reason:'Monthly internet - Admin push', reference:'', meta:'{"reason":"monthly","account":"ACC_001"}' },
        hotspot: { msisdn:'0745936445', amount:'50',   plan:'1hr_10mbps', reference:'', meta:'{"router":"Marcep-R1","site":"HQ"}' },
        store:   { msisdn:'0745936445', amount:'3500', reference:'ORDER_ABC123', meta:'{"items":[{"sku":"RB4011","qty":1}],"delivery":"pickup"}' },
    };
    const demo = demos[which]; if (!demo) return;
    Object.keys(demo).forEach(k => { const el = document.getElementById(`${which}_${k}`); if (el) el.value = demo[k]; });
}

// ── Modal ────────────────────────────────────────────────
function openModal(id) { new bootstrap.Modal(document.getElementById(id)).show(); }

// ── Helpers ──────────────────────────────────────────────
function getCsrfToken() { const m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : null; }
function safeJson(s)    { if (!s) return null; try { return JSON.parse(s); } catch(e) { return null; } }
async function readRes(r) { const t = await r.text(); try { return { j: JSON.parse(t), raw: t }; } catch(e) { return { j: null, raw: t }; } }

// ── STK Initiation ───────────────────────────────────────
async function initiateMegaPay(which) {
    const configs = {
        admin:   { msisdn:'admin_msisdn',   amount:'admin_amount',   ref:'admin_reference',   meta:'admin_meta',   purpose:'admin_push',      channel:'dashboard',    extra: () => ({ reason: document.getElementById('admin_reason')?.value?.trim() || null }) },
        hotspot: { msisdn:'hotspot_msisdn', amount:'hotspot_amount', ref:'hotspot_reference', meta:'hotspot_meta', purpose:'hotspot_package', channel:'hotspot',      extra: () => ({ plan:   document.getElementById('hotspot_plan')?.value?.trim() || null }) },
        store:   { msisdn:'store_msisdn',   amount:'store_amount',   ref:'store_reference',   meta:'store_meta',   purpose:'store_order',      channel:'store',        extra: () => ({}) },
    };
    const c = configs[which]; if (!c) return;

    const msisdn    = document.getElementById(c.msisdn)?.value?.trim();
    const amount    = parseInt(document.getElementById(c.amount)?.value || '0', 10);
    const reference = document.getElementById(c.ref)?.value?.trim() || null;
    const meta      = Object.assign(safeJson(document.getElementById(c.meta)?.value?.trim()) || {}, c.extra());

    if (!msisdn || !amount || amount < 1) { showToast('Validation', 'Phone number and amount are required.', 'error'); return; }

    const payload = { msisdn, amount, purpose: c.purpose, channel: c.channel, meta };
    if (reference) payload.reference = reference;

    showToast('MegaPay', 'Sending STK request…', 'info');

    try {
        const res  = await fetch('/api/megapay/initiate', { method:'POST', credentials:'same-origin', headers: { 'Content-Type':'application/json', 'Accept':'application/json', ...(getCsrfToken() ? {'X-CSRF-TOKEN': getCsrfToken()} : {}) }, body: JSON.stringify(payload) });
        const { j } = await readRes(res);

        if (!res.ok || (j && j.ok === false)) { showToast('Error', j?.message || 'Request failed', 'error'); return; }

        const ref = j?.reference || reference || '(auto)';
        const trx = j?.transaction_request_id ? `\nTX: ${j.transaction_request_id}` : '';
        showToast('Sent', `STK dispatched.\nRef: ${ref}${trx}\nAsk customer to enter PIN.`, 'success');
    } catch(err) {
        showToast('Network Error', err.message || 'Could not reach server.', 'error');
    }
}

const INVOICE_API_BASE = '/invoices';
const INVOICE_POLL_URL = '/invoices/poll';
const INVOICE_REQUEST_PAYMENT_URL = '/invoices/request-payment';
let INVOICE_REFRESH_TIMER = null;
const INVOICE_STATUS_STYLE = {
    paid:      { bg: '#e8f5e9', text: '#2e7d32', icon: 'bi-check-circle-fill', label: 'Paid' },
    partial:   { bg: '#e3f2fd', text: '#1565c0', icon: 'bi-pie-chart-fill', label: 'Partial' },
    overdue:   { bg: '#fff3e0', text: '#ef6c00', icon: 'bi-exclamation-triangle-fill', label: 'Overdue' },
    due:       { bg: '#ede9fe', text: '#5b21b6', icon: 'bi-calendar-event-fill', label: 'Due' },
    unpaid:    { bg: '#fce4ec', text: '#c62828', icon: 'bi-x-circle-fill', label: 'Unpaid' },
    cancelled: { bg: '#f5f5f5', text: '#424242', icon: 'bi-slash-circle', label: 'Cancelled' },
};

function escapeHtml(value) {
    return (value ?? '').toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatKes(value) {
    const amount = Number(value || 0);
    return `KES ${amount.toFixed(2)}`;
}

function selectedInvoices() {
    return Array.from(document.querySelectorAll('.invoice-select:checked')).map((el) => ({
        id: Number(el.value),
        balance: Number(el.dataset.balance || 0),
        phone: (el.dataset.phone || '').trim(),
        customerId: Number(el.dataset.customerId || 0),
    }));
}

function buildInvoiceReference(ids) {
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

async function pollInvoicesAjax(silent = true) {
    const ids = Array.from(document.querySelectorAll('.invoice-select'))
        .map((el) => Number(el.value))
        .filter((id) => Number.isFinite(id) && id > 0);

    if (!ids.length) {
        return;
    }

    const params = new URLSearchParams();
    ids.forEach((id) => params.append('ids[]', String(id)));

    try {
        const res = await fetch(`${INVOICE_POLL_URL}?${params.toString()}`, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            },
        });
        const { j } = await readRes(res);
        if (!res.ok || !j?.ok || !Array.isArray(j.rows)) {
            if (!silent) {
                showToast('Refresh failed', j?.message || 'Could not refresh invoices.', 'error');
            }
            return;
        }

        j.rows.forEach((row) => {
            const id = Number(row.id || 0);
            if (!id) {
                return;
            }

            const status = (row.status || 'unpaid').toString();
            const currency = (row.currency || 'KES').toString();
            const totalAmount = Number(row.total_amount || 0);
            const balanceAmount = Number(row.balance_amount || 0);

            setInvoiceBadge(id, status);

            const totalEl = document.getElementById(`invoice-total-${id}`);
            if (totalEl) {
                totalEl.textContent = `${currency} ${totalAmount.toFixed(2)}`;
            }

            const balanceEl = document.getElementById(`invoice-balance-${id}`);
            if (balanceEl) {
                balanceEl.textContent = `Balance: ${currency} ${balanceAmount.toFixed(2)}`;
            }

            const dueEl = document.getElementById(`invoice-due-${id}`);
            if (dueEl) {
                dueEl.textContent = `Due: ${row.due_date || '—'}`;
            }

            const checkbox = document.querySelector(`.invoice-select[value="${id}"]`);
            if (checkbox) {
                checkbox.dataset.balance = balanceAmount.toFixed(2);
                const nextPhone = (row.customer?.phone || '').toString().trim();
                if (nextPhone) {
                    checkbox.dataset.phone = nextPhone;
                }
            }

            const statusSelect = document.getElementById(`invoice-status-select-${id}`);
            if (statusSelect && statusSelect.value !== status) {
                statusSelect.value = status;
            }
        });

        if (j.totals) {
            const t = j.totals;
            const countEl = document.getElementById('invoice_total_count');
            const billedEl = document.getElementById('invoice_total_billed');
            const paidEl = document.getElementById('invoice_total_paid');
            const balanceEl = document.getElementById('invoice_total_balance');

            if (countEl) countEl.textContent = Number(t.count_all || 0).toLocaleString();
            if (billedEl) billedEl.textContent = formatKes(t.total_billed || 0);
            if (paidEl) paidEl.textContent = formatKes(t.total_paid || 0);
            if (balanceEl) balanceEl.textContent = formatKes(t.total_balance || 0);
        }

        updateInvoiceSelectionSummary();
    } catch (error) {
        if (!silent) {
            showToast('Network error', error.message || 'Could not refresh invoices.', 'error');
        }
    }
}

function startInvoiceAutoRefresh() {
    if (INVOICE_REFRESH_TIMER) {
        clearInterval(INVOICE_REFRESH_TIMER);
    }

    INVOICE_REFRESH_TIMER = setInterval(() => {
        pollInvoicesAjax(true);
    }, 12000);
}

function statusStyle(status) {
    return INVOICE_STATUS_STYLE[status] || { bg: '#f5f5f5', text: '#424242', icon: 'bi-question-circle', label: status || 'Unknown' };
}

function setInvoiceBadge(invoiceId, status) {
    const badge = document.getElementById(`invoice-status-badge-${invoiceId}`);
    if (!badge) {
        return;
    }

    const s = statusStyle(status);
    badge.style.background = s.bg;
    badge.style.color = s.text;
    badge.innerHTML = `<i class="bi ${s.icon}"></i> ${escapeHtml(s.label)}`;
}

function updateInvoiceSelectionSummary() {
    const selected = selectedInvoices();
    const total = selected.reduce((sum, row) => sum + (Number.isFinite(row.balance) ? row.balance : 0), 0);
    const countEl = document.getElementById('invoice_selected_count');
    const totalEl = document.getElementById('invoice_selected_total');
    const referenceEl = document.getElementById('invoice_request_reference');
    const amountEl = document.getElementById('invoice_request_amount');
    const phoneEl = document.getElementById('invoice_request_msisdn');

    if (countEl) countEl.textContent = `${selected.length} invoice(s)`;
    if (totalEl) totalEl.textContent = formatKes(total);
    if (referenceEl) referenceEl.value = buildInvoiceReference(selected.map((i) => i.id));

    if (amountEl && amountEl.dataset.userEdited !== '1') {
        amountEl.value = total.toFixed(2);
    }

    if (phoneEl) {
        const phones = [...new Set(selected.map((i) => i.phone).filter(Boolean))];
        if (!phones.length) {
            phoneEl.value = '';
        } else if (phones.length === 1 && !phoneEl.dataset.userEdited) {
            phoneEl.value = phones[0];
        }
    }

    const master = document.getElementById('invoice_master_checkbox');
    if (master) {
        const all = document.querySelectorAll('.invoice-select');
        const allChecked = all.length > 0 && Array.from(all).every((el) => el.checked);
        master.checked = allChecked;
    }
}

async function requestInvoicePayment() {
    const rows = selectedInvoices();
    if (!rows.length) {
        showToast('Selection required', 'Select at least one invoice before requesting payment.', 'warning');
        return;
    }

    const uniqueCustomers = [...new Set(rows.map((r) => r.customerId).filter((id) => id > 0))];
    if (uniqueCustomers.length > 1) {
        showToast('Invalid selection', 'Select invoices from one customer only.', 'error');
        return;
    }

    const msisdn = (document.getElementById('invoice_request_msisdn')?.value || '').trim();
    const amount = Number(document.getElementById('invoice_request_amount')?.value || 0);

    if (!msisdn) {
        showToast('Phone required', 'Provide a valid customer number.', 'error');
        return;
    }

    if (!Number.isFinite(amount) || amount <= 0) {
        showToast('Amount required', 'Amount must be above zero.', 'error');
        return;
    }

    const payload = {
        invoice_ids: rows.map((r) => r.id),
        msisdn,
        amount,
    };

    try {
        const res = await fetch(INVOICE_REQUEST_PAYMENT_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(getCsrfToken() ? { 'X-CSRF-TOKEN': getCsrfToken() } : {}),
            },
            body: JSON.stringify(payload),
        });

        const { j } = await readRes(res);
        if (!res.ok || (j && j.ok === false)) {
            showToast('Request failed', j?.message || 'Could not request payment.', 'error');
            return;
        }

        showToast('STK sent', `Reference: ${j?.reference || 'AUTO'}\nAmount: ${formatKes(j?.amount || amount)}`, 'success');
        document.querySelectorAll('.invoice-select').forEach((el) => { el.checked = false; });

        const amountEl = document.getElementById('invoice_request_amount');
        if (amountEl) amountEl.dataset.userEdited = '0';
        updateInvoiceSelectionSummary();
        setTimeout(() => pollInvoicesAjax(true), 2500);
    } catch (error) {
        showToast('Network error', error.message || 'Could not request payment.', 'error');
    }
}

async function sendInvoiceReminder(invoiceId) {
    try {
        const res = await fetch(`${INVOICE_API_BASE}/${invoiceId}/reminder`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                ...(getCsrfToken() ? { 'X-CSRF-TOKEN': getCsrfToken() } : {}),
            },
        });
        const { j } = await readRes(res);
        if (!res.ok || (j && j.ok === false)) {
            showToast('Reminder failed', j?.message || 'Could not send reminder.', 'error');
            return;
        }

        showToast('Reminder sent', j?.message || 'Customer notified successfully.', 'success');
    } catch (error) {
        showToast('Network error', error.message || 'Could not send reminder.', 'error');
    }
}

async function updateInvoiceStatus(invoiceId) {
    const select = document.getElementById(`invoice-status-select-${invoiceId}`);
    const newStatus = select?.value;
    if (!newStatus) {
        showToast('Missing status', 'Choose a status first.', 'warning');
        return;
    }

    try {
        const res = await fetch(`${INVOICE_API_BASE}/${invoiceId}/status`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(getCsrfToken() ? { 'X-CSRF-TOKEN': getCsrfToken() } : {}),
            },
            body: JSON.stringify({ invoice_status: newStatus }),
        });
        const { j } = await readRes(res);
        if (!res.ok || (j && j.ok === false)) {
            showToast('Update failed', j?.message || 'Could not update status.', 'error');
            return;
        }

        const updated = j?.invoice || {};
        const status = updated.invoice_status || updated.status || newStatus;
        setInvoiceBadge(invoiceId, status);

        const balance = Number(updated.balance_amount || 0);
        const balanceCell = document.getElementById(`invoice-balance-${invoiceId}`);
        if (balanceCell) {
            balanceCell.textContent = `Balance: ${updated.currency || 'KES'} ${balance.toFixed(2)}`;
        }

        const checkbox = document.querySelector(`.invoice-select[value="${invoiceId}"]`);
        if (checkbox) {
            checkbox.dataset.balance = balance.toFixed(2);
        }

        updateInvoiceSelectionSummary();
        showToast('Updated', j?.message || 'Invoice status saved.', 'success');
    } catch (error) {
        showToast('Network error', error.message || 'Could not update status.', 'error');
    }
}

async function deleteInvoice(invoiceId) {
    const password = window.prompt('Type deleteAdmin to confirm invoice deletion.');
    if (password === null) {
        return;
    }

    if (!password.trim()) {
        showToast('Password required', 'Invoice was not deleted.', 'warning');
        return;
    }

    try {
        const res = await fetch(`${INVOICE_API_BASE}/${invoiceId}/delete`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(getCsrfToken() ? { 'X-CSRF-TOKEN': getCsrfToken() } : {}),
            },
            body: JSON.stringify({ delete_password: password }),
        });
        const { j } = await readRes(res);
        if (!res.ok || (j && j.ok === false)) {
            showToast('Delete failed', j?.message || 'Could not delete invoice.', 'error');
            return;
        }

        const row = document.getElementById(`invoice-row-${invoiceId}`);
        if (row) {
            row.remove();
        }

        const master = document.getElementById('invoice_master_checkbox');
        if (master) {
            master.checked = false;
        }

        updateInvoiceSelectionSummary();
        showToast('Invoice deleted', j?.message || 'Invoice removed successfully.', 'success');
        setTimeout(() => pollInvoicesAjax(true), 250);
    } catch (error) {
        showToast('Network error', error.message || 'Could not delete invoice.', 'error');
    }
}

async function viewInvoice(invoiceId) {
    const body = document.getElementById('invoiceDetailBody');
    if (body) {
        body.innerHTML = '<div class="rc-section-title mb-2"><i class="bi bi-hourglass-split"></i> Loading</div><div>Fetching invoice details...</div>';
    }

    const modalEl = document.getElementById('invoiceDetailModal');
    if (!modalEl) {
        return;
    }

    bootstrap.Modal.getOrCreateInstance(modalEl).show();

    try {
        const res = await fetch(`${INVOICE_API_BASE}/${invoiceId}/view`, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        const { j } = await readRes(res);
        if (!res.ok || !j?.invoice) {
            if (body) {
                body.innerHTML = '<div class="text-danger">Failed to load invoice details.</div>';
            }
            return;
        }

        const invoice = j.invoice || {};
        const customer = j.customer || {};
        const payments = Array.isArray(j.payments) ? j.payments : [];
        const paymentRows = payments.length
            ? payments.map((p) => `
                <tr>
                    <td>${escapeHtml((p.paid_at || p.created_at || '').toString().slice(0, 19).replace('T', ' ')) || '-'}</td>
                    <td>${escapeHtml(p.method || 'mpesa')}</td>
                    <td>${escapeHtml(p.reference || '-')}</td>
                    <td>${escapeHtml((p.transaction_code || p.transaction_id || '-'))}</td>
                    <td>${escapeHtml(invoice.currency || 'KES')} ${Number(p.amount || 0).toFixed(2)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="5" style="color:var(--rc-muted);">No payments yet.</td></tr>';

        if (body) {
            body.innerHTML = `
                <div class="rc-detail-grid">
                    <div class="rc-detail-block">
                        <div class="rc-section-title mb-2"><i class="bi bi-person-vcard"></i> Customer</div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Name</span><span class="rc-detail-val">${escapeHtml(customer.name || customer.username || 'N/A')}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Phone</span><span class="rc-detail-val">${escapeHtml(customer.phone || '-')}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Email</span><span class="rc-detail-val">${escapeHtml(customer.email || '-')}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Status</span><span class="rc-detail-val">${escapeHtml(invoice.invoice_status || invoice.status || 'unpaid')}</span></div>
                    </div>
                    <div class="rc-detail-block">
                        <div class="rc-section-title mb-2"><i class="bi bi-cash-coin"></i> Totals</div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Invoice No.</span><span class="rc-detail-val">${escapeHtml(invoice.invoice_number || '-')}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Issued</span><span class="rc-detail-val">${escapeHtml(invoice.issued_at || invoice.created_at || '-')}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Due</span><span class="rc-detail-val">${escapeHtml(invoice.due_date || '-')}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Total</span><span class="rc-detail-val">${escapeHtml(invoice.currency || 'KES')} ${Number(invoice.total_amount || invoice.amount || 0).toFixed(2)}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Paid</span><span class="rc-detail-val">${escapeHtml(invoice.currency || 'KES')} ${Number(invoice.paid_amount || 0).toFixed(2)}</span></div>
                        <div class="rc-detail-row"><span class="rc-detail-key">Balance</span><span class="rc-detail-val">${escapeHtml(invoice.currency || 'KES')} ${Number(invoice.balance_amount || 0).toFixed(2)}</span></div>
                    </div>
                </div>
                <div class="rc-detail-full mt-3">
                    <div class="rc-section-title mb-2"><i class="bi bi-credit-card"></i> Payments</div>
                    <div class="rc-table-wrap">
                        <table class="rc-table">
                            <thead>
                                <tr>
                                    <th>Paid At</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Transaction</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>${paymentRows}</tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <a href="${INVOICE_API_BASE}/${invoiceId}/print" target="_blank" class="rc-btn rc-btn-ghost rc-btn-sm">
                            <i class="bi bi-printer"></i> Print Invoice
                        </a>
                        ${j.public_url ? `<a href="${escapeHtml(j.public_url)}" target="_blank" class="rc-btn rc-btn-ghost rc-btn-sm"><i class="bi bi-link-45deg"></i> Public Link</a>` : ''}
                    </div>
                </div>
            `;
        }
    } catch (error) {
        if (body) {
            body.innerHTML = `<div class="text-danger">${escapeHtml(error.message || 'Failed to load invoice')}</div>`;
        }
    }
}

function bindInvoiceManager() {
    const amountEl = document.getElementById('invoice_request_amount');
    if (amountEl) {
        amountEl.addEventListener('input', () => {
            amountEl.dataset.userEdited = '1';
        });
    }

    const phoneEl = document.getElementById('invoice_request_msisdn');
    if (phoneEl) {
        phoneEl.addEventListener('input', () => {
            phoneEl.dataset.userEdited = '1';
        });
    }

    document.querySelectorAll('.invoice-select').forEach((el) => {
        el.addEventListener('change', updateInvoiceSelectionSummary);
    });

    const master = document.getElementById('invoice_master_checkbox');
    if (master) {
        master.addEventListener('change', () => {
            document.querySelectorAll('.invoice-select').forEach((el) => {
                el.checked = master.checked;
            });
            updateInvoiceSelectionSummary();
        });
    }

    const selectAllBtn = document.getElementById('invoiceSelectAllBtn');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.invoice-select').forEach((el) => {
                el.checked = true;
            });
            updateInvoiceSelectionSummary();
        });
    }

    const clearSelectionBtn = document.getElementById('invoiceClearSelectionBtn');
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', () => {
            document.querySelectorAll('.invoice-select').forEach((el) => {
                el.checked = false;
            });

            if (amountEl) amountEl.dataset.userEdited = '0';
            if (phoneEl) phoneEl.dataset.userEdited = '';
            updateInvoiceSelectionSummary();
        });
    }

    const requestBtn = document.getElementById('invoiceRequestPaymentBtn');
    if (requestBtn) {
        requestBtn.addEventListener('click', requestInvoicePayment);
    }

    document.querySelectorAll('.js-invoice-remind').forEach((btn) => {
        btn.addEventListener('click', () => sendInvoiceReminder(btn.dataset.id));
    });

    document.querySelectorAll('.js-invoice-status-save').forEach((btn) => {
        btn.addEventListener('click', () => updateInvoiceStatus(btn.dataset.id));
    });

    document.querySelectorAll('.js-invoice-view').forEach((btn) => {
        btn.addEventListener('click', () => viewInvoice(btn.dataset.id));
    });

    document.querySelectorAll('.js-invoice-delete').forEach((btn) => {
        btn.addEventListener('click', () => deleteInvoice(btn.dataset.id));
    });

    updateInvoiceSelectionSummary();
}

bindInvoiceManager();
startInvoiceAutoRefresh();
pollInvoicesAjax(true);
</script>
@endsection
