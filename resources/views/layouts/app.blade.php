<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NetBil System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo.png') }}">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

@auth
    {{-- Navbar --}}
    @include('components.navbar')

    {{-- Sidebar --}}
    @include('components.sidebar')

    <div id="sidebar-overlay"></div>

    {{-- Main content --}}
    <main id="main-content">
        @yield('content')
    </main>
@else
    {{-- If user not authenticated, redirect to login --}}
    <script>
        window.location = "{{ route('login') }}";
    </script>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const sidebar = document.getElementById('sidebar');
const burger = document.getElementById('burger-toggle');
const burgerIcon = document.querySelector('.burger');
const overlay = document.getElementById('sidebar-overlay');
const main = document.getElementById('main-content');
const SIDEBAR_STATE_KEY = 'netbil.sidebar.state';
const DESKTOP_BREAKPOINT = 992;

function persistSidebarState(isOpen) {
    try {
        window.localStorage.setItem(SIDEBAR_STATE_KEY, isOpen ? 'open' : 'closed');
    } catch (error) {
        // Ignore storage errors so the layout still works in restricted browsers.
    }
}

function readSidebarState() {
    try {
        return window.localStorage.getItem(SIDEBAR_STATE_KEY);
    } catch (error) {
        return null;
    }
}

function applySidebarState(isOpen) {
    if (!sidebar || !overlay || !burgerIcon || !main) return;

    sidebar.classList.toggle('closed', !isOpen);
    overlay.classList.toggle('show', isOpen && window.innerWidth < DESKTOP_BREAKPOINT);
    burgerIcon.classList.toggle('active', isOpen);
    main.style.marginLeft = window.innerWidth >= DESKTOP_BREAKPOINT && isOpen ? '250px' : '0';
    document.body.classList.toggle('no-scroll', isOpen && window.innerWidth < DESKTOP_BREAKPOINT);
}

function openSidebar(shouldPersist = true) {
    applySidebarState(true);
    if (shouldPersist) persistSidebarState(true);
}

function closeSidebar(shouldPersist = true) {
    applySidebarState(false);
    if (shouldPersist) persistSidebarState(false);
}

if (sidebar && burger && burgerIcon && overlay) {
    burger.addEventListener('click', () => {
        sidebar.classList.contains('closed') ? openSidebar() : closeSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    const savedSidebarState = readSidebarState();

    if (savedSidebarState === 'closed') {
        closeSidebar(false);
    } else if (savedSidebarState === 'open') {
        openSidebar(false);
    } else if (window.innerWidth < DESKTOP_BREAKPOINT) {
        closeSidebar(false);
    } else {
        openSidebar(false);
    }

    window.addEventListener('resize', () => {
        applySidebarState(!sidebar.classList.contains('closed'));
    });
}

(() => {
    const TOAST_TTL_MS = 2000;
    const BOUND_ATTR = 'data-netbil-autoclose';
    const selectors = ['.nm-toast', '.rc-toast', '.toast-stack .toast', '.toast-container .toast'];

    function closeEl(el) {
        if (!el || !el.isConnected) return;

        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.fadeOut === 'function') {
            window.jQuery(el).fadeOut(160, () => el.remove());
            return;
        }

        el.style.transition = 'opacity .16s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 180);
    }

    function schedule(el) {
        if (!el || !el.isConnected || el.getAttribute(BOUND_ATTR) === '1') return;
        el.setAttribute(BOUND_ATTR, '1');
        setTimeout(() => closeEl(el), TOAST_TTL_MS);
    }

    function scan(root = document) {
        if (!root || typeof root.querySelectorAll !== 'function') return;
        selectors.forEach((selector) => {
            root.querySelectorAll(selector).forEach(schedule);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        scan();
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof Element)) return;
                    scan(node);
                    selectors.forEach((selector) => {
                        if (typeof node.matches === 'function' && node.matches(selector)) {
                            schedule(node);
                        }
                    });
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
})();
</script>

<style>
body.no-scroll {
    overflow: hidden;
}

#main-content {
    margin-left: 250px;
    padding-top: 80px;
    padding-left: 20px;
    transition: margin-left .25s ease;
}

#sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease;
    z-index: 1030;
}
#sidebar-overlay.show {
    opacity: 1;
    pointer-events: auto;
}

@media (max-width: 991px) {
    #main-content {
        margin-left: 0;
    }
}

.settings-tabs {
    display: flex;
    gap: 4px;
    padding: 10px;
    background: #f8fafc;
    overflow-x: auto;
}

.settings-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    border-radius: 8px;
    text-decoration: none;
    color: #444;
    font-weight: 500;
    white-space: nowrap;
    transition: all .15s ease;
}

.settings-tab:hover {
    background: #e9eef5;
    color: #111;
}

.settings-tab.active {
    background: #0d6efd;
    color: #fff;
    font-weight: 600;
    box-shadow: 0 0 0 2px #0d6efd22;
}
</style>

@include('partials.back_iconize')

</body>
</html>
