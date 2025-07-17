<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light me-3" id="burger-toggle">
            <i class="bi bi-list"></i>
        </button>

        <a class="navbar-brand" href="{{ route('dashboard') }}">NetBil</a>

        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-3">Niaje, {{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>
