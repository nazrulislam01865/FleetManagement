@extends('layouts.fleetman')

@section('title', 'Monthly Driver & Fuel Summary Report | FleetMan')
@section('mobile-title', 'Monthly Report')

@section('content')
<div class="report-page" data-report-page="monthly">
    <x-fleetman.topbar :items="[['label' => 'Reports', 'route' => 'fleet.reports'], ['label' => 'Monthly Driver & Fuel Summary']]" />

    <x-fleetman.title-card
        title="Monthly Driver & Fuel Summary Report"
        subtitle="On screen, users see a monthly summary list. The Excel export downloads a wide date-wise monthly report from the first day to the last day of the selected month, similar to the weekly Excel format."
    >
        <x-slot:action>
            <div class="report-title-actions">
                <button class="btn green" type="button" data-report-export="monthly-datewise-excel">⬇ Export Date-wise Monthly Excel</button>
                <button class="btn secondary" type="button" data-report-export="csv">⬇ Export Summary CSV</button>
            </div>
        </x-slot:action>
    </x-fleetman.title-card>

    <x-fleetman.report-filter-card
        title="Report Filters"
        subtitle="Select a month and optional filters. The screen list stays summarized; the exported Excel contains all date-wise columns."
    >
        <div class="report-filter-grid monthly-filter-grid">
            <div class="field"><label for="monthFilter">Month</label><select id="monthFilter"></select></div>
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
        <div id="progressWrap" class="report-progress"><div class="bar-outer"><div id="barInner" class="bar-inner"></div></div><div id="progressText" class="progress-text">Preparing date-wise monthly Excel...</div></div>
        <div class="note-panel"><b>Excel export format:</b> Entry ID, Contract, Car, Driver, Total Hours, then date-wise columns for every day of the selected month: Diesel (L), CNG/LPG Cost, Octane (L), then monthly totals, Tk(KM), and odometer summary.</div>
    </x-fleetman.report-filter-card>

    <x-fleetman.report-kpis :items="[
        ['id' => 'kpiRows', 'label' => 'Summary Rows'],
        ['id' => 'kpiDays', 'label' => 'Total Active Days'],
        ['id' => 'kpiHours', 'label' => 'Total Work Hours'],
        ['id' => 'kpiDiesel', 'label' => 'Total Diesel (L)'],
        ['id' => 'kpiGas', 'label' => 'Total CNG/LPG Cost'],
        ['id' => 'kpiKm', 'label' => 'Total KM'],
    ]" />

    <x-fleetman.report-shell
        title="Monthly Summary List"
        subtitle="Only this report box has horizontal scrolling. Vertical space is fixed for 10 rows."
        table-min-width="1900px"
    >
        <x-slot:table>
            <thead>
                <tr>
                    <th rowspan="2" class="sticky-left sticky-1">Summary ID</th>
                    <th rowspan="2" class="sticky-left sticky-2">Month</th>
                    <th rowspan="2" class="sticky-left sticky-3">Contract</th>
                    <th rowspan="2" class="sticky-left sticky-4">Car</th>
                    <th rowspan="2">Driver</th>
                    <th colspan="2" class="group-head">Usage Summary</th>
                    <th colspan="3" class="group-head">Fuel Summary</th>
                    <th colspan="5" class="group-head">Odometer Summary</th>
                    <th rowspan="2">Last Status</th>
                    <th rowspan="2">Submitted By</th>
                </tr>
                <tr>
                    <th class="sub-head">Active Days</th>
                    <th class="sub-head">Total Hours</th>
                    <th class="sub-head">Diesel (L)</th>
                    <th class="sub-head">CNG/LPG Cost (৳)</th>
                    <th class="sub-head">Octane (L)</th>
                    <th class="sub-head">Month Start KM</th>
                    <th class="sub-head">Month End KM</th>
                    <th class="sub-head">Total KM</th>
                    <th class="sub-head">Tk(KM)</th>
                    <th class="sub-head">Avg Mileage</th>
                </tr>
            </thead>
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
