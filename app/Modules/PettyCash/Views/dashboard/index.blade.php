@extends('pettycash::layouts.app')

@section('title','PettyCash Dashboard')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    /* Note: layout already sets body background; we keep your theme */
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #fafafa !important;
        color: #1a1a1a;
    }

    /* Override layout container spacing a bit for your dashboard */
    .container { max-width: 1200px !important; padding: 24px 16px !important; }

    .wrap { max-width: 1200px; margin: 0 auto; }

    /* Header */
    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 4px;
    }

    .header-sub {
        font-size: 14px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .date-badge {
        padding: 4px 10px;
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 13px;
        color: #4a4a4a;
        font-weight: 500;
    }

    /* Filter Card */
    .filter-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
    }

    .filter-form {
        display: flex;
        gap: 10px;
        align-items: end;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 0 0 auto;
        min-width: 130px;
    }

    .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #4a4a4a;
        margin-bottom: 4px;
        display: block;
    }

    .form-input {
        width: 100%;
        border: 1px solid #d5d5d5;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 13px;
        transition: all 0.2s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #4a4a4a;
    }

    .btn-primary {
        border: none;
        background: #1a1a1a;
        color: #fff;
        padding: 9px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s ease;
    }

    .btn-primary:hover {
        background: #000;
        transform: translateY(-1px);
    }

    .btn-secondary {
        border: 1px solid #d0d0d0;
        background: #fff;
        color: #4a4a4a;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s ease;
    }

    .btn-secondary:hover {
        background: #f9f9f9;
        transform: translateY(-1px);
    }

    .filter-tip {
        flex-basis: 100%;
        font-size: 12px;
        color: #666;
        padding: 10px;
        background: #f9f9f9;
        border-radius: 6px;
        border-left: 3px solid #e0e0e0;
        margin-top: 4px;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 12px;
        padding: 24px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #e5e5e5;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: #d0d0d0;
    }

    .stat-card:hover::before { width: 6px; }

    .stat-card.credited::before { background: #16a34a; }
    .stat-card.spent::before { background: #dc2626; }
    .stat-card.balance::before { background: #1a1a1a; }
    .stat-card.top::before { background: #666; }

    .stat-label {
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .stat-label i { font-size: 14px; opacity: 0.7; }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 8px;
        color: #1a1a1a;
    }

    .stat-value.green { color: #16a34a; }
    .stat-value.red { color: #dc2626; }

    .stat-meta { font-size: 13px; color: #888; }

    /* Tables Section */
    .tables-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }

    .table-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .table-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .table-header {
        background: #fafafa;
        padding: 18px 20px;
        border-bottom: 1px solid #e5e5e5;
    }

    .table-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-title i { font-size: 16px; opacity: 0.6; }
    .table-subtitle { font-size: 13px; color: #666; }

    table { width: 100%; border-collapse: collapse; }
    th, td {
        text-align: left;
        padding: 16px 24px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    th {
        background: #fafafa;
        color: #4a4a4a;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    tbody tr { transition: all 0.2s ease; }
    tbody tr:hover { background: #fafafa; transform: scale(1.01); }
    tbody tr:last-child td { border-bottom: none; }

    .category-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        color: #4a4a4a;
    }

    .category-badge i { font-size: 12px; opacity: 0.7; }
    .amount-green { color: #16a34a; font-weight: 600; }
    .amount-red { color: #dc2626; font-weight: 600; }

    .empty-state {
        padding: 48px 24px;
        text-align: center;
        color: #999;
    }

    .empty-icon { font-size: 40px; opacity: 0.3; margin-bottom: 12px; }

    @media (max-width: 980px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .tables-row { grid-template-columns: 1fr; }
    }

    @media (max-width: 600px) {
        .stats-grid { grid-template-columns: 1fr; }
        .filter-form { flex-direction: column; align-items: stretch; }
        .form-group { min-width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="wrap">

    <!-- Header  -->
    <div class="header">
        <div>
            <h1>PettyCash Dashboard</h1>
            <div class="header-sub">
                @if($from || $to)
                    <span>Showing:</span>
                    <span class="date-badge">{{ $from ?? '...' }}</span>
                    <span>to</span>
                    <span class="date-badge">{{ $to ?? '...' }}</span>
                @else
                    <span class="date-badge">All time</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('petty.dashboard') }}" class="filter-form">
            <div class="form-group">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-input">
            </div>
            <div>
                <button class="btn-primary" type="submit">Filter</button>
            </div>
            <div>
                <a class="btn-secondary" href="{{ route('petty.dashboard') }}">Reset</a>
            </div>
            <div class="filter-tip">
                Tip: You can filter any time range.
            </div>
        </form>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card credited">
            <div class="stat-label"><i class="bi bi-arrow-down-circle"></i> Total Credited</div>
            <div class="stat-value green">{{ number_format((float)$totalCredited, 2) }}</div>
            <div class="stat-meta">Money received</div>
        </div>

        <div class="stat-card spent">
            <div class="stat-label"><i class="bi bi-arrow-up-circle"></i> Total Spent</div>
            <div class="stat-value red">{{ number_format((float)$totalSpent, 2) }}</div>
            <div class="stat-meta">Money disbursed</div>
        </div>

        <div class="stat-card balance">
            <div class="stat-label"><i class="bi bi-wallet2"></i> Balance</div>
            <div class="stat-value {{ (float)$balance < 0 ? 'red' : 'green' }}">
                {{ number_format((float)$balance, 2) }}
            </div>
            <div class="stat-meta">{{ (float)$balance < 0 ? 'Deficit' : 'Available' }}</div>
        </div>

        <div class="stat-card top">
            <div class="stat-label"><i class="bi bi-graph-up"></i> Most Spending</div>
            <div class="stat-value">
                @if($topBucket)
                    {{ strtolower($topBucket['type']) }}
                @else
                    -
                @endif
            </div>
            <div class="stat-meta">
                @if($topBucket)
                    @if(!empty($topBucket['sub_type']))
                        {{ strtoupper($topBucket['sub_type']) }} •
                    @endif
                    {{ number_format((float)$topBucket['total'], 2) }}
                @else
                    No data
                @endif
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="tables-row">
        <!-- Category Totals -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title"><i class="bi bi-pie-chart"></i>  Totals by Category</div>
                <div class="table-subtitle">Top-level totals (bikes, meals, token, others)</div>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Category</th>
                    <th style="text-align:right;">Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($typeTotals as $type => $total)
                    <tr>
                        <td>
                            <span class="category-badge"><i class="bi bi-tag-fill"></i> {{ strtoupper($type) }}</span>
                        </td>
                        <td style="text-align:right;" class="amount-red">
                            {{ number_format((float)$total, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">
                            <div class="empty-state">
                                <div class="empty-icon">—</div>
                                <div>No spendings yet</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <!-- Detailed Breakdown -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title"><i class="bi bi-list-task"></i> Spend Breakdown (Type / Subtype)</div>
                <div class="table-subtitle">Shows bikes fuel vs maintenance, meals lunch, etc.</div>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Bucket</th>
                    <th style="text-align:right;">Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($byType as $row)
                    <tr>
                        <td>
                            <span class="category-badge">
                                <i class="bi bi-folder2-open"></i>
                                {{ strtoupper($row['type']) }}
                                @if(!empty($row['sub_type']))
                                    / {{ strtoupper($row['sub_type']) }}
                                @endif
                            </span>
                        </td>
                        <td style="text-align:right;" class="amount-red">
                            {{ number_format((float)$row['total'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">
                            <div class="empty-state">
                                <div class="empty-icon">—</div>
                                <div>No spendings yet</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
