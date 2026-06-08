@extends('layouts.fleetman')

@section('title', 'Weekly Driver Fuel Summary Report | FleetMan')
@section('mobile-title', 'Weekly Report')

@section('content')
<div class="report-page" data-report-page="weekly">
    <x-fleetman.topbar :items="[['label' => 'Reports', 'route' => 'fleet.reports'], ['label' => 'Weekly Driver Fuel Summary']]" />

    <x-fleetman.title-card
        title="Weekly Driver Fuel Summary Report"
        subtitle="Week starts on Saturday and ends on Friday. The report is kept inside a fixed box, so users scroll only inside the report area."
    >
        <x-slot:action>
            <div class="report-title-actions">
                <button class="btn green" type="button" data-report-export="excel">⬇ Export Excel</button>
                <button class="btn secondary" type="button" data-report-export="csv">⬇ Export CSV</button>
            </div>
        </x-slot:action>
    </x-fleetman.title-card>

    <x-fleetman.report-filter-card
        title="Report Filters"
        subtitle="Select week and optional filters. Report table stays inside fixed laptop screen space."
    >
        <div class="report-filter-grid weekly-filter-grid">
            <div class="field"><label for="weekFilter">Week</label><select id="weekFilter"></select></div>
            <div class="field"><label for="contractFilter">Contract</label><select id="contractFilter"></select></div>
            <div class="field"><label for="vehicleFilter">Car / Vehicle</label><select id="vehicleFilter"></select></div>
            <div class="field"><label for="driverFilter">Driver</label><select id="driverFilter"></select></div>
            <div class="field"><label for="statusFilter">Status</label><select id="statusFilter"></select></div>
            <div class="field"><label for="pageSize">Rows per page</label><select id="pageSize"><option value="10">Load 10 rows</option><option value="20">Load 20 rows</option><option value="30">Load 30 rows</option><option value="50">Load 50 rows</option></select></div>
        </div>
        <div class="action-row">
            <button class="btn primary" type="button" data-report-apply>Apply Report</button>
            <button class="btn light" type="button" data-report-reset>Clear Filters</button>
        </div>
        <div id="progressWrap" class="report-progress"><div class="bar-outer"><div id="barInner" class="bar-inner"></div></div><div id="progressText" class="progress-text">Preparing Excel export...</div></div>
    </x-fleetman.report-filter-card>

    <x-fleetman.report-kpis :items="[
        ['id' => 'kpiRows', 'label' => 'Rows Found'],
        ['id' => 'kpiHours', 'label' => 'Total Work Hours'],
        ['id' => 'kpiDiesel', 'label' => 'Total Diesel (L)'],
        ['id' => 'kpiGas', 'label' => 'Total CNG/LPG Cost'],
        ['id' => 'kpiOctane', 'label' => 'Total Octane (L)'],
        ['id' => 'kpiKm', 'label' => 'Total KM'],
    ]" />

    <x-fleetman.report-shell
        title="Report Result"
        subtitle="Only this report box has horizontal scrolling. Page layout remains fixed."
        table-min-width="2680px"
    >
        <x-slot:table>
            <thead id="thead"></thead>
            <tbody id="tbody"></tbody>
        </x-slot:table>
    </x-fleetman.report-shell>
</div>
@endsection

@push('scripts')
<script>
    window.FLEETMAN.report = @json($report);
</script>
<script src="{{ asset('js/fleetman-reports.js') }}"></script>
@endpush
