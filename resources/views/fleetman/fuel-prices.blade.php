@extends('layouts.fleetman')

@section('title', 'Fuel Prices | FleetMan')
@section('mobile-title', 'Fuel Prices')

@section('content')
<div class="page-section">
    <div id="fuelPriceAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Price']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-page-target="fuelPriceListPage">← Price List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Price"
            subtitle="A clearer setup screen for office users. Keep the fuel type, display name, price, and effective date easy to understand and easy to save."
        >

        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>1. Fuel Price Information</h2>
                        </div>
                    </div>
                    <div class="grid3">
                        <div class="field"><label for="fuelPriceId">Fuel Price ID <span class="req">*</span></label><input id="fuelPriceId" readonly required aria-required="true"></div>
                        <x-fleetman.select id="fuelType" label="Fuel Type" :options="$fleetman['options']['fuel_types']" placeholder="Select fuel type" required />
                        <div class="field"><label for="fuelName">Name <span class="req">*</span></label><input id="fuelName" maxlength="160" required aria-required="true" placeholder="Example: Diesel - Standard Rate"></div>
                        <x-fleetman.select id="fuelStatus" label="Status" :options="$fleetman['options']['fuel_statuses']" required />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <div class="field"><label for="fuelPrice">Price per Unit <span class="req">*</span></label><input id="fuelPrice" type="number" min="0.01" step="0.01" required aria-required="true" placeholder="Example: 122"></div>
                        <x-fleetman.select id="fuelUnit" label="Unit" :options="$fleetman['options']['fuel_units']" placeholder="Select unit type" required />
                        <div class="field"><label for="effectiveDate">Effective Date <span class="req">*</span></label><input id="effectiveDate" type="date" required aria-required="true"></div>
                        <div class="field"><label for="fuelReference">Reference <span class="req">*</span></label><input id="fuelReference" maxlength="160" required aria-required="true" placeholder="Circular / memo / market note"></div>
                    </div>
                    <div class="field" style="margin-top:16px">
                        <label for="fuelRemarks">Remarks <span class="req">*</span></label>
                        <textarea id="fuelRemarks" maxlength="1000" required aria-required="true" placeholder="Note about source, approval, or special rule."></textarea>
                    </div>
                </div>
            </div>

        </div>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetFuelPriceBtn">Reset Form</button>
            <button type="button" class="btn secondary" id="saveFuelPriceDraftBtn">Save as Draft</button>
            <button type="button" class="btn primary" id="saveFuelPriceBtn">Save Fuel Price</button>
        </div>
    </div>

    <div id="fuelPriceListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Price List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportFuelPricesBtn">⬇ Export CSV</button>
                @if(data_get($fleetman, 'auth.pageAccess.canManage'))
                    <a href="{{ route('fleet.fuel-prices', ['action' => 'add']) }}" class="btn primary" id="addPriceFromListBtn">＋ Add Price</a>
                @else
                    <span class="btn primary rbac-control-muted" id="addPriceFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Price</span>
                @endif
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Price List"
            subtitle="A simple list page with sample data, quick search, status filters, and edit/delete actions. Suitable for non-technical back-office users."
        />

        <div class="kpi">
            <div class="card"><strong id="fuelPriceKpiTotal">0</strong><span>Total Fuel Prices</span></div>
            <div class="card"><strong id="fuelPriceKpiActive">0</strong><span>Active Rates</span></div>
            <div class="card"><strong id="fuelPriceKpiTypes">0</strong><span>Fuel Types Used</span></div>
            <div class="card"><strong id="fuelPriceKpiLatest">-</strong><span>Latest Effective Date</span></div>
        </div>

        <div class="card">
            <div class="filters">
                <input id="fuelPriceSearch" placeholder="Search by fuel type, name, reference, or ID">
                <x-fleetman.select id="fuelPriceFilterFuel" label="" :options="$fleetman['options']['fuel_types']" placeholder="All Fuel Types" />
                <x-fleetman.select id="fuelPriceFilterStatus" label="" :options="$fleetman['options']['fuel_statuses']" placeholder="All Status" />
                <x-fleetman.select id="fuelPriceFilterUnit" label="" :options="$fleetman['options']['fuel_units']" placeholder="All Units" />
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyFuelPriceFiltersBtn">Apply</button><button type="button" class="btn light" id="clearFuelPriceFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Fuel Price</th><th>Fuel Type</th><th>Price</th><th>Unit</th><th>Effective Date</th><th>Reference</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="fuelPriceTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
