<aside id="sidebar" class="main-sidebar bg-white border-end vh-100 position-fixed">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center p-3 border-bottom">
        <img src="{{ asset('logo.png') }}" alt="icon" class="brand-image rounded-circle me-2" style="width:35px; height:35px;">
        <span class="brand-text fw-semibold">NetBil</span>
    </a>

    <div class="sidebar px-2">
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                <div class="nav-group">
                    <div class="nav-group-header text-muted text-uppercase small">Management</div>
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link">
                            <i class="bi bi-speedometer2 nav-icon"></i>Dashboard

                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('invoices.index') }}" class="nav-link">
                            <i class="bi bi-coin nav-icon"></i>Billing

                        </a>
                    </li>
                </div>

                <div class="nav-group mt-3">
                    <div class="nav-group-header text-muted text-uppercase small">User Control</div>
                    <li class="nav-item">
                        <a href="{{ route('chats.index') }}" class="nav-link">
                            <i class="bi bi-chat nav-icon"></i>Communications

                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('customers.index') }}" class="nav-link">
                            <i class="bi bi-people nav-icon"></i>Users

                        </a>
                    </li>
                </div>

                <div class="nav-group mt-3">
                    <div class="nav-group-header text-muted text-uppercase small">Network</div>
                    <li class="nav-item">
                        <a href="{{ route('dhcp.index') }}" class="nav-link">
                            <i class="bi bi-hdd-stack nav-icon"></i>IP/DHCP Pools

                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('devices.index') }}" class="nav-link">
                            <i class="bi bi-laptop nav-icon"></i>Devices/Activity

                        </a>
                    </li>
                </div>

                <div class="nav-group mt-3">
                    <div class="nav-group-header text-muted text-uppercase small">Analytics</div>
                    <li class="nav-item">
                        <a href="{{ route('reports.index') }}" class="nav-link">
                            <i class="bi bi-graph-up nav-icon"></i>Reports

                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('logs.index') }}" class="nav-link">
                            <i class="bi bi-journal-text me-2"></i> Logs
                        </a>
                    </li>

                </div>

                <div class="nav-group mt-3">
                    <div class="nav-group-header text-muted text-uppercase small">System</div>
                    <li class="nav-item">
                        <a href="mikrotik/test" class="nav-link">
                            <i class="bi bi-gear nav-icon"></i>System Settings

                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('control.panel') }}" class="nav-link">
                            <i class="bi bi-sliders nav-icon"></i>Control Panel

                        </a>
                    </li>
                </div>
            </ul>

            <div class="sidebar-footer mt-5 ps-2">

                    <span class="nav-link small">Logged in as: <strong>{{ Auth::user()->name }}</strong></span>

                <label class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" id="lockSidebar">
                    <span class="form-check-label">Lock Sidebar</span>
                </label>
            </div>
        </nav>
    </div>
</aside>
