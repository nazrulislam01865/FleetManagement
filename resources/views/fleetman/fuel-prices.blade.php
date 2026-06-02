@extends('layouts.fleetman')

@section('title', 'Fuel Prices | FleetMan')
@section('mobile-title', 'Fuel Prices')

@section('content')
<div class="page-section">
    <div id="fuelPriceAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Fuel Price']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-page-target="fuelPriceListPage">← Fuel Price List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Fuel Price"
            subtitle="A clearer setup screen for office users. Keep the fuel type, display name, price, and effective date easy to understand and easy to save."
        >
            <x-slot:action>
                <button type="button" class="btn secondary" id="loadFuelPriceSampleBtn">Use sample data</button>
            </x-slot:action>
        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>1. Fuel Price Information</h2>
                            <p>Use one row per fuel price setup. If price changes later, create a new setup or edit the existing one based on business rules.</p>
                        </div>
                    </div>
                    <div class="grid4">
                        <div class="field"><label for="fuelPriceId">Fuel Price ID <span class="req">*</span></label><input id="fuelPriceId" readonly></div>
                        <x-fleetman.select id="fuelType" label="Fuel Type" :options="$fleetman['options']['fuel_price_types']" placeholder="Select fuel type" required />
                        <div class="field"><label for="fuelName">Name <span class="req">*</span></label><input id="fuelName" placeholder="Example: Diesel - Standard Rate"></div>
                        <x-fleetman.select id="fuelStatus" label="Status" :options="$fleetman['options']['fuel_statuses']" required />
                    </div>
                    <div class="grid4" style="margin-top:16px">
                        <div class="field"><label for="fuelPrice">Price per Unit <span class="req">*</span></label><input id="fuelPrice" type="number" step="0.01" placeholder="Example: 122"></div>
                        <x-fleetman.select id="fuelUnit" label="Unit" :options="$fleetman['options']['fuel_units']" />
                        <div class="field"><label for="effectiveDate">Effective Date <span class="req">*</span></label><input id="effectiveDate" type="date"></div>
                        <div class="field"><label for="fuelReference">Reference</label><input id="fuelReference" placeholder="Circular / memo / market note"></div>
                    </div>
                    <div class="field" style="margin-top:16px">
                        <label for="fuelRemarks">Remarks</label>
                        <textarea id="fuelRemarks" placeholder="Optional note about source, approval, or special rule."></textarea>
                        <div class="hint">Example: “Updated based on management approval effective from 1 June 2026.”</div>
                    </div>
                </div>
            </div>
            <div>
                <div class="side-note">
                    <h3>Recommended form flow</h3>
                    <ul>
                        <li>Choose the fuel type first.</li>
                        <li>Give the price a simple business-friendly name.</li>
                        <li>Use <b>Active</b> only for the current rate.</li>
                        <li>After save, the page redirects to the fuel price list.</li>
                    </ul>
                </div>
                <div class="required-box">
                    <b>Required before save:</b><br>
                    Fuel type, name, price, status, and effective date.
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
        <x-fleetman.topbar :items="[['label' => 'Fuel Price List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportFuelPricesBtn">⬇ Export CSV</button>
                <button type="button" class="btn primary" id="newFuelPriceBtn">＋ Add Fuel Price</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Fuel Price List"
            subtitle="A simple list page with sample data, quick search, status filters, and edit/delete actions. Suitable for non-technical back-office users."
        >
            <x-slot:action>
                <div class="pillbar"><div class="pill active">All Rates</div><div class="pill">Active</div><div class="pill">Recent Updates</div></div>
            </x-slot:action>
        </x-fleetman.title-card>

        <div class="kpi">
            <div class="card"><strong id="fuelPriceKpiTotal">0</strong><span>Total Fuel Prices</span></div>
            <div class="card"><strong id="fuelPriceKpiActive">0</strong><span>Active Rates</span></div>
            <div class="card"><strong id="fuelPriceKpiTypes">0</strong><span>Fuel Types Used</span></div>
            <div class="card"><strong id="fuelPriceKpiLatest">-</strong><span>Latest Effective Date</span></div>
        </div>

        <div class="card">
            <div class="filters">
                <input id="fuelPriceSearch" placeholder="Search by fuel type, name, reference, or ID">
                <x-fleetman.select id="fuelPriceFilterFuel" label="" :options="$fleetman['options']['fuel_price_types']" placeholder="All Fuel Types" />
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
