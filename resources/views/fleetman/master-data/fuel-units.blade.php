@extends('layouts.fleetman')

@section('title', 'Fuel Unit Master | FleetMan')
@section('mobile-title', 'Fuel Unit Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Fuel Unit Master']]">
        <x-slot:actions>
            <a href="{{ route('fleet.master-data.fuel-types') }}" class="btn secondary">Fuel Type Master</a>
            <span class="badge soft">Database backed dropdown values</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Fuel Unit Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage fuel units for dropdowns across the application.' }}"
    />

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.fuel-types') }}">
            <div class="master-overview-icon">⛽</div>
            <div><strong id="masterFuelTypeCount">0</strong><span>Fuel types available for dropdowns</span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">📏</div>
            <div><strong id="masterFuelUnitCount">0</strong><span>Fuel units available for dropdowns</span></div>
        </div>
    </div>

    <section class="card master-card" id="fuelUnitMasterCard">
        <div class="section-head">
            <div>
                <h2>Fuel Unit Master</h2>
                <p>Add fuel units once and use them in fuel related dropdowns across the app.</p>
            </div>
            <button type="button" class="btn light" id="resetFuelUnitMasterBtn">Reset</button>
        </div>

        <form id="fuelUnitMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="fuelUnitEditingCode">
            <x-fleetman.input id="fuelUnitMasterName" label="Fuel Unit Name" placeholder="Example: Per Liter" required />
            <x-fleetman.input id="fuelUnitMasterCode" label="Code" placeholder="Example: LITER" hint="Code is auto-generated but can be edited before save." />
            <x-fleetman.input id="fuelUnitMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="fuelUnitMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="fuelUnitMasterDescription" label="Description / Note" placeholder="Optional internal note about where this fuel unit should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveFuelUnitMasterBtn">Save Fuel Unit</button>
                <button type="button" class="btn light" id="cancelFuelUnitEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Fuel Units</b><small>These rows are stored in the fleet_fuel_units table.</small></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fuel Unit</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="fuelUnitMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
