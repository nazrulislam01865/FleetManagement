@extends('layouts.fleetman')

@section('title', 'Driver Attendance | FleetMan')
@section('mobile-title', 'Attendance')

@section('content')
<div class="page-section">
    <div id="attendanceAddPage">
        <x-fleetman.topbar :items="[['label' => 'Drive Log'], ['label' => 'Add Log']]">
            <x-slot:actions><button type="button" class="btn light" data-page-target="attendanceListPage">← Log List</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Add Log" subtitle="Select a real contract first. Vehicle and driver options will load only from that contract assignment." />
        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Trip & Assignment">
                    <div class="grid2"><x-fleetman.input id="attendanceId" label="Attendance ID" required readonly /><x-fleetman.input id="attendanceDate" label="Date" type="date" required /></div>
                    <div class="grid3" style="margin-top:16px">
                        <div class="field searchable"><div class="search-label"><label for="attendanceContract">Contract <span class="req">*</span></label><span class="search-tag">Searchable</span></div><input id="attendanceContract" list="attendanceContractList" placeholder="Type to search contract" autocomplete="off" required aria-required="true"><datalist id="attendanceContractList"></datalist></div>
                        <div class="field searchable"><div class="search-label"><label for="attendanceVehicle">Vehicle <span class="req">*</span></label><span class="search-tag">Filtered</span></div><input id="attendanceVehicle" list="attendanceVehicleList" placeholder="Select vehicle from contract" autocomplete="off" required aria-required="true"><datalist id="attendanceVehicleList"></datalist></div>
                        <div class="field searchable"><div class="search-label"><label for="attendanceDriver">Driver <span class="req">*</span></label><span class="search-tag">Filtered</span></div><input id="attendanceDriver" list="attendanceDriverList" placeholder="Select driver from contract" autocomplete="off" required aria-required="true"><datalist id="attendanceDriverList"></datalist></div>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="2. Time & Attendance">
                    <div class="attendance-time-grid">
                        <div class="attendance-time-group">
                            <x-fleetman.input id="attendanceStartTime" label="Start Time" type="time" step="60" required />
                            <button type="button" class="btn secondary" data-time-now="attendanceStartTime">Start Now</button>
                            <button type="button" class="btn secondary" data-clear-field="attendanceStartTime">Clear Start</button>
                        </div>
                        <div class="attendance-time-group">
                            <x-fleetman.input id="attendanceEndTime" label="End Time" type="time" step="60" />
                            <button type="button" class="btn secondary" data-time-now="attendanceEndTime">End Now</button>
                            <button type="button" class="btn secondary" data-clear-field="attendanceEndTime">Clear End</button>
                        </div>
                    </div>
                    <div class="field attendance-status-row" id="attendanceStatusField">
                        <label class="section-label" id="attendanceStatusLabel">Status <span class="req">*</span></label>
                        <div id="attendanceStatusChoices" class="choice-grid auto-grid" role="group" aria-labelledby="attendanceStatusLabel" aria-required="true" tabindex="-1"></div>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="3. Notes">
                    <x-fleetman.textarea id="attendanceNotes" label="Notes" placeholder="Any attendance note, route note, or special event." />
                </x-fleetman.section-card>
            </div>
        </div>
        <div class="save-bar"><button type="button" class="btn light" id="resetAttendanceBtn">Reset Form</button><button type="button" class="btn secondary" id="saveAttendanceDraftBtn">Save as Draft</button><button type="button" class="btn primary" id="saveAttendanceBtn">Save Attendance</button></div>
    </div>

    <div id="attendanceListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Log List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportAttendanceBtn">⬇ Export CSV</button>
                @if(data_get($fleetman, 'auth.pageAccess.canManage'))
                    <a href="{{ route('fleet.driver-attendance', ['action' => 'add']) }}" class="btn primary" id="addLogFromListBtn">＋ Add Log</a>
                @else
                    <span class="btn primary rbac-control-muted" id="addLogFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Log</span>
                @endif
            </x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Log List" subtitle="Database-backed drive log / attendance records using real contract, vehicle, and driver assignments." />
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
