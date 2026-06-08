@extends('layouts.fleetman')

@section('title', 'Daily Driver & Fuel Report | FleetMan')
@section('mobile-title', 'Daily Report')

@section('content')
<div class="report-page" data-report-page="daily">
    <x-fleetman.topbar :items="[['label' => 'Reports', 'route' => 'fleet.reports'], ['label' => 'Daily Driver & Fuel Report']]" />

    <x-fleetman.title-card
        title="Daily Driver & Fuel Report"
        subtitle="Daily report for driver working time, fuel use, odometer movement, mileage, and submission status. The report stays inside a fixed laptop-screen box."
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
    >
        <div class="report-filter-grid daily-filter-grid">
            <div class="field"><label for="fromDate">From Date</label><input id="fromDate" type="date"></div>
            <div class="field"><label for="toDate">To Date</label><input id="toDate" type="date"></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="contractFilter">Contract</label><span class="search-tag">Searchable</span></div><input id="contractFilter" list="contractFilterList" placeholder="All contracts" autocomplete="off"><datalist id="contractFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="vehicleFilter">Car / Vehicle</label><span class="search-tag">Filtered</span></div><input id="vehicleFilter" list="vehicleFilterList" placeholder="All vehicles" autocomplete="off"><datalist id="vehicleFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="driverFilter">Driver</label><span class="search-tag">Filtered</span></div><input id="driverFilter" list="driverFilterList" placeholder="All drivers" autocomplete="off"><datalist id="driverFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="statusFilter">Submission Status</label><span class="search-tag">Searchable</span></div><input id="statusFilter" list="statusFilterList" placeholder="All statuses" autocomplete="off"><datalist id="statusFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="fuelFilter">Fuel Type</label><span class="search-tag">Searchable</span></div><input id="fuelFilter" list="fuelFilterList" placeholder="All fuel types" autocomplete="off"><datalist id="fuelFilterList"></datalist></div>
            <div class="field"><label for="pageSize">Rows per page</label><select id="pageSize"><option value="10">Load 10 rows</option><option value="20">Load 20 rows</option><option value="30">Load 30 rows</option><option value="50">Load 50 rows</option></select></div>
        </div>
        <div class="action-row">
            <button class="btn primary" type="button" data-report-apply>Apply Report</button>
            <button class="btn light" type="button" data-report-reset>Clear Filters</button>
        </div>
        <div id="progressWrap" class="report-progress"><div class="bar-outer"><div id="barInner" class="bar-inner"></div></div><div id="progressText" class="progress-text">Preparing Excel export...</div></div>
    </x-fleetman.report-filter-card>

    <x-fleetman.report-kpis :items="[
        ['id' => 'kpiRows', 'label' => 'Total Entries'],
        ['id' => 'kpiHours', 'label' => 'Total Work Hours'],
        ['id' => 'kpiDiesel', 'label' => 'Total Diesel (L)'],
        ['id' => 'kpiGas', 'label' => 'Total CNG/LPG Cost'],
        ['id' => 'kpiKm', 'label' => 'Total KM'],
        ['id' => 'kpiDraft', 'label' => 'Draft Entries'],
    ]" />

    <x-fleetman.report-shell
        title="Report Result"
        table-min-width="2280px"
    >
        <x-slot:table>
            <thead>
                <tr>
                    <th rowspan="2" class="sticky-left sticky-1">Entry ID</th>
                    <th rowspan="2" class="sticky-left sticky-2">Date</th>
                    <th rowspan="2" class="sticky-left sticky-3">Contract</th>
                    <th rowspan="2" class="sticky-left sticky-4">Car</th>
                    <th rowspan="2">Driver</th>
                    <th colspan="3" class="group-head">Driver Time</th>
                    <th colspan="3" class="group-head">Fuel</th>
                    <th colspan="5" class="group-head">Odometer & Mileage</th>
                    <th rowspan="2">Draft / Submitted</th>
                    <th rowspan="2">Submitted By</th>
                </tr>
                <tr>
                    <th class="sub-head">Start Time</th>
                    <th class="sub-head">End Time</th>
                    <th class="sub-head">Total Time (hrs)</th>
                    <th class="sub-head">Diesel (L)</th>
                    <th class="sub-head">CNG/LPG Cost (৳)</th>
                    <th class="sub-head">Octane (L)</th>
                    <th class="sub-head">Start KM</th>
                    <th class="sub-head">End KM</th>
                    <th class="sub-head">Total KM</th>
                    <th class="sub-head">Tk(KM)</th>
                    <th class="sub-head">Mileage (KM/L)</th>
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
<script src="{{ asset('js/fleetman-reports.js') }}?v={{ filemtime(public_path('js/fleetman-reports.js')) }}"></script>
@endpush
