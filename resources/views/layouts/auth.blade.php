<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Secure Access') · {{ $brand['name'] ?? 'FleetMan' }}</title>
    @if(!empty($brand['favicon_url']))
        <link rel="icon" href="{{ $brand['favicon_url'] }}">
        <link rel="shortcut icon" href="{{ $brand['favicon_url'] }}">
        <link rel="apple-touch-icon" href="{{ $brand['favicon_url'] }}">
    @endif
    @php
        $fleetCssVersion = file_exists(public_path('css/fleetman.css'))
            ? filemtime(public_path('css/fleetman.css'))
            : null;
        $fleetTransactionGuardJsVersion = file_exists(public_path('js/fleetman-transaction-guard.js'))
            ? filemtime(public_path('js/fleetman-transaction-guard.js'))
            : null;
    @endphp
    <link rel="stylesheet" href="{{ asset('css/fleetman.css') }}{{ $fleetCssVersion ? '?v='.$fleetCssVersion : '' }}">
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-brand-panel">
            @if(!empty($brand['logo_url']))
                <div class="login-logo login-logo-image">
                    <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] ?? 'FleetMan' }} logo">
                </div>
            @else
                <div class="login-logo">
                    🚙 {{ $brand['name'] ?? 'FleetMan' }}
                    <small>{{ $brand['tagline'] ?? 'Fleet Management System' }}</small>
                </div>
            @endif

            <h1>Manage your fleet from one secure dashboard.</h1>

            <div class="login-feature-grid">
                <div><b>🚗 Vehicles</b><span>Fleet master and documents</span></div>
                <div><b>🧭 Trips</b><span>Trip cost and route control</span></div>
                <div><b>⛽ Fuel</b><span>Recharge and price setup</span></div>
                <div><b>📝 Attendance</b><span>Driver work log tracking</span></div>
            </div>
        </section>

        <section class="login-card">
            @yield('auth-content')
        </section>

        <x-fleetman.footer :brand="$brand" class="login-footer" />
    </main>
    <script src="{{ asset('js/fleetman-transaction-guard.js') }}{{ $fleetTransactionGuardJsVersion ? '?v='.$fleetTransactionGuardJsVersion : '' }}"></script>
</body>
</html>
