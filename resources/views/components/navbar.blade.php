@auth
<nav class="navbar fixed-top bg-white border-bottom" style="height:64px; z-index:1050;">
    <div class="container-fluid">

        <!-- BURGER -->
        <button class="btn btn-light me-3" id="burger-toggle">
            <span class="burger"></span>
        </button>

        <!-- BRAND -->
        <a class="navbar-brand fw-semibold text-dark" href="{{ route('dashboard') }}">
            NetBil
        </a>

        <!-- RIGHT -->
        <div class="ms-auto d-flex align-items-center gap-3">

            <!-- SYSTEM STATS -->
            <div class="dropdown">
                <button class="btn btn-light" data-bs-toggle="dropdown">
                    <i class="bi bi-speedometer2"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3" style="width:220px;">
                    <div class="small text-muted">System</div>
                    <div>CPU: <strong>--%</strong></div>
                    <div>RAM: <strong>--%</strong></div>
                    <div>Uptime: <strong>--</strong></div>
                </div>
            </div>

            <!-- NOTIFICATIONS -->
            <div class="dropdown">
                <button class="btn btn-light position-relative" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge bg-danger">0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <span class="dropdown-item-text text-muted small">No alerts</span>
                </div>
            </div>

            <!-- PROFILE -->
<div class="dropdown">
    <button class="btn btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
        <img src="{{ asset('assets/images/avatar.png') }}" 
             width="32" height="32" 
             class="rounded-circle">
        <span class="fw-medium">{{ Auth::user()->name }}</span>
    </button>

    <div class="dropdown-menu dropdown-menu-end">
        @auth
<a href="{{ route('authentication') }}" class="dropdown-item">
    <i class="bi bi-shield-lock me-2"></i> Authenticator
</a>
@endauth


        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="dropdown-item text-danger">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </button>
        </form>
    </div>
</div>


        </div>
    </div>
</nav>

<style>
.burger {
    width:22px;
    height:2px;
    background:#333;
    display:block;
    position:relative;
}
.burger::before,
.burger::after {
    content:'';
    position:absolute;
    width:22px;
    height:2px;
    background:#333;
    transition:.25s ease;
}
.burger::before { top:-6px; }
.burger::after { top:6px; }

.burger.active {
    background:transparent;
}
.burger.active::before {
    transform:rotate(45deg);
    top:0;
}
.burger.active::after {
    transform:rotate(-45deg);
    top:0;
}
</style>
@endauth
