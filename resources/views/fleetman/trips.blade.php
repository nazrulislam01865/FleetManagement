@extends('layouts.fleetman')

@section('title', 'Trips | FleetMan')
@section('mobile-title', 'Trips')

@section('content')
<div class="page-section trip-page">
    <div id="tripAddPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Add Trip']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-page-target="tripListPage">← Trip List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Trip"
            subtitle="Create a trip using saved vehicles and drivers, record the total cost, and collect payment using one or more methods."
        />

        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Basic Trip Information">
                    <div class="grid">
                        <x-fleetman.input id="tripId" label="Trip ID" required readonly />
                        <x-fleetman.input id="tripStartDate" label="Start Date" type="date" required />
                    </div>

                    <div class="grid" style="margin-top:16px">
                        <x-fleetman.input id="tripVehicle" label="Vehicle" placeholder="Enter vehicle" autocomplete="off" required />

                        <x-fleetman.input id="tripDriver" label="Driver" placeholder="Enter driver" autocomplete="off" required />
                    </div>

                    <div class="grid" style="margin-top:18px">
                        <x-fleetman.input id="tripPurpose" label="Purpose" placeholder="Example: Client visit / Staff movement" />
                        <div>
                            <label class="section-label">Quick Purpose</label>
                            <div id="tripPurposeChoices" class="choice-grid auto-grid"></div>
                        </div>
                    </div>

                    <div id="tripClientVisitField" class="field searchable hidden" style="margin-top:16px">
                        <div class="search-label">
                            <label for="tripClient">Client <span class="req">*</span></label>
                            <span class="search-tag">Searchable</span>
                        </div>
                        <input id="tripClient" list="tripClientList" placeholder="Type client ID, name, phone, or email" autocomplete="off">
                        <datalist id="tripClientList"></datalist>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="2. Route & Odometer">
                    <div class="grid">
                        <x-fleetman.input id="tripFromLocation" label="From Location" placeholder="Example: Head Office" />
                        <x-fleetman.input id="tripToLocation" label="To Location" placeholder="Example: Gulshan Client Office" />
                    </div>
                    <div class="grid" style="margin-top:16px">
                        <x-fleetman.input id="tripOdoStart" label="Odo Start (Optional)" type="number" min="0" step="1" placeholder="Starting reading" />
                        <x-fleetman.input id="tripOdoEnd" label="Odo End (Optional)" type="number" min="0" step="1" placeholder="Ending reading" />
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="3. Trip Cost & Payments">
                    <div class="grid3 trip-payment-summary">
                        <x-fleetman.input id="tripTotalCost" label="Total Cost (Taka)" type="number" min="0.01" step="0.01" placeholder="0.00" required />
                        <x-fleetman.input id="tripPaidAmount" label="Paid Amount" type="number" value="0" readonly />
                        <x-fleetman.input id="tripBalanceDue" label="Remaining Payment Required" type="number" value="0" readonly />
                    </div>

                    <div class="trip-payment-head">
                        <div>
                            <h3>Payment Status</h3>
                        </div>
                        <button type="button" class="btn secondary" id="addTripPaymentBtn">+ Add Payment</button>
                    </div>
                    <div id="tripPayments" class="trip-payment-list"></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="4. Notes">
                    <x-fleetman.textarea id="tripDetails" label="Details" placeholder="Write trip details, purpose, or special instructions." required />
                </x-fleetman.section-card>
            </div>
        </div>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetTripBtn">Reset Form</button>
            <button type="button" class="btn primary" id="saveTripBtn">Save Trip</button>
        </div>
    </div>

    <div id="tripListPage">
        <x-fleetman.topbar :items="[['label' => 'Trip List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportTripsBtn">⬇ Export CSV</button>
                @if(data_get($fleetman, 'auth.pageAccess.canManage'))
                    <a href="{{ route('fleet.trips', ['action' => 'add']) }}" class="btn primary" id="addTripFromListBtn">＋ Add Trip</a>
                @else
                    <span class="btn primary rbac-control-muted" id="addTripFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Trip</span>
                @endif
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Trip List"
            subtitle="Saved trips with vehicle, driver, route, total cost, paid amount, and remaining payment."
        />

        <div class="kpi">
            <x-fleetman.kpi-card id="tripKpiTotal" label="Total Trips" />
            <x-fleetman.kpi-card id="tripKpiCost" label="Total Trip Cost" />
            <x-fleetman.kpi-card id="tripKpiPaid" label="Total Paid" />
            <x-fleetman.kpi-card id="tripKpiBalance" label="Total Balance" />
        </div>

        <div class="card">
            <div class="filters trip-list-filters">
                <input id="tripSearch" placeholder="Search by trip ID, vehicle, driver, client, route, or purpose">
                <input id="tripVehicleSearch" placeholder="Filter by vehicle">
                <div class="trip-filter-actions"><button type="button" class="btn secondary" id="applyTripFiltersBtn">Apply</button><button type="button" class="btn light" id="clearTripFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap trip-table">
                <table>
                    <thead><tr><th>Created At</th><th>Trip</th><th>Date</th><th>Vehicle & Driver</th><th>Route</th><th>Odometer</th><th>Payment</th><th>Actions</th></tr></thead>
                    <tbody id="tripTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
