<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $brand['name'] ?? 'FleetMan')</title>
    @php
        $fleetCssVersion = filemtime(public_path('css/fleetman.css'));
        $fleetJsVersion = filemtime(public_path('js/fleetman.js'));
        $fleetNavigationJsVersion = filemtime(public_path('js/fleetman-navigation.js'));
    @endphp
    <link rel="stylesheet" href="{{ asset('css/fleetman.css') }}?v={{ $fleetCssVersion }}">
</head>
<body class="preload" data-page="{{ $fleetman['page'] ?? '' }}">
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

                    if (!sidebar || !window.localStorage) {
                        return;
                    }

                    var pendingScrollValue = sessionStorage.getItem('fleetman.sidebar.pendingScrollTop');
                    var savedScrollValue = localStorage.getItem('fleetman.sidebar.scrollTop');
                    var pendingScrollTop = pendingScrollValue === null ? NaN : Number(pendingScrollValue);
                    var savedScrollTop = savedScrollValue === null ? NaN : Number(savedScrollValue);
                    var scrollTop = Number.isFinite(pendingScrollTop) && pendingScrollTop >= 0
                        ? pendingScrollTop
                        : (Number.isFinite(savedScrollTop) && savedScrollTop >= 0 ? savedScrollTop : 0);

                    window.__fleetmanSidebarScrollTarget = scrollTop;

                    sidebar.querySelectorAll('[data-menu-block]').forEach(function (block) {
                        var key = block.getAttribute('data-menu-key') || '';
                        var toggle = block.querySelector('[data-submenu-toggle]');

                        if (!key || !toggle) {
                            return;
                        }

                        var routeActive = block.getAttribute('data-route-active') === '1';
                        var saved = localStorage.getItem('fleetman.sidebar.open.' + key);
                        var isOpen = routeActive;

                        if (!routeActive && saved === '1') {
                            isOpen = true;
                        } else if (!routeActive && saved === '0') {
                            isOpen = false;
                        }

                        block.classList.toggle('open', isOpen);
                        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    });

                    sidebar.scrollTop = scrollTop;
                    requestAnimationFrame(function () {
                        sidebar.scrollTop = scrollTop;
                    });
                } catch (error) {
                    console.warn('Unable to restore the FleetMan sidebar state.', error);
                }
            })();
        </script>

        <main class="main-content">
            @yield('content')

            <footer class="fleet-footer">
                © {{ date('Y') }} {{ $brand['name'] ?? 'FleetMan' }}. All Rights Reserved.<br>
                System Design, Development &amp; Intellectual Property owned by
                <a href="https://itqanconsulting.com/" target="_blank"><b>{{ $brand['footer_owner'] ?? 'ITQAN Consulting' }}</b></a>
            </footer>
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script>
        window.FLEETMAN = @json($fleetman ?? []);
    </script>
    <script src="{{ asset('js/fleetman.js') }}?v={{ $fleetJsVersion }}"></script>
    <script src="{{ asset('js/fleetman-navigation.js') }}?v={{ $fleetNavigationJsVersion }}"></script>
    @stack('scripts')
</body>
</html>
