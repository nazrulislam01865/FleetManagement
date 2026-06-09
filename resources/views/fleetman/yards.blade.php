@extends('layouts.fleetman')

@section('title', 'Yards | FleetMan')
@section('mobile-title', 'Yards')

@section('content')
<div class="page-section yard-page">
    <div id="yardAddPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Fleet Management'], ['label' => 'Add Yard']]">
            <x-slot:actions>
                <a href="{{ route('fleet.yards', ['action' => 'list']) }}" class="btn light">← Yard List</a>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card title="Create Parking Yard" subtitle="Create a database-backed parking yard with optional zones and documents." />

        <x-fleetman.section-card
            title="Basic Yard Information"
            description="Add the yard identity, supervisor, contact, location, capacity, charge, and status."
        >
            <div class="grid3">
                <x-fleetman.input id="yardId" label="Yard ID" required readonly />
                <x-fleetman.input id="yardName" label="Yard Name" placeholder="Example: Mirpur Parking Yard" required />
                <div class="field searchable">
                    <div class="search-label">
                        <label for="yardSupervisor">Supervisor <span class="req">*</span></label>
                        <span class="search-tag">Searchable</span>
                    </div>
                    <input id="yardSupervisor" list="yardSupervisorList" placeholder="Type employee name" autocomplete="off" required>
                    <datalist id="yardSupervisorList"></datalist>
                </div>
            </div>

            <div class="grid3 yard-form-row">
                <x-fleetman.input id="yardPhone" label="Phone Number" placeholder="01XXXXXXXXX" maxlength="11" inputmode="numeric" required />
                <x-fleetman.input id="yardSecondaryPhone" label="Secondary Contact" placeholder="Optional" maxlength="11" inputmode="numeric" />
                <x-fleetman.input id="yardWhatsapp" label="WhatsApp Number" placeholder="Optional" maxlength="11" inputmode="numeric" />
            </div>

            <div class="grid3 yard-form-row">
                <x-fleetman.input id="yardParkingSlots" label="Parking Slots" type="number" min="0" step="1" placeholder="0" placeholder="Total number of vehicles the yard can hold." />
                <x-fleetman.input id="yardMonthlyCharge" label="Monthly Charge (Taka)" type="number" min="0" step="0.01" placeholder="0.00" required />
                <div class="field">
                    <label for="yardStatus">Status <span class="req">*</span></label>
                    <select id="yardStatus" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="grid yard-form-row">
                <x-fleetman.input id="yardAddress" label="Address" placeholder="Full yard address" required />
                <div class="grid">
                    <x-fleetman.input id="yardCity" label="City" placeholder="Example: Dhaka" />
                    <x-fleetman.input id="yardArea" label="Area" placeholder="Example: Mirpur" />
                </div>
            </div>

            <div class="yard-form-row">
                <x-fleetman.textarea id="yardRemarks" label="Remarks" placeholder="Security, access, parking, or operational notes." />
            </div>
        </x-fleetman.section-card>

        <x-fleetman.section-card
            title="Parking Zone / Slot Group"
        >
            <x-slot:action>
                <button type="button" class="btn secondary" id="addYardZoneBtn">＋ Add Zone</button>
            </x-slot:action>
            <div id="yardZones" class="yard-repeat-list"></div>
            <div class="yard-empty-note" id="yardZonesEmpty">No zone added. The yard can be saved without zones.</div>
        </x-fleetman.section-card>

        <x-fleetman.section-card title="Documents (Optional)">
            <x-slot:action>
                <button type="button" class="btn secondary" id="addYardDocumentBtn">＋ Add Document</button>
            </x-slot:action>
            <div id="yardDocuments"></div>
        </x-fleetman.section-card>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetYardBtn">Clear Form</button>
            <button type="button" class="btn secondary" id="saveYardDraftBtn">Save Draft</button>
            <button type="button" class="btn primary" id="submitYardBtn">Submit Yard</button>
        </div>
    </div>

    <div id="yardListPage">
        <x-fleetman.topbar :items="[['label' => 'Fleet Management'], ['label' => 'Yard List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportYardsBtn">⬇ Export CSV</button>
                @if(data_get($fleetman, 'auth.pageAccess.canManage'))
                    <a href="{{ route('fleet.yards', ['action' => 'add']) }}" class="btn primary" id="addYardFromListBtn">＋ Add Yard</a>
                @else
                    <span class="btn primary rbac-control-muted" id="addYardFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Yard</span>
                @endif
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card title="Parking Yard List" subtitle="Search, review, edit, and maintain saved parking yard records." />

        <div class="kpi yard-kpis">
            <x-fleetman.kpi-card id="yardKpiTotal" label="Total Yards" />
            <x-fleetman.kpi-card id="yardKpiActive" label="Active Yards" />
            <x-fleetman.kpi-card id="yardKpiSlots" label="Total Parking Slots" />
            <x-fleetman.kpi-card id="yardKpiCharge" label="Total Monthly Charge" />
        </div>

        <div class="card">
            <div class="section-head">
                <div>
                    <h2>Filters</h2>
                    <p>Search by yard ID, yard name, supervisor, phone, city, or area.</p>
                </div>
            </div>
            <div class="yard-list-filters">
                <div class="field yard-filter-search">
                    <label for="yardSearch">Search</label>
                    <input id="yardSearch" placeholder="Yard, supervisor, phone, city, or area">
                </div>
                <div class="field">
                    <label for="yardStatusFilter">Status</label>
                    <select id="yardStatusFilter">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Draft">Draft</option>
                    </select>
                </div>
                <div class="field">
                    <label for="yardRowsPerPage">Rows per page</label>
                    <select id="yardRowsPerPage">
                        <option value="10">10 rows</option>
                        <option value="20">20 rows</option>
                        <option value="30">30 rows</option>
                        <option value="50">50 rows</option>
                    </select>
                </div>
                <div class="yard-filter-actions">
                    <button type="button" class="btn secondary" id="applyYardFiltersBtn">Apply</button>
                    <button type="button" class="btn light" id="clearYardFiltersBtn">Clear</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="section-head">
                <div>
                    <h2>Yard Records</h2>
                    <p id="yardListSummary">Showing saved yard records.</p>
                </div>
            </div>
            <div class="table-wrap yard-table-wrap">
                <table class="yard-table">
                    <thead>
                        <tr>
                            <th>Created At</th>
                            <th>Yard ID</th>
                            <th>Yard Name</th>
                            <th>Supervisor</th>
                            <th>Status</th>
                            <th>Phone</th>
                            <th>Area / City</th>
                            <th>Slots</th>
                            <th>Monthly Charge</th>
                            <th>Zones</th>
                            <th>Documents</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="yardTableBody"></tbody>
                </table>
            </div>
            <div class="yard-mobile-list" id="yardMobileList"></div>
            <div class="yard-pagination">
                <span id="yardPageInfo">0 records</span>
                <div class="yard-page-buttons">
                    <button type="button" class="mini-btn" id="yardPreviousPageBtn">Previous</button>
                    <span id="yardPageNumbers"></span>
                    <button type="button" class="mini-btn" id="yardNextPageBtn">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php($yardJsVersion = file_exists(public_path('js/fleetman-yards.js')) ? filemtime(public_path('js/fleetman-yards.js')) : time())
<script src="{{ asset('js/fleetman-yards.js') }}?v={{ $yardJsVersion }}"></script>
@endpush
