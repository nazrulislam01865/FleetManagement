@extends('layouts.fleetman')

@section('title', 'FleetMan Dashboard')
@section('mobile-title', 'Dashboard')

@section('content')
    @php
        $stats = $fleetman['dashboard']['stats'] ?? [];
        $finance = $fleetman['dashboard']['finance'] ?? [];
        $recent = $fleetman['dashboard']['recent'] ?? [];
        $warnings = $fleetman['dashboard']['warnings'] ?? [];
        $latestFuel = $finance['fuel_rate'] ?? null;
        $access = $fleetman['dashboard']['access'] ?? [];
        $canFleet = static fn (string $permission): bool => auth()->user()?->canFleet($permission) ?? false;
    @endphp

    <x-fleetman.topbar :items="[['label' => 'DASHBOARD']]" />

    <section class="dashboard-hero">
        <div>
            <span class="dashboard-eyebrow">Fleet control center</span>
            <h1>Welcome back, {{ auth()->user()->name ?? ($account['name'] ?? 'User') }}</h1>
            <p>Monitor trips, vehicles, drivers, fuel, clients, vendors, employees, and attendance from one place.</p>
            <div class="hero-actions">
                @if($canFleet('driver_attendance.view') && $canFleet('driver_attendance.manage'))
                    <a class="btn primary" href="{{ route('fleet.driver-attendance', ['action' => 'add']) }}">📝 Add Log</a>
                @else
                    <span class="btn primary dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">🔒 Add Log</span>
                @endif
                @if($canFleet('fuel_recharge.view') && $canFleet('fuel_recharge.manage'))
                    <a class="btn secondary" href="{{ route('fleet.fuel-recharge', ['action' => 'add']) }}">⛽ Add Fuel</a>
                @else
                    <span class="btn secondary dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">🔒 Add Fuel</span>
                @endif
                @if($canFleet('trips.view') && $canFleet('trips.manage'))
                    <a class="btn light" href="{{ route('fleet.trips', ['action' => 'add']) }}">🧭 Add Trip</a>
                @else
                    <span class="btn light dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">🔒 Add Trip</span>
                @endif
            </div>
        </div>
        <div class="dashboard-hero-card">
            <small>Today</small>
            <strong>{{ now()->format('d M Y') }}</strong>
            <span>{{ now()->format('l') }}</span>
            <div class="hero-mini-grid">
                <div><b>৳ {{ number_format($finance['trip_cost'] ?? 0) }}</b><small>Trip cost</small></div>
                <div><b>৳ {{ number_format($finance['payroll'] ?? 0) }}</b><small>Payroll base</small></div>
            </div>
        </div>
    </section>

    <div class="dashboard-kpis">
        @foreach($stats as $stat)
            @php($statAllowed = $canFleet($stat['permission'] ?? ''))
            @if($statAllowed)
                <a class="dashboard-kpi-card" href="{{ route($stat['route']) }}">
                    <div class="dashboard-kpi-icon">{{ $stat['icon'] }}</div>
                    <div>
                        <strong>{{ $stat['value'] }}</strong>
                        <span>{{ $stat['label'] }}</span>
                        <small>{{ $stat['helper'] }}</small>
                    </div>
                </a>
            @else
                <div class="dashboard-kpi-card dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">
                    <div class="dashboard-kpi-icon">🔒</div>
                    <div>
                        <strong>—</strong>
                        <span>{{ $stat['label'] }}</span>
                        <small>Access not granted</small>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="dashboard-grid dashboard-grid-wide">
        <x-fleetman.section-card title="Financial & Fuel Overview" class="dashboard-panel">
            <div class="finance-grid">
                <div class="finance-box"><small>Total Trip Cost</small><b>৳ {{ number_format($finance['trip_cost'] ?? 0) }}</b></div>
                <div class="finance-box"><small>Driver + Employee Salary</small><b>৳ {{ number_format($finance['payroll'] ?? 0) }}</b></div>
                <div class="finance-box"><small>Attendance Distance</small><b>{{ number_format($finance['attendance_km'] ?? 0) }} km</b></div>
                <div class="finance-box">
                    <small>Latest Fuel Rate</small>
                    <b>{{ $latestFuel ? (($latestFuel['fuelType'] ?? 'Fuel') . ' ৳' . number_format((float) ($latestFuel['price'] ?? 0), 2)) : '-' }}</b>
                </div>
            </div>
        </x-fleetman.section-card>

        <x-fleetman.section-card title="Operational Alerts" class="dashboard-panel">
            <div class="warning-list">
                @foreach($warnings as $warning)
                    <div class="warning-row">
                        <div><b>{{ $warning['title'] }}</b><span>{{ $warning['description'] }}</span></div>
                        <strong>{{ $warning['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        </x-fleetman.section-card>
    </div>

    <div class="dashboard-grid dashboard-grid-wide">
        <x-fleetman.section-card title="Recent Trips" class="dashboard-panel">
            <div class="compact-list">
                @if(!($access['trips'] ?? false))
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                @else
                @forelse(($recent['trips'] ?? []) as $trip)
                    <a href="{{ route('fleet.trips') }}" class="compact-row">
                        <div class="compact-icon">🧭</div>
                        <div><b>{{ $trip['tripId'] ?? '-' }} · {{ $trip['purpose'] ?? 'Trip' }}</b><span>{{ $trip['vehicle'] ?? '-' }} / {{ $trip['driver'] ?? '-' }}</span></div>
                        @php($tripBalance = (float) ($trip['balanceDue'] ?? max(0, (float) ($trip['totalCost'] ?? 0) - (float) ($trip['paidAmount'] ?? 0))))
                        <span class="badge {{ $tripBalance <= 0.009 ? 'ok' : 'warn' }}">{{ $tripBalance <= 0.009 ? 'Paid' : ('Balance ৳' . number_format($tripBalance, 2)) }}</span>
                    </a>
                @empty
                    <div class="empty compact-empty">No trips found.</div>
                @endforelse
                @endif
            </div>
        </x-fleetman.section-card>

        <x-fleetman.section-card title="Recent Vehicles" class="dashboard-panel">
            <div class="compact-list">
                @if(!($access['vehicles'] ?? false))
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                @else
                @forelse(($recent['vehicles'] ?? []) as $vehicle)
                    <a href="{{ route('fleet.vehicles') }}" class="compact-row">
                        <div class="compact-icon">🚗</div>
                        <div><b>{{ $vehicle['name'] ?? '-' }}</b><span>{{ $vehicle['id'] ?? '-' }} / {{ $vehicle['regNo'] ?? '-' }}</span></div>
                        <span class="badge soft">{{ $vehicle['category'] ?? 'Vehicle' }}</span>
                    </a>
                @empty
                    <div class="empty compact-empty">No vehicles found.</div>
                @endforelse
                @endif
            </div>
        </x-fleetman.section-card>
    </div>

    <div class="dashboard-grid dashboard-grid-wide">
        <x-fleetman.section-card title="Recent Drivers" class="dashboard-panel">
            <div class="compact-list">
                @if(!($access['drivers'] ?? false))
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                @else
                @forelse(($recent['drivers'] ?? []) as $driver)
                    <a href="{{ route('fleet.drivers') }}" class="compact-row">
                        <div class="compact-icon">🧑‍✈️</div>
                        <div><b>{{ $driver['fullName'] ?? '-' }}</b><span>{{ $driver['driverId'] ?? '-' }} / {{ $driver['contact'] ?? '-' }}</span></div>
                        <span class="badge {{ ($driver['status'] ?? '') === 'Active' ? 'ok' : 'soft' }}">{{ $driver['status'] ?? '-' }}</span>
                    </a>
                @empty
                    <div class="empty compact-empty">No drivers found.</div>
                @endforelse
                @endif
            </div>
        </x-fleetman.section-card>

        <x-fleetman.section-card title="Recent Parties" class="dashboard-panel">
            <div class="compact-list">
                @if(!($access['clients'] ?? false))
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                @else
                @forelse(($recent['clients'] ?? []) as $client)
                    <a href="{{ route('fleet.clients') }}" class="compact-row">
                        <div class="compact-icon">🏢</div>
                        <div><b>{{ $client['clientName'] ?? '-' }}</b><span>{{ $client['clientId'] ?? '-' }} / {{ $client['phone'] ?? '-' }}</span></div>
                        <span class="badge {{ ($client['status'] ?? '') === 'Active' ? 'ok' : 'warn' }}">{{ $client['status'] ?? '-' }}</span>
                    </a>
                @empty
                    <div class="empty compact-empty">No clients found.</div>
                @endforelse
                @endif
            </div>
        </x-fleetman.section-card>
    </div>
@endsection
