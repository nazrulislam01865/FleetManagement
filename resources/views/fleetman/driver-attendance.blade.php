@extends('layouts.fleetman')

@section('title', 'Driver Attendance | FleetMan')
@section('mobile-title', 'Attendance')

@section('content')
<div class="page-section">
    <div id="attendanceAddPage">
        <x-fleetman.topbar :items="[['label' => 'Drive Log'], ['label' => 'Add Driver Attendance']]">
            <x-slot:actions><button type="button" class="btn light" data-page-target="attendanceListPage">← Attendance List</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Add Driver Attendance" subtitle="Select a real contract first. Vehicle and driver options will load only from that contract assignment." />
        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Trip & Assignment" description="Contract comes from the real contract table. Vehicle and driver are filtered by the selected contract.">
                    <div class="grid2"><x-fleetman.input id="attendanceId" label="Attendance ID" readonly /><x-fleetman.input id="attendanceDate" label="Date" type="date" required /></div>
                    <div class="grid3" style="margin-top:16px">
                        <div class="field searchable"><div class="search-label"><label for="attendanceContract">Contract <span class="req">*</span></label><span class="search-tag">Searchable</span></div><input id="attendanceContract" list="attendanceContractList" placeholder="Type to search contract"><datalist id="attendanceContractList"></datalist><div class="hint">Type contract code or party name. Example: CNT26060137</div></div>
                        <div class="field searchable"><div class="search-label"><label for="attendanceVehicle">Vehicle <span class="req">*</span></label><span class="search-tag">Filtered</span></div><input id="attendanceVehicle" list="attendanceVehicleList" placeholder="Select vehicle from contract"><datalist id="attendanceVehicleList"></datalist><div class="hint">Only vehicles assigned to the selected contract will show.</div></div>
                        <div class="field searchable"><div class="search-label"><label for="attendanceDriver">Driver <span class="req">*</span></label><span class="search-tag">Filtered</span></div><input id="attendanceDriver" list="attendanceDriverList" placeholder="Select driver from contract"><datalist id="attendanceDriverList"></datalist><div class="hint">Driver is loaded from the selected contract/vehicle assignment.</div></div>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="2. Time & Attendance" description="Use quick action buttons for faster mobile entry. Work hours are calculated automatically.">
                    <div class="grid3"><x-fleetman.input id="attendanceStartTime" label="Start Time" type="time" required /><x-fleetman.input id="attendanceEndTime" label="End Time" type="time" /><div><label class="section-label">Status <span class="req">*</span></label><div id="attendanceStatusChoices" class="choice-grid auto-grid"></div></div></div>
                    <div class="quick-actions" style="margin-top:16px"><button type="button" class="btn secondary" data-time-now="attendanceStartTime">Start Now</button><button type="button" class="btn secondary" data-clear-field="attendanceStartTime">Clear Start</button><button type="button" class="btn secondary" data-time-now="attendanceEndTime">End Now</button><button type="button" class="btn secondary" data-clear-field="attendanceEndTime">Clear End</button></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="3. Notes" description="Add any attendance note, route note, or special event. Yard and kilometer fields are skipped for now.">
                    <x-fleetman.textarea id="attendanceNotes" label="Notes" placeholder="Any attendance note, route note, or special event." />
                </x-fleetman.section-card>
            </div>
            <aside>
                <div class="metric-box" style="margin-bottom:14px"><strong id="attendanceSummaryHours">0h 0m</strong><span>Total Work Time</span></div>
                <x-fleetman.side-note title="Dynamic contract assignment"><ul><li>Contracts come from the <b>real contract table</b>.</li><li>Vehicle and driver lists are filtered by the selected contract.</li><li>If the selected vehicle has one assigned driver, it will auto-fill.</li><li>Yard and kilometer fields are skipped for now.</li><li>After save, the record redirects to the list page automatically.</li></ul></x-fleetman.side-note>
                <div class="required-box"><b>Required before save:</b><br>Date, contract, vehicle, driver, start time, and status.</div>
            </aside>
        </div>
        <div class="save-bar"><button type="button" class="btn light" id="resetAttendanceBtn">Reset Form</button><button type="button" class="btn secondary" id="saveAttendanceDraftBtn">Save as Draft</button><button type="button" class="btn primary" id="saveAttendanceBtn">Save Attendance</button></div>
    </div>

    <div id="attendanceListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Driver Attendance List']]">
            <x-slot:actions><button type="button" class="btn light" id="exportAttendanceBtn">⬇ Export CSV</button><button type="button" class="btn primary" id="newAttendanceBtn">＋ Add Attendance</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Driver Attendance List" subtitle="Database-backed drive log / attendance records using real contract, vehicle, and driver assignments." />
        <div class="kpi"><x-fleetman.kpi-card id="attendanceKpiTotal" label="Total Logs" /><x-fleetman.kpi-card id="attendanceKpiCompleted" label="Completed Logs" /><x-fleetman.kpi-card id="attendanceKpiRunning" label="Running Logs" /><x-fleetman.kpi-card id="attendanceKpiHours" label="Total Hours" /></div>
        <div class="card">
            <div class="filters attendance-filters">
                <input id="attendanceSearch" placeholder="Search by log ID, contract, vehicle, or driver">
                <input id="attendanceFilterStatus" list="attendanceStatusFilterList" placeholder="Status"><datalist id="attendanceStatusFilterList"></datalist>
                <input id="attendanceFilterContract" list="attendanceFilterContractList" placeholder="Contract"><datalist id="attendanceFilterContractList"></datalist>
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyAttendanceFiltersBtn">Apply</button><button type="button" class="btn light" id="clearAttendanceFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap attendance-table"><table><thead><tr><th>Attendance</th><th>Date & Time</th><th>Contract / Vehicle</th><th>Driver</th><th>Hours</th><th>Status</th><th>Actions</th></tr></thead><tbody id="attendanceTbody"></tbody></table></div>
        </div>
    </div>
</div>
@endsection
