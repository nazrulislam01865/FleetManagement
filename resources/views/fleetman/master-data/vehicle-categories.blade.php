@extends('layouts.fleetman')

@section('title', 'Vehicle Category Master | FleetMan')
@section('mobile-title', 'Vehicle Category Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Vehicle Category Master']]">

    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Vehicle Category Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage vehicle categories for dropdowns across the application.' }}"
    />

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">🚗</div>
            <div><strong id="masterVehicleCategoryCount">0</strong><span>Vehicle categories available</span></div>
        </div>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.vehicle-sub-categories') }}">
            <div class="master-overview-icon">↳</div>
            <div><strong id="masterVehicleSubCategoryCount">0</strong><span>Vehicle sub categories</span></div>
        </a>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.vehicles') }}">
            <div class="master-overview-icon">📋</div>
        </a>
    </div>

    <section class="card master-card" id="vehicleCategoryMasterCard">
        <div class="section-head">
            <div>
                <h2>Vehicle Category Master</h2>
            </div>
            <button type="button" class="btn light" id="resetVehicleCategoryMasterBtn">Reset</button>
        </div>

        <form id="vehicleCategoryMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="vehicleCategoryEditingCode">
            <x-fleetman.input id="vehicleCategoryMasterName" label="Vehicle Category Name" placeholder="Example: Light-Duty Vehicle" required />
            <x-fleetman.input id="vehicleCategoryMasterCode" label="Code" placeholder="Example: LIGHT_DUTY_VEHICLE" />
            <x-fleetman.input id="vehicleCategoryMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="vehicleCategoryMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="vehicleCategoryMasterDescription" label="Description / Note" placeholder="Optional internal note about where this category should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveVehicleCategoryMasterBtn">Save Vehicle Category</button>
                <button type="button" class="btn light" id="cancelVehicleCategoryEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Vehicle Categories</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Created At</th><th>Vehicle Category</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="vehicleCategoryMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
