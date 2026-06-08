@extends('layouts.fleetman')

@section('title', 'Fuel Type Master | FleetMan')
@section('mobile-title', 'Fuel Type Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Fuel Type Master']]">

    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Fuel Type Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage fuel types for dropdowns across the application.' }}"
    />

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">⛽</div>
            <div><strong id="masterFuelTypeCount">0</strong><span>Fuel types available</span></div>
        </div>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.fuel-units') }}">
            <div class="master-overview-icon">📏</div>
            <div><strong id="masterFuelUnitCount">0</strong><span>Fuel units available</span></div>
        </a>
    </div>

    <section class="card master-card" id="fuelTypeMasterCard">
        <div class="section-head">
            <div>
                <h2>Fuel Type Master</h2>
            </div>
            <button type="button" class="btn light" id="resetFuelTypeMasterBtn">Reset</button>
        </div>

        <form id="fuelTypeMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="fuelTypeEditingCode">
            <x-fleetman.input id="fuelTypeMasterName" label="Fuel Type Name" placeholder="Example: Diesel" required />
            <x-fleetman.input id="fuelTypeMasterCode" label="Code" placeholder="Example: DIESEL"  />
            <x-fleetman.input id="fuelTypeMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="fuelTypeMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="fuelTypeMasterDescription" label="Description / Note" placeholder="Optional internal note about where this fuel type should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveFuelTypeMasterBtn">Save Fuel Type</button>
                <button type="button" class="btn light" id="cancelFuelTypeEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Fuel Types</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fuel Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="fuelTypeMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
