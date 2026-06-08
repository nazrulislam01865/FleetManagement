@extends('layouts.fleetman')

@section('title', 'Vehicle Sub Category Master | FleetMan')
@section('mobile-title', 'Vehicle Sub Category Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Vehicle Sub Category Master']]">
        <x-slot:actions>
            <a href="{{ route('fleet.master-data.vehicle-categories') }}" class="btn secondary">Vehicle Category Master</a>
            <span class="badge soft">Database backed dropdown values</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Vehicle Sub Category Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage vehicle sub categories and map each one to a vehicle category.' }}"
    />

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.vehicle-categories') }}">
            <div class="master-overview-icon">🚗</div>
            <div><strong id="masterVehicleCategoryCount">0</strong><span>Vehicle categories available for Vehicle dropdowns</span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">↳</div>
            <div><strong id="masterVehicleSubCategoryCount">0</strong><span>Vehicle sub categories mapped under categories</span></div>
        </div>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.vehicles') }}">
            <div class="master-overview-icon">📋</div>
            <div><strong>Use</strong><span>These values appear after selecting a vehicle category</span></div>
        </a>
    </div>

    <section class="card master-card" id="vehicleSubCategoryMasterCard">
        <div class="section-head">
            <div>
                <h2>Vehicle Sub Category Master</h2>
                <p>Add vehicle sub categories and map them under the correct vehicle category.</p>
            </div>
            <button type="button" class="btn light" id="resetVehicleSubCategoryMasterBtn">Reset</button>
        </div>

        <form id="vehicleSubCategoryMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="vehicleSubCategoryEditingCode">
            <x-fleetman.select id="vehicleSubCategoryParent" label="Vehicle Category" :options="[]" placeholder="Select vehicle category" required />
            <x-fleetman.input id="vehicleSubCategoryMasterName" label="Vehicle Sub Category Name" placeholder="Example: Pickup truck" required />
            <x-fleetman.input id="vehicleSubCategoryMasterCode" label="Code" placeholder="Example: LIGHT_DUTY_VEHICLE_PICKUP_TRUCK" hint="Code is auto-generated but can be edited before save." />
            <x-fleetman.input id="vehicleSubCategoryMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="vehicleSubCategoryMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="vehicleSubCategoryMasterDescription" label="Description / Note" placeholder="Optional internal note about where this sub category should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveVehicleSubCategoryMasterBtn">Save Vehicle Sub Category</button>
                <button type="button" class="btn light" id="cancelVehicleSubCategoryEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Vehicle Sub Categories</b><small>These rows are stored in the fleet_vehicle_sub_categories table.</small></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle Sub Category</th>
                        <th>Vehicle Category</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vehicleSubCategoryMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
