@extends('layouts.fleetman')

@section('title', 'Vehicles | FleetMan')
@section('mobile-title', 'Vehicles')

@section('content')
<div class="page-section">
    <div id="vehicleAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Vehicle']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-page-target="vehicleListPage">← Vehicle List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Vehicle"
            subtitle="A simple guided form for non-technical users. Fill basic information first, then fuel, documents and driver assignment."
        >

        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>1. Basic Vehicle Information</h2>
                        </div>
                    </div>
                    <div class="grid3">
                        <div class="field"><label for="vehicleId">Vehicle ID <span class="req">*</span></label><input id="vehicleId" readonly></div>
                        <div class="field"><label for="vehicleName">Vehicle Name <span class="req">*</span></label><input id="vehicleName" placeholder="Example: Dhaka Pickup 01" required></div>
                        <div class="field"><label for="regNo">Registration Number <span class="req">*</span></label><input id="regNo" placeholder="Example: DHAKA METRO-GA 12-3456" title="The characters @ # $ % ^ & * ( ) ! ` ~ are not allowed." required></div>
                        <x-fleetman.select id="vendor" label="Vendor / Owner" :options="$fleetman['options']['vehicle_vendors']" placeholder="Select vendor/owner" />
                        <div class="field"><label for="model">Model <span class="req">*</span></label><input id="model" placeholder="Example: Toyota Hiace 2021" required></div>
                        <div class="field"><label for="color">Color</label><input id="color" placeholder="Example: White"></div>
                        <div class="field"><label for="engineNo">Engine Number <span class="req">*</span></label><input id="engineNo" maxlength="22" placeholder="Enter engine number (maximum 22 characters)" required></div>
                        <div class="field"><label for="mileage">Regular Mileage Target</label><input id="mileage" type="number" placeholder="Example: 8.5"></div>
                        <div class="field"><label for="odo">Current Odometer</label><input id="odo" type="number" placeholder="Example: 45230"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>2. Vehicle Type & Usage</h2>
                        </div>
                    </div>
                    <div class="grid">
                        <x-fleetman.select id="category" label="Vehicle Category" :options="$fleetman['options']['vehicle_categories']" placeholder="Select category" required />
                        <x-fleetman.select id="subCategory" label="Vehicle Sub-category" :options="[]" placeholder="Select sub-category" />
                    </div>
                    <div class="field" style="margin-top:14px">
                        <label>Usage Type <span class="req">*</span></label>
                        <div class="choice-grid">
                            @foreach ($fleetman['options']['usage_types'] as $usage)
                                <label class="choice">
                                    <input type="radio" name="usage" value="{{ $usage['value'] }}">
                                    <span>{{ $usage['title'] }}</span>
                                    <small>{{ $usage['description'] }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="grid" style="margin-top:16px">
                        <x-fleetman.select id="rentalType" label="Rental Type" :options="['With Driver', 'Without Driver']" placeholder="Select rental type" required />
                        <div class="field searchable">
                            <div class="search-label">
                                <label for="driver">Driver <small>(Optional)</small></label>
                                <span class="search-tag">Searchable</span>
                            </div>
                            <input id="driver" list="vehicleDriverList" placeholder="Type to search and select a driver (optional)" autocomplete="off">
                            <datalist id="vehicleDriverList">
                                @foreach ($fleetman['options']['drivers'] as $driverOptionValue => $driverOptionLabel)
                                    @php
                                        $driverValue = is_int($driverOptionValue) ? $driverOptionLabel : $driverOptionValue;
                                        $driverLabel = is_array($driverOptionLabel) ? ($driverOptionLabel['label'] ?? $driverValue) : $driverOptionLabel;
                                    @endphp
                                    <option value="{{ $driverValue }}">{{ $driverLabel }}</option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div id="driverPaymentFields" class="grid" style="margin-top:16px">
                        <x-fleetman.input id="driverPaymentAmount" label="Driver Payment Amount" type="number" min="0" step="0.01" placeholder="0.00" required />
                        <x-fleetman.select id="driverPaymentCycle" label="Driver Payment Cycle" :options="$fleetman['options']['rental_payment_cycles']" placeholder="Select payment cycle" required />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.input id="vehicleRentalAmount" label="Vehicle Rental Amount" type="number" min="0" step="0.01" placeholder="0.00" required />
                        <x-fleetman.select id="vehiclePaymentCycle" label="Vehicle Payment Cycle" :options="$fleetman['options']['rental_payment_cycles']" placeholder="Select payment cycle" required />
                        <x-fleetman.input id="totalRentalAmount" label="Total Rental Amount" type="number" min="0" step="0.01" value="0.00" readonly />
                    </div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>3. Fuel Setup</h2>
                        </div>
                        <button type="button" class="btn secondary" id="addFuelRowBtn">+ Add fuel</button>
                    </div>
                    <div id="vehicleFuelRows"></div>
                </div>

                <div class="card document-section-card">
                    <div class="section-head">
                        <div>
                            <h2>4. Documents</h2>
                        </div>
                        <button type="button" class="btn secondary" id="addDocRowBtn">+ Add document</button>
                    </div>
                    <div id="vehicleDocRows"></div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>5. Photo & Notes</h2>
                        </div>
                    </div>
                    <div class="grid">
                        <div class="field"><label for="image">Vehicle Image</label><input id="image" type="file" accept="image/jpeg,image/png,image/webp"><input id="vehicleImageData" type="hidden"><div class="temp-upload-progress hidden" id="vehicleImageProgress"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div><div class="upload-meta" id="vehicleImageUploadInfo"></div><div class="hint">Allowed: JPG, PNG or WEBP. Maximum size: 100 KB.</div></div>
                        <div class="field"><label for="notes">Notes</label><textarea id="notes" placeholder="Any special note about vehicle condition or assignment"></textarea></div>
                    </div>
                </div>

                <div class="save-bar">
                    <button type="button" class="btn light" id="clearVehicleBtn">Clear</button>
                    <button type="button" class="btn primary" id="saveVehicleBtn">Save Vehicle & Go to List</button>
                </div>
            </div>

        </div>
    </div>

    <div id="vehicleListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Vehicle List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportVehiclesBtn">⬇ Export CSV</button>
                @if(data_get($fleetman, 'auth.pageAccess.canManage'))
                    <a href="{{ route('fleet.vehicles', ['action' => 'add']) }}" class="btn primary" id="addVehicleFromListBtn">＋ Add Vehicle</a>
                @else
                    <span class="btn primary rbac-control-muted" id="addVehicleFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Vehicle</span>
                @endif
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Vehicle List"
            subtitle="All created vehicles will appear here. Search, filter, view documents and check fuel setup quickly."
        />

        <div class="kpi">
            <div class="card"><strong id="vehicleKpiTotal">0</strong><span>Total vehicles</span></div>
            <div class="card"><strong id="vehicleKpiActive">0</strong><span>Active vehicles</span></div>
            <div class="card"><strong id="vehicleKpiDocs">0</strong><span>Expiring documents</span></div>
            <div class="card"><strong id="vehicleKpiFuel">0</strong><span>Multi-fuel vehicles</span></div>
        </div>

        <div class="card">
            <div class="filters">
                <input id="vehicleSearch" placeholder="Search by vehicle, registration, driver">
                <x-fleetman.select id="vehicleFilterCategory" label="" :options="$fleetman['options']['vehicle_categories']" placeholder="All categories" />
                <x-fleetman.select id="vehicleFilterFuel" label="" :options="$fleetman['options']['fuel_types']" placeholder="All fuel" />
                <select id="vehicleFilterStatus"><option value="">All status</option><option>Active</option><option>Needs document review</option></select>
                <button type="button" class="btn light" id="clearVehicleFiltersBtn">Clear</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Created At</th><th>Vehicle</th><th>Registration</th><th>Category</th><th>Fuel Setup</th><th>Driver</th><th>Documents</th><th>Rent</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="vehicleTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
