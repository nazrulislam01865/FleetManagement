@extends('layouts.fleetman')

@section('title', 'Vendors & Parties | FleetMan')
@section('mobile-title', 'Vendors')

@section('content')
<div class="page-section vendor-party-page">
    <div id="vendorAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Vendor']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-page-target="vendorListPage">← Vendor List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Vendor"
            subtitle="Create vendors, suppliers, workshops, fuel stations, transport providers, and other external parties from one usable form."
        >

        </x-fleetman.title-card>

        <div class="layout vendor-party-form-layout">
            <div>
                <x-fleetman.section-card
                    title="1. Vendor / Party Information"
                >
                    <div class="grid3">
                        <x-fleetman.input id="partyId" label="Vendor / Party ID" required readonly />
                        <x-fleetman.input id="partyName" label="Party Name" placeholder="Example: Speed Transport Services" required />
                        <x-fleetman.select id="partyType" label="Party Type" :options="$fleetman['options']['party_types']" placeholder="Select type" required />
                        @php
                            $defaultVendorContractorType = collect($fleetman['options']['vendor_contractor_types'] ?? [])->first() ?? '';
                        @endphp
                        <input
                            type="hidden"
                            id="vendorContractorType"
                            name="vendorContractorType"
                            value="{{ $defaultVendorContractorType }}"
                            data-default-value="{{ $defaultVendorContractorType }}"
                        >
                        <x-fleetman.select id="partyStatus" label="Status" :options="$fleetman['options']['party_statuses']" required />
                    </div>
                    <div id="partyFuelTypesField" class="fuel-station-config hidden" style="margin-top:16px">
                        <div class="field">
                            <label>Fuel Types Sold <span class="req">*</span></label>
                            @php
                                $vendorFuelTypes = collect($fleetman['options']['fuel_types'] ?? [])
                                    ->filter(fn ($fuelType) => filled($fuelType))
                                    ->unique()
                                    ->values();
                            @endphp
                            <div class="fuel-type-check-grid" id="partyFuelTypes">
                                @foreach ($vendorFuelTypes as $fuelType)
                                    <label class="fuel-type-check">
                                        <input type="checkbox" name="partyFuelTypes" value="{{ $fuelType }}">
                                        <span>{{ $fuelType }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.input id="partyPhone" label="Phone Number" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" placeholder="01XXXXXXXXX" required />
                        <x-fleetman.input id="partyEmail" label="Email" type="email" placeholder="vendor@example.com" />
                        <x-fleetman.input id="partyWhatsapp" label="WhatsApp Number" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" placeholder="01XXXXXXXXX" />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.input id="tradeLicense" label="Trade License No." inputmode="numeric" pattern="[0-9]+" placeholder="Optional" />
                        <x-fleetman.input id="tinBin" label="TIN / BIN" placeholder="Optional" />
                        <x-fleetman.select id="paymentTerms" label="Payment Terms" :options="$fleetman['options']['payment_terms']" />
                    </div>
                    <div style="margin-top:16px">
                        <x-fleetman.textarea id="partyAddress" label="Address" placeholder="Office / shop address" required />
                    </div>
                    <div style="margin-top:16px">
                        <x-fleetman.textarea id="partyAbout" label="About / Notes" placeholder="Short note about service area, service quality, billing note, or internal remark." />
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card
                    title="2. Contact Person(s)"
                >
                    <x-slot:action>
                        <button type="button" class="btn light" id="addPartyContactBtn">＋ Add Contact Person</button>
                    </x-slot:action>
                    <div id="partyContacts"></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card
                    title="3. Documents"
                    class="document-section-card"
                >
                    <x-slot:action>
                        <button type="button" class="btn secondary" id="addPartyDocumentBtn">+ Add document</button>
                    </x-slot:action>
                    <div id="partyDocuments"></div>
                </x-fleetman.section-card>
            </div>

        </div>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetPartyBtn">Reset Form</button>
            <button type="button" class="btn secondary" id="savePartyDraftBtn">Save as Draft</button>
            <button type="button" class="btn primary" id="savePartyBtn">Save Vendor / Party</button>
        </div>
    </div>

    <div id="vendorListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Vendor List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportPartiesBtn">⬇ Export CSV</button>
                @if(data_get($fleetman, 'auth.pageAccess.canManage'))
                    <a href="{{ route('fleet.vendors', ['action' => 'add']) }}" class="btn primary" id="addVendorFromListBtn">＋ Add Vendor</a>
                @else
                    <span class="btn primary rbac-control-muted" id="addVendorFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Vendor</span>
                @endif
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Vendor List"
            subtitle="A simple list showing combined vendor and party information. Quick search and easy filtering."
        />

        <div class="kpi">
            <x-fleetman.kpi-card id="partyKpiTotal" label="Total Parties" />
            <x-fleetman.kpi-card id="partyKpiActive" label="Active Parties" />
            <x-fleetman.kpi-card id="partyKpiTypes" label="Party Types" />
            <x-fleetman.kpi-card id="partyKpiContacts" label="Contact Persons" />
        </div>

        <div class="card">
            <div class="filters">
                <input id="partySearch" placeholder="Search by party name, phone, email, contact person, or ID">
                <x-fleetman.select id="partyFilterType" label="" :options="$fleetman['options']['party_types']" placeholder="All Types" />
                <x-fleetman.select id="partyFilterStatus" label="" :options="$fleetman['options']['party_statuses']" placeholder="All Status" />
                <x-fleetman.select id="partyFilterTerms" label="" :options="$fleetman['options']['payment_terms']" placeholder="All Payment Terms" />
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyPartyFiltersBtn">Apply</button><button type="button" class="btn light" id="clearPartyFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap wide-table">
                <table>
                    <thead><tr><th>Created At</th><th>Vendor / Party</th><th>Type</th><th>Phone</th><th>Contact Person</th><th>Payment Terms</th><th>Documents</th><th>Status</th><th>Address</th><th>Actions</th></tr></thead>
                    <tbody id="partyTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
