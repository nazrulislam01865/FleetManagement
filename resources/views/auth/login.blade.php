<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login · {{ $brand['name'] ?? 'FleetMan' }}</title>
    <link rel="stylesheet" href="{{ asset('css/fleetman.css') }}">
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-brand-panel">
            <div class="login-logo">🚙 {{ $brand['name'] ?? 'FleetMan' }}<small>{{ $brand['tagline'] ?? 'Fleet Management System' }}</small></div>
            <h1>Manage your fleet from one secure dashboard.</h1>
            <p>Track vehicles, trips, drivers, attendance, fuel recharge, clients, vendors, and employees from a single Laravel + MySQL system.</p>
            <div class="login-feature-grid">
                <div><b>🚗 Vehicles</b><span>Fleet master and documents</span></div>
                <div><b>🧭 Trips</b><span>Trip cost and route control</span></div>
                <div><b>⛽ Fuel</b><span>Recharge and price setup</span></div>
                <div><b>📝 Attendance</b><span>Driver work log tracking</span></div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-head">
                <span>Secure Access</span>
                <h2>Sign in to FleetMan</h2>
                <p>Use your admin account to open the dashboard.</p>
            </div>

            @if (isset($errors) && $errors->any())
                <div class="login-error">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="login-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf
                <div class="field">
                    <label for="email">Email Address <span class="req">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email', 'admin@fleetman.local') }}" autocomplete="email" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password <span class="req">*</span></label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter password">
                    <div class="hint">Default seeded password: <b>password</b></div>
                </div>
                <label class="remember-line">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in</span>
                </label>
                <button class="btn primary login-submit" type="submit">Login to Dashboard</button>
            </form>

            <div class="login-demo-note">
                <b>Demo admin</b>
                <span>admin@fleetman.local / password</span>
            </div>
        </section>
    </main>
</body>
</html>
