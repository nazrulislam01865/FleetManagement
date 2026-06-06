@extends('layouts.fleetman')

@section('title', 'Trips | FleetMan')
@section('mobile-title', 'Trips')

@section('content')
<div class="page-section">
    <div id="tripAddPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Add Trip']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-page-target="tripListPage">← Trip List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Trip"
            subtitle="Create trips dynamically using saved vehicles from the vehicle table and saved drivers from the driver table."
        >
            <x-slot:action>
                <button type="button" class="btn secondary" id="loadTripSampleBtn">Use existing trip data</button>
            </x-slot:action>
        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <x-fleetman.section-card
                    title="1. Basic Trip Information"
                >
                    <div class="grid3">
                        <x-fleetman.input id="tripId" label="Trip ID" required readonly />
                        <x-fleetman.input id="tripStartDate" label="Start Date" type="date" required />
                        <x-fleetman.input id="tripEndDate" label="End Date" type="date" required />
                    </div>

                    <div class="field" style="margin-top:16px">
                        <label>Vehicle <span class="req">*</span></label>
                        <div class="picker-field">
                            <div class="picker-value" id="tripVehicleSummary">
                                <div><b>No vehicle selected</b></div>
                            </div>
                            <button type="button" class="btn secondary" id="selectTripVehicleBtn">Select Vehicle</button>
                        </div>
                        <div class="quick-chips" id="recentTripVehicleChips"></div>
                    </div>

                    <div class="field" style="margin-top:16px">
                        <label>Driver <span class="req">*</span></label>
                        <div class="picker-field">
                            <div class="picker-value" id="tripDriverSummary">
                                <div><b>No driver selected</b></div>
                            </div>
                            <button type="button" class="btn secondary" id="selectTripDriverBtn">Select Driver</button>
                        </div>
                        <div class="quick-chips" id="recentTripDriverChips"></div>
                    </div>

                    <div style="margin-top:18px">
                        <label class="section-label">Status <span class="req">*</span></label>
                        <div id="tripStatusChoices" class="choice-grid auto-grid"></div>
                    </div>
                    <div class="grid" style="margin-top:18px">
                        <div>
                            <label class="section-label">Trip Around <span class="req">*</span></label>
                            <div id="tripAroundChoices" class="choice-grid auto-grid"></div>
                        </div>
                        <div>
                            <label class="section-label">Trip Period <span class="req">*</span></label>
                            <div id="tripPeriodChoices" class="choice-grid auto-grid"></div>
                        </div>
                    </div>
                    <div class="grid" style="margin-top:18px">
                        <x-fleetman.input id="tripPurpose" label="Purpose" placeholder="Example: Client visit / Staff movement" />
                        <div>
                            <label class="section-label">Quick Purpose</label>
                            <div id="tripPurposeChoices" class="choice-grid auto-grid"></div>
                        </div>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card
                    title="2. Route & Odometer"
                >
                    <div class="grid">
                        <x-fleetman.input id="tripFromLocation" label="From Location" placeholder="Example: Head Office" />
                        <x-fleetman.input id="tripToLocation" label="To Location" placeholder="Example: Gulshan Client Office" />
                    </div>
                    <div class="grid" style="margin-top:16px">
                        <x-fleetman.input id="tripOdoStart" label="Odo Start" type="number" placeholder="Starting reading" required />
                        <x-fleetman.input id="tripOdoEnd" label="Odo End" type="number" placeholder="Ending reading" />
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card
                    title="3. Trip Costs"
                >
                    <div class="grid">
                        <x-fleetman.input id="tripFuelCost" label="Fuel Cost" type="number" step="0.01" placeholder="0" />
                        <x-fleetman.input id="tripFoodCost" label="Food Cost" type="number" step="0.01" placeholder="0" />
                    </div>
                    <div class="grid" style="margin-top:16px">
                        <x-fleetman.input id="tripTolls" label="Tolls" type="number" step="0.01" placeholder="0" />
                        <x-fleetman.input id="tripOtherCost" label="Other Cost" type="number" step="0.01" placeholder="0" />
                    </div>
                    <div class="grid" style="margin-top:16px">
                        <x-fleetman.input id="tripAccommodationCost" label="Accommodation Cost" type="number" step="0.01" placeholder="0" />
                        <x-fleetman.input id="tripTotalCost" label="Total Estimated Cost" readonly />
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card
                    title="4. Notes"
                >
                    <x-fleetman.textarea id="tripDetails" label="Details" placeholder="Write trip details, purpose, or special instructions." required />
                </x-fleetman.section-card>
            </div>

        </div>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetTripBtn">Reset Form</button>
            <button type="button" class="btn secondary" id="saveTripDraftBtn">Save as Draft</button>
            <button type="button" class="btn primary" id="saveTripBtn">Save Trip</button>
        </div>
    </div>

    <div id="tripListPage">
        <x-fleetman.topbar :items="[['label' => 'Trip List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportTripsBtn">⬇ Export CSV</button>
                <button type="button" class="btn primary" id="newTripBtn">＋ Add Trip</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Trip List"
            subtitle="Saved trips with quick search, filters, export, and edit/delete actions."
        />

        <div class="kpi">
            <x-fleetman.kpi-card id="tripKpiTotal" label="Total Trips" />
            <x-fleetman.kpi-card id="tripKpiRunning" label="Running Trips" />
            <x-fleetman.kpi-card id="tripKpiCompleted" label="Completed Trips" />
            <x-fleetman.kpi-card id="tripKpiCost" label="Total Trip Cost" />
        </div>

        <div class="card">
            <div class="filters">
                <input id="tripSearch" placeholder="Search by trip ID, vehicle, driver, route, purpose, or status">
                <input id="tripVehicleSearch" placeholder="Filter by vehicle">
                <x-fleetman.select id="tripFilterStatus" label="" :options="$fleetman['options']['trip_statuses']" placeholder="All Status" />
                <x-fleetman.select id="tripFilterAround" label="" :options="$fleetman['options']['trip_around']" placeholder="All Trip Areas" />
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyTripFiltersBtn">Apply</button><button type="button" class="btn light" id="clearTripFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap trip-table">
                <table>
                    <thead><tr><th>Trip</th><th>Dates</th><th>Vehicle & Driver</th><th>Route</th><th>Odometer</th><th>Costs</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="tripTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="tripSelectorOverlay" class="overlay" aria-hidden="true">
    <div class="sheet" role="dialog" aria-modal="true" aria-labelledby="tripSelectorTitle">
        <div class="sheet-handle"></div>
        <div class="sheet-head">
            <div>
                <h3 id="tripSelectorTitle">Select</h3>
                <p id="tripSelectorSubtitle">Search and choose</p>
            </div>
            <button type="button" class="btn light" id="closeTripSelectorBtn">Close</button>
        </div>
        <div class="sheet-body">
            <div class="sheet-search">
                <input id="tripSelectorSearch" placeholder="Search">
                <select id="tripSelectorFilter"></select>
            </div>
            <div class="sheet-stats" id="tripSelectorStats"></div>
            <div class="selector-tabs">
                <button type="button" class="selector-tab active" id="tripSelectorRecentTab">Recent</button>
                <button type="button" class="selector-tab" id="tripSelectorAllTab">All</button>
            </div>
            <div class="list-items" id="tripSelectorList"></div>
        </div>
        <div class="sheet-foot">
            <button type="button" class="btn light" id="clearTripSelectorChoiceBtn">Clear Selection</button>
            <button type="button" class="btn primary" id="doneTripSelectorBtn">Done</button>
        </div>
    </div>
</div>
@endsection
