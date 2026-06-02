<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
