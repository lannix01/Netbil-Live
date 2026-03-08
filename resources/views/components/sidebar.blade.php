@auth
<aside id="sidebar" class="sidebar bg-white border-end">

    <!-- BRAND -->
    <div class="sidebar-brand d-flex align-items-center gap-2 px-3 py-3 border-bottom">
        <img src="{{ asset('assets/images/logo.png') }}" 
             width="34" height="34" 
             class="rounded-circle">
        <span class="fw-bold fs-5">NetBil</span>
    </div>

    <nav class="sidebar-nav px-2 py-3">

        <!-- MANAGEMENT -->
        <div class="nav-section">Management</div>
        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('revenue.index') }}"
           class="nav-item {{ request()->routeIs('revenue.*') ? 'active' : '' }}">
            <i class="bi bi-coin"></i>
            <span>Billing</span>
        </a>

        <!-- USER CONTROL -->
        <div class="nav-section">User Control</div>
        <a href="{{ route('chats.index') }}"
           class="nav-item {{ request()->routeIs('chats.*') ? 'active' : '' }}">
            <i class="bi bi-chat-dots"></i>
            <span>Communications</span>
        </a>

        <a href="{{ route('customers.index') }}"
           class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i>
            <span>Users</span>
        </a>

        <!-- NETWORK -->
        <div class="nav-section">Network</div>
        <a href="{{ route('dhcp.index') }}"
           class="nav-item {{ request()->routeIs('dhcp.*') ? 'active' : '' }}">
            <i class="bi bi-hdd-stack"></i>
            <span>IP / DHCP Pools</span>
        </a>

        <a href="{{ route('devices.index') }}"
           class="nav-item {{ request()->routeIs('devices.*') ? 'active' : '' }}">
            <i class="bi bi-laptop"></i>
            <span>Devices & Activity</span>
        </a>

        <a href="{{ route('services.index') }}"
           class="nav-item {{ request()->routeIs('services.*') ? 'active' : '' }}">
            <i class="bi bi-server"></i>
            <span>Services</span>
        </a>

        <!-- ANALYTICS -->
        <div class="nav-section">Analytics</div>
        <a href="{{ route('reports.index') }}"
           class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i>
            <span>Reports</span>
        </a>

        <a href="{{ route('logs.index') }}"
           class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i>
            <span>Logs</span>
        </a>

        <!-- SYSTEM -->
        <div class="nav-section">System</div>
        <a href="{{ route('settings.index') }}"
           class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i>
            <span>System Settings</span>
        </a>

        <a href="{{ route('control.panel') }}"
           class="nav-item {{ request()->routeIs('control.panel') ? 'active' : '' }}">
            <i class="bi bi-sliders"></i>
            <span>Control Panel</span>
        </a>

        <!-- FOOTER -->
        <div class="sidebar-footer mt-4 px-3 small text-muted">
            Logged in as<br>
            <strong class="text-dark">{{ Auth::user()?->name ?? 'Guest' }}</strong>
        </div>

    </nav>
</aside>

<style>
.sidebar {
    position: fixed;
    top: 64px;
    left: 0;
    width: 250px;
    height: calc(100vh - 64px);
    overflow-y: auto;
    transition: transform .25s ease;
    z-index: 1040;
}
.sidebar.closed {
    transform: translateX(-100%);
}

/* NAV ITEMS */
.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    margin: 2px 6px;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
    font-size: .95rem;
    transition: background .15s ease, color .15s ease;
}
.nav-item i {
    font-size: 1.05rem;
}
.nav-item:hover {
    background: #f1f5f9;
}
.nav-item.active {
    background: #0d6efd;
    color: #fff;
    font-weight: 600;
}
.nav-item.active i {
    color: #fff;
}

/* SECTION LABEL */
.nav-section {
    font-size: .7rem;
    text-transform: uppercase;
    color: #6c757d;
    margin: 18px 12px 6px;
    font-weight: 600;
}

/* FOOTER */
.sidebar-footer {
    border-top: 1px solid #eee;
    padding-top: 12px;
}
</style>
@endauth
