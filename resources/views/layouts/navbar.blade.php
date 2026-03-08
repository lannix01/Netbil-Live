<nav class="navbar bg-white border-bottom fixed-top shadow-sm" style="height:60px; z-index:1050;">
    <div class="container-fluid d-flex align-items-center">

        <!-- BURGER -->
        <button class="btn btn-light d-lg-none me-2" id="burger-toggle" style="border:1px solid #ddd;">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- BRAND -->
        <a class="navbar-brand fw-semibold text-dark ms-1" href="{{ route('dashboard') }}">
            NetBil
        </a>

        <!-- RIGHT SIDE -->
        <div class="ms-auto d-flex align-items-center gap-3">

            {{-- 🔔 NOTIFICATION BELL --}}
            <div class="dropdown">
                <button class="btn btn-light position-relative" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        3
                    </span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="width:260px;">
                    <li class="dropdown-header fw-semibold">Notifications</li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small" href="#">New user registered</a></li>
                    <li><a class="dropdown-item small" href="#">Router interface down</a></li>
                    <li><a class="dropdown-item small" href="#">Invoice system updated</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center small" href="#">View all</a></li>
                </ul>
            </div>

            {{-- 📊 SYSTEM STATS DROPDOWN --}}
            <div class="dropdown">
                <button class="btn btn-light" data-bs-toggle="dropdown">
                    <i class="bi bi-speedometer2 fs-5"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm p-2" style="width:260px;">
                    <li class="dropdown-header fw-semibold">System Status</li>
                    <li><hr class="dropdown-divider"></li>

                    <li class="px-2 small">RAM: {{ $latestMetrics->ram_percent ?? 0 }}%</li>
                    <li><div class="progress mb-2"><div class="progress-bar bg-primary" style="width: {{ $latestMetrics->ram_percent ?? 0 }}%"></div></div></li>

                    <li class="px-2 small">CPU Load: {{ $latestMetrics->cpu_percent ?? 0 }}%</li>
                    <li><div class="progress mb-2"><div class="progress-bar bg-info" style="width: {{ $latestMetrics->cpu_percent ?? 0 }}%"></div></div></li>

                    <li class="px-2 small">HDD: {{ $latestMetrics->hdd_percent ?? 0 }}%</li>
                    <li><div class="progress mb-3"><div class="progress-bar bg-warning" style="width: {{ $latestMetrics->hdd_percent ?? 0 }}%"></div></div></li>

                    <li class="px-2 small">Uptime: {{ $latestMetrics->uptime ?? '0s' }}</li>
                </ul>
            </div>

            {{-- 👤 PROFILE DROPDOWN --}}
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center" data-bs-toggle="dropdown" style="border:1px solid #ddd;">
                    <img 
                        src="{{ Auth::user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) }}"
                        class="rounded-circle me-2"
                        style="width:32px; height:32px;"
                    >
                    <span class="fw-medium d-none d-sm-inline">{{ Auth::user()->name }}</span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>

<script>
    // Sidebar Toggle
    const burger = document.getElementById('burger-toggle');
    const sidebar = document.getElementById('sidebar');

    burger.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        document.body.classList.toggle('no-scroll');
    });
</script>

<style>
    .no-scroll { overflow: hidden; }
    nav.navbar { backdrop-filter: blur(4px); }
</style>
