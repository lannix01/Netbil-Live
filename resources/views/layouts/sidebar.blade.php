<aside id="sidebar" class="main-sidebar bg-white border-end" style="width:250px; min-height:100vh; position:fixed; top:0; left:0;">

    <!-- BRAND -->
    <a href="{{ route('dashboard') }}" 
       class="d-flex align-items-center gap-2 px-3 py-3 border-bottom text-decoration-none">
        <img src="{{ asset('logo.png') }}" 
             class="rounded-circle shadow-sm" 
             style="width:36px; height:36px; object-fit:cover;">
        <span class="fw-semibold fs-5 text-dark">NetBil</span>
    </a>

    <!-- NAV -->
    <nav class="py-3">

        {{-- SECTION GROUP COMPONENT --}}
        @php
            function sectionTitle($title) {
                return "<div class='text-uppercase small text-muted mt-3 mb-1 px-3 fw-bold'>$title</div>";
            }
            function navItem($route, $icon, $label) {
                $active = request()->routeIs($route) ? 'active-link' : '';
                return "
                    <li>
                        <a href='".route($route)."'
                           class='nav-link-custom $active'>
                            <i class='bi $icon me-2'></i>$label
                        </a>
                    </li>
                ";
            }
        @endphp

        {{-- MANAGEMENT --}}
        {!! sectionTitle('Management') !!}
        <ul class="nav flex-column">
            {!! navItem('dashboard', 'bi-speedometer2', 'Dashboard') !!}
            {!! navItem('invoices.index', 'bi-coin', 'Billing') !!}
        </ul>

        {{-- USER CONTROL --}}
        {!! sectionTitle('User Control') !!}
        <ul class="nav flex-column">
            {!! navItem('chats.index', 'bi-chat', 'Communications') !!}
            {!! navItem('customers.index', 'bi-people', 'Users') !!}
        </ul>

        {{-- NETWORK --}}
        {!! sectionTitle('Network') !!}
        <ul class="nav flex-column">
            {!! navItem('dhcp.index', 'bi-hdd-stack', 'IP/DHCP Pools') !!}
            {!! navItem('devices.index', 'bi-laptop', 'Devices/Activity') !!}
            {!! navItem('services.index', 'bi-server', 'Services') !!}
        </ul>

        {{-- ANALYTICS --}}
        {!! sectionTitle('Analytics') !!}
        <ul class="nav flex-column">
            {!! navItem('reports.index', 'bi-graph-up', 'Reports') !!}
            {!! navItem('logs.index', 'bi-journal-text', 'Logs') !!}
        </ul>

        {{-- SYSTEM --}}
        {!! sectionTitle('System') !!}
        <ul class="nav flex-column">
            <li>
                <a href="/mikrotik/test" class="nav-link-custom">
                    <i class="bi bi-gear me-2"></i>System Settings
                </a>
            </li>
            {!! navItem('control.panel', 'bi-sliders', 'Control Panel') !!}
        </ul>

        {{-- FOOTER --}}
        <div class="sidebar-footer text-muted small px-3 mt-4">
            Logged in as:<br>
            <strong class="text-dark">{{ Auth::user()->name }}</strong>
        </div>

    </nav>

</aside>

<style>
    .nav-link-custom {
        display: flex;
        align-items: center;
        padding: 8px 14px;
        margin: 2px 8px;
        color: #333;
        border-radius: 6px;
        font-size: 0.95rem;
        text-decoration: none;
        transition: 0.15s ease;
    }
    .nav-link-custom:hover {
        background: #f1f5f9;
        color: #111;
    }
    .active-link {
        background: #0d6efd;
        color: white !important;
        font-weight: 600;
        box-shadow: 0 0 0 2px #0d6efd33;
    }
    .active-link:hover {
        background: #0b5ed7;
    }
    /* Responsive Sidebar */
    @media (max-width: 991px) {
        #sidebar {
            position: fixed;
            left: -260px;
            transition: left 0.25s ease;
        }
        #sidebar.open {
            left: 0;
        }
    }
</style>
