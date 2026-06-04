<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $brand['name'] ?? 'FleetMan')</title>
    <link rel="stylesheet" href="{{ asset('css/fleetman.css') }}">
</head>
<body data-page="{{ $fleetman['page'] ?? '' }}">
    <div class="mobile-top">
        <button type="button" id="menuBtn">☰ Menu</button>
        <b>{{ $brand['name'] ?? 'FleetMan' }}</b>
        <span>@yield('mobile-title', 'Fleet')</span>
    </div>
    <div class="drawer-backdrop" id="backdrop"></div>

    <div class="app">
        <x-fleetman.sidebar
            :brand="$brand"
            :account="$account"
            :menu-groups="$menuGroups"
            :active-menu="$activeMenu"
        />
        <script>
            (function () {
                try {
                    var sidebar = document.getElementById('fleetSidebar');
                    if (!sidebar || !window.localStorage) return;
                    var scrollTop = Number(localStorage.getItem('fleetman.sidebar.scrollTop') || 0);
                    if (scrollTop > 0) sidebar.scrollTop = scrollTop;
                    sidebar.querySelectorAll('[data-menu-block]').forEach(function (block) {
                        var key = block.getAttribute('data-menu-key') || '';
                        var toggle = block.querySelector('[data-submenu-toggle]');
                        if (!key || !toggle) return;
                        var saved = localStorage.getItem('fleetman.sidebar.open.' + key);
                        if (saved !== '1' && saved !== '0') return;
                        var isOpen = saved === '1';
                        block.classList.toggle('open', isOpen);
                        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    });
                } catch (error) {}
            })();
        </script>

        <main class="main-content">
            @yield('content')

            <footer class="fleet-footer">
                © {{ date('Y') }} {{ $brand['name'] ?? 'FleetMan' }}. All Rights Reserved.<br>
                System Design, Development &amp; Intellectual Property owned by
                <a href="#">{{ $brand['footer_owner'] ?? 'ITQAN Consulting' }}</a>
            </footer>
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script>
        window.FLEETMAN = @json($fleetman ?? []);
    </script>
    <script src="{{ asset('js/fleetman.js') }}"></script>
    @stack('scripts')
</body>
</html>
