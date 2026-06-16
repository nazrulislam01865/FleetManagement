<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $brand['name'] ?? 'FleetMan')</title>
    @if(!empty($brand['favicon_url']))
        <link rel="icon" href="{{ $brand['favicon_url'] }}">
        <link rel="shortcut icon" href="{{ $brand['favicon_url'] }}">
        <link rel="apple-touch-icon" href="{{ $brand['favicon_url'] }}">
    @endif
    @php
        $fleetCssVersion = filemtime(public_path('css/fleetman.css'));
        $fleetPage = (string) ($fleetman['page'] ?? '');
        $fleetCoreAsset = 'js/dist/fleetman-core.min.js';
        // Keep the small record API source authoritative so pagination/export
        // safeguards work even before a production asset rebuild is run.
        $fleetRecordApiAsset = 'js/fleetman-record-api.js';
        $fleetModuleAsset = match (true) {
            in_array($fleetPage, ['vehicles', 'fuel-prices', 'fuel-recharge'], true) => 'js/dist/fleetman-operations.min.js',
            in_array($fleetPage, ['vendors', 'trips', 'drivers', 'clients', 'employees', 'driver-attendance'], true) => 'js/dist/fleetman-people.min.js',
            $fleetPage === 'master-data' => 'js/dist/fleetman-master.min.js',
            $fleetPage === 'contracts' => 'js/dist/fleetman-contracts.min.js',
            default => null,
        };
        $fleetUseSplitAssets = file_exists(public_path($fleetCoreAsset))
            && (! $fleetModuleAsset || file_exists(public_path($fleetModuleAsset)));
        $fleetCoreJsVersion = $fleetUseSplitAssets ? filemtime(public_path($fleetCoreAsset)) : filemtime(public_path('js/fleetman.js'));
        $fleetRecordApiJsVersion = filemtime(public_path($fleetRecordApiAsset));
        $fleetModuleJsVersion = $fleetUseSplitAssets && $fleetModuleAsset ? filemtime(public_path($fleetModuleAsset)) : $fleetCoreJsVersion;
        $fleetTransactionGuardJsVersion = filemtime(public_path('js/fleetman-transaction-guard.js'));
        $fleetActionLoaderJsVersion = filemtime(public_path('js/fleetman-action-loader.js'));
        $fleetSearchableDropdownJsVersion = filemtime(public_path('js/fleetman-searchable-dropdown.js'));
        $fleetNavigationJsVersion = filemtime(public_path('js/fleetman-navigation.js'));
        $fleetRbacJsVersion = filemtime(public_path('js/fleetman-rbac.js'));
        $fleetSessionJsVersion = filemtime(public_path('js/fleetman-session-timeout.js'));
        $fleetNotificationsJsVersion = filemtime(public_path('js/fleetman-notifications.js'));
        $pusherEnabled = filled(config('services.pusher.key'))
            && filled(config('services.pusher.secret'))
            && filled(config('services.pusher.app_id'))
            && filled(config('services.pusher.cluster'));
    @endphp
    <link rel="stylesheet" href="{{ asset('css/fleetman.css') }}?v={{ $fleetCssVersion }}">
</head>
<body class="preload" data-page="{{ $fleetman['page'] ?? '' }}">
    <div class="mobile-top">
        <button type="button" id="menuBtn">☰ Menu</button>
        <a href="{{ route('fleet.dashboard') }}" class="mobile-brand-link" aria-label="Go to Dashboard">
            <b>{{ $brand['name'] ?? 'FleetMan' }}</b>
        </a>
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
            <div class="fleet-notification-slot" aria-label="Notification and account controls">
                <x-fleetman.notification-bell />
                <x-fleetman.user-menu :account="$account" />
            </div>

            <div class="fleet-main-body">
                @yield('content')
            </div>

            <x-fleetman.footer :brand="$brand" />
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script>
        window.FLEETMAN = @json($fleetman ?? []);
    </script>
    <script src="{{ asset('js/fleetman-transaction-guard.js') }}?v={{ $fleetTransactionGuardJsVersion }}"></script>
    <script src="{{ asset('js/fleetman-searchable-dropdown.js') }}?v={{ $fleetSearchableDropdownJsVersion }}"></script>
    <script src="{{ asset($fleetRecordApiAsset) }}?v={{ $fleetRecordApiJsVersion }}"></script>
    @if($fleetUseSplitAssets)
        <script src="{{ asset($fleetCoreAsset) }}?v={{ $fleetCoreJsVersion }}"></script>
        @if($fleetModuleAsset)
            <script src="{{ asset($fleetModuleAsset) }}?v={{ $fleetModuleJsVersion }}"></script>
        @endif
    @else
        <script src="{{ asset('js/fleetman.js') }}?v={{ $fleetCoreJsVersion }}"></script>
    @endif
    @if(session('login_notice'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var loginToast = document.getElementById('toast');

                if (!loginToast) {
                    return;
                }

                loginToast.textContent = @json(session('login_notice'));
                loginToast.classList.add('show');
                window.setTimeout(function () {
                    loginToast.classList.remove('show');
                }, 5200);
            }, { once: true });
        </script>
    @endif
    <script src="{{ asset('js/fleetman-navigation.js') }}?v={{ $fleetNavigationJsVersion }}"></script>
    <script src="{{ asset('js/fleetman-rbac.js') }}?v={{ $fleetRbacJsVersion }}"></script>
    <script>
        window.FLEETMAN_NOTIFICATIONS = {
            userId: {{ (int) auth()->id() }},
            feedUrl: @json(route('fleet.notifications.feed')),
            readAllUrl: @json(route('fleet.notifications.read-all')),
            readUrlTemplate: @json(route('fleet.notifications.read', ['notification' => '__ID__'])),
            pusherAuthUrl: @json(route('fleet.notifications.pusher-auth')),
            pusherEnabled: @json($pusherEnabled),
            pusherKey: @json(config('services.pusher.key')),
            pusherCluster: @json(config('services.pusher.cluster')),
            pollIntervalMs: 60000
        };
    </script>
    @if($pusherEnabled)
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    @endif
    <script src="{{ asset('js/fleetman-notifications.js') }}?v={{ $fleetNotificationsJsVersion }}"></script>
    <script>
        window.FLEETMAN_SESSION = {
            timeoutMs: {{ (int) config('fleetman.inactivity_timeout_minutes', 15) * 60 * 1000 }},
            keepAliveUrl: @json(route('session.keep-alive')),
            timeoutUrl: @json(route('session.timeout')),
            loginUrl: @json(route('login'))
        };
    </script>
    <script src="{{ asset('js/fleetman-session-timeout.js') }}?v={{ $fleetSessionJsVersion }}"></script>
    @stack('scripts')
    <script src="{{ asset('js/fleetman-action-loader.js') }}?v={{ $fleetActionLoaderJsVersion }}"></script>
</body>
</html>
