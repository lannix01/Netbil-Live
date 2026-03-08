@php
    use App\Modules\PettyCash\Support\PettyAccess;

    $r = request()->route()?->getName();

    // true if route is exactly the name OR starts with "name."
    $is = function(string $name) use ($r) {
        return $r === $name || str_starts_with((string) $r, $name . '.');
    };

    // Logged-in petty user (guard: petty)
    $u = auth('petty')->user();
    $can = static fn (string $permission): bool => PettyAccess::allows($u, $permission);

    $showOverview = $can('dashboard.view') || $can('reports.view') || $can('ledger.view');
    $showMoneyIn = $can('credits.view') || $can('credits.create') || $can('credits.edit') || $can('batches.view');
    $showSpendings =
        $can('bikes.view') || $can('bikes.create') || $can('bikes.edit') ||
        $can('meals.view') || $can('meals.create') || $can('meals.edit') ||
        $can('tokens.view') || $can('tokens.create_hostel') || $can('tokens.record_payment') || $can('tokens.edit_payment') ||
        $can('others.view') || $can('others.create') || $can('others.edit');
    $showMaster =
        $can('bikes_master.view') || $can('bikes_master.create') || $can('bikes_master.edit') ||
        $can('respondents.view') || $can('respondents.create') || $can('respondents.edit') ||
        $can('maintenances.view') || $can('maintenances.create_service') || $can('maintenances.unroadworthy');
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    /* IMPORTANT:
       This file only styles nav items.
       Sidebar layout (positioning/mobile transform) is handled by layouts/app.blade.php
    */

    .nav-wrap { padding: 6px; }

    .nav-brand {
        padding: 10px 10px 14px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .nav-brand .name {
        font-size: 18px;
        font-weight: 900;
        color: var(--text);
        letter-spacing: -0.3px;
        line-height: 1.1;
    }

    .nav-brand .sub {
        font-size: 12px;
        color: var(--muted);
        margin-top: 2px;
    }

    .nav-close {
        display: none;
        border: 1px solid var(--border);
        background: #fff;
        color: #344054;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-weight: 900;
        cursor: pointer;
        line-height: 1;
    }

    /* show close button only on mobile (because sidebar becomes a drawer) */
    @media (max-width: 980px) {
        .nav-close { display: inline-flex; align-items: center; justify-content: center; }
    }

    .nav-group { margin: 14px 0 18px; }

    .nav-label {
        font-size: 11px;
        font-weight: 800;
        color: #8a8a8a;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0 10px 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-label i { opacity: .7; }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        color: #475467;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        transition: background .15s ease, color .15s ease;
        margin-bottom: 2px;
    }

    .nav-link:hover {
        background: #f4f4f5;
        color: #111827;
    }

    .nav-link.active {
        background: #111827;
        color: #fff;
    }

    .nav-link .nav-icon {
        width: 20px;
        text-align: center;
        font-size: 16px;
        color: #98a2b3;
    }

    .nav-link.active .nav-icon { color: #fff; }

    .nav-divider {
        height: 1px;
        background: #eef2f6;
        margin: 10px 0;
    }

    /* Footer "logged in as" */
    .nav-footer {
        margin: 10px 6px 6px;
        padding: 10px 12px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #eef2f6;
        color: #475467;
        font-size: 12px;
        line-height: 1.35;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    .nav-footer .icon {
        width: 20px;
        text-align: center;
        font-size: 16px;
        color: #98a2b3;
        margin-top: 1px;
    }
    .nav-footer .k {
        font-size: 11px;
        color: #667085;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 3px;
    }
    .nav-footer .v {
        font-weight: 800;
        color: #111827;
        word-break: break-word;
    }
    .nav-footer .s {
        color: #667085;
        font-size: 11px;
        margin-top: 2px;
        word-break: break-word;
    }
</style>

<aside class="sidebar">
    <div class="nav-wrap">
        <div class="nav-brand">
            <div>
                <div class="name">PettyCash</div>
                <div class="sub">Operations ledger</div>
            </div>
            <button class="nav-close" type="button" onclick="toggleSidebar()">×</button>
        </div>

        @if($showOverview)
            <div class="nav-group">
                <div class="nav-label"><i class="bi bi-grid"></i> Overview</div>

                @if($can('dashboard.view'))
                    <a class="nav-link {{ $is('petty.dashboard') ? 'active' : '' }}" href="{{ route('petty.dashboard') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
                    </a>
                @endif

                @if($can('reports.view'))
                    <a class="nav-link {{ $is('petty.reports') ? 'active' : '' }}" href="{{ route('petty.reports.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-file-earmark-text nav-icon"></i> Reports
                    </a>
                @endif

                @if($can('ledger.view'))
                    <a class="nav-link {{ $is('petty.ledger') ? 'active' : '' }}" href="{{ route('petty.ledger.spendings') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-journal-text nav-icon"></i> Ledger
                    </a>
                @endif
            </div>
        @endif

        @if($showMoneyIn)
            <div class="nav-group">
                <div class="nav-label"><i class="bi bi-arrow-down-circle"></i> Money In</div>

                @if($can('credits.view') || $can('credits.create') || $can('credits.edit'))
                    <a class="nav-link {{ $is('petty.credits') ? 'active' : '' }}" href="{{ route('petty.credits.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-plus-circle nav-icon"></i> Credits
                    </a>
                @endif

                @if($can('batches.view'))
                    <a class="nav-link {{ $is('petty.batches') ? 'active' : '' }}" href="{{ route('petty.batches.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-collection nav-icon"></i> Batches
                    </a>
                @endif
            </div>
        @endif

        @if($showSpendings)
            <div class="nav-group">
                <div class="nav-label"><i class="bi bi-arrow-up-circle"></i> Spendings</div>

                @if($can('bikes.view') || $can('bikes.create') || $can('bikes.edit'))
                    <a class="nav-link {{ $is('petty.bikes') ? 'active' : '' }}" href="{{ route('petty.bikes.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-truck nav-icon"></i> Motor Vehicles
                    </a>
                @endif

                @if($can('meals.view') || $can('meals.create') || $can('meals.edit'))
                    <a class="nav-link {{ $is('petty.meals') ? 'active' : '' }}" href="{{ route('petty.meals.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-cup-hot nav-icon"></i> Meals
                    </a>
                @endif

                @if($can('tokens.view') || $can('tokens.create_hostel') || $can('tokens.record_payment') || $can('tokens.edit_payment'))
                    <a class="nav-link {{ $is('petty.tokens') ? 'active' : '' }}" href="{{ route('petty.tokens.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-phone nav-icon"></i> Tokens
                    </a>
                @endif

                @if($can('others.view') || $can('others.create') || $can('others.edit'))
                    <a class="nav-link {{ $is('petty.others') ? 'active' : '' }}" href="{{ route('petty.others.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-three-dots nav-icon"></i> Other Expenses
                    </a>
                @endif
            </div>
        @endif

        @if($showMaster)
            <div class="nav-group">
                <div class="nav-label"><i class="bi bi-database"></i> Master Data</div>

                @if($can('bikes_master.view') || $can('bikes_master.create') || $can('bikes_master.edit'))
                    <a class="nav-link {{ $is('petty.bikes_master') ? 'active' : '' }}" href="{{ route('petty.bikes_master.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-car-front nav-icon"></i> Vehicles
                    </a>
                @endif

                @if($can('respondents.view') || $can('respondents.create') || $can('respondents.edit'))
                    <a class="nav-link {{ $is('petty.respondents') ? 'active' : '' }}" href="{{ route('petty.respondents.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-people nav-icon"></i> Respondents
                    </a>
                @endif

                @if($can('maintenances.view') || $can('maintenances.create_service') || $can('maintenances.unroadworthy'))
                    <a class="nav-link {{ $is('petty.maintenances') ? 'active' : '' }}"
                       href="{{ route('petty.maintenances.index') }}"
                       onclick="if(window.innerWidth<=980)toggleSidebar()">
                        <i class="bi bi-gear nav-icon"></i> Bike Service
                    </a>
                @endif
            </div>
        @endif

        <div class="nav-divider"></div>

        {{-- Footer: Logged in as --}}
        <div class="nav-footer">
            <div class="icon"><i class="bi bi-person-circle"></i></div>
            <div>
                <div class="k">Logged in as</div>
                <div class="v">{{ $u?->name ?? 'Guest' }}</div>
                <div class="s">{{ $u?->email ?? '' }}</div>
            </div>
        </div>
    </div>
</aside>
