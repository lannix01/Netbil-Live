<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NetBil') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Tailwind Support (if not using Vite, uncomment below) -->
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}

    <!-- Laravel Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            overflow-x: hidden;
        }
        #sidebar {
            width: 250px;
            transition: margin-left 0.3s ease;
        }
        #sidebar.collapsed {
            margin-left: -250px;
        }
        #main-content {
            transition: margin-left 0.3s ease;
            margin-left: 250px;
        }
        #main-content.expanded {
            margin-left: 0;
        }

        @media (max-width: 768px) {
            #main-content {
                margin-left: 0 !important;
            }
        }
         .pagination .page-link {
        padding: 0.3rem 0.6rem;
        font-size: 0.875rem;
    }

        .nav-link {
            color: #333;
            font-weight: 500;
            display: flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            white-space: nowrap;
            border-radius: 0.375rem;
        }

        .nav-link:hover {
            background-color: #9bc0e5;
        }

        .nav-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .brand-link {
            display: flex;
            align-items: center;
            padding: 1rem;
            text-decoration: none;
            color: #000;
            font-weight: bold;
        }

        .brand-image {
            height: 32px;
            margin-right: 0.5rem;
        }

        /* Tailwind-style Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100 text-gray-900">

@auth
    @include('layouts.navbar')

    <div class="d-flex">
        @include('layouts.sidebar')

        <div id="main-content" class="flex-grow-1 py-4 px-3">
            @yield('content')
        </div>
    </div>
@else
    <main class="py-5 px-4">
        @yield('content')
    </main>
@endauth

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const burger = document.getElementById('burger-toggle');
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('main-content');

        burger?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        });

        const lockToggle = document.getElementById('lockSidebar');
        const isLocked = localStorage.getItem('sidebarLocked') === 'true';

        if (isLocked) {
            sidebar.classList.remove('collapsed');
            lockToggle.checked = true;
        }

        lockToggle?.addEventListener('change', function () {
            if (this.checked) {
                localStorage.setItem('sidebarLocked', 'true');
                sidebar.classList.remove('collapsed');
            } else {
                localStorage.removeItem('sidebarLocked');
                sidebar.classList.add('collapsed');
            }
        });
    });
</script>

@stack('scripts')
</body>
</html>
