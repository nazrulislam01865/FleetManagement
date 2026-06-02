@extends('layouts.fleetman')

@section('title', 'Vendors & Parties | FleetMan')
@section('mobile-title', 'Vendors')

@section('content')
<div class="page-section">
    <div id="vendorAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Vendor / Party']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-page-target="vendorListPage">← Vendor / Party List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Vendor / Party"
            subtitle="Standard wording: use Vendor / Party when this master stores all external parties. Use Vendor only when it stores suppliers or service providers only."
        >
            <x-slot:action>
                <button type="button" class="btn secondary" id="loadPartySampleBtn">Use sample data</button>
            </x-slot:action>
        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <x-fleetman.section-card
                    title="1. Vendor / Party Information"
                    description="Keep the main information simple. Party type helps users understand whether this is a transport provider, fuel station, workshop, or other service partner."
                >
                    <div class="grid4">
                        <x-fleetman.input id="partyId" label="Vendor / Party ID" required readonly />
                        <x-fleetman.input id="partyName" label="Party Name" placeholder="Example: Speed Transport Services" required />
                        <x-fleetman.select id="partyType" label="Party Type" :options="$fleetman['options']['party_types']" placeholder="Select type" required />
                        <x-fleetman.select id="partyStatus" label="Status" :options="$fleetman['options']['party_statuses']" required />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.input id="partyPhone" label="Phone Number" placeholder="01XXXXXXXXX" required />
                        <x-fleetman.input id="partyEmail" label="Email" placeholder="vendor@example.com" />
                        <x-fleetman.input id="partyWhatsapp" label="WhatsApp Number" placeholder="01XXXXXXXXX" />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.input id="tradeLicense" label="Trade License No." placeholder="Optional" />
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
                    description="Add one or more contact persons. Keep the main contact first."
                >
                    <x-slot:action>
                        <button type="button" class="btn light" id="addPartyContactBtn">＋ Add Contact Person</button>
                    </x-slot:action>
                    <div id="partyContacts"></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card
                    title="3. Documents"
                    description="Instead of uploading unnamed files only, write document name and reference so users can understand it later."
                >
                    <x-slot:action>
                        <button type="button" class="btn light" id="addPartyDocumentBtn">＋ Add Document</button>
                    </x-slot:action>
                    <div id="partyDocuments"></div>
                </x-fleetman.section-card>
            </div>

            <aside>
                <x-fleetman.side-note title="Standard naming suggestion">
                    <ul>
                        <li>For this screen, use <b>Vendor / Party</b>.</li>
                        <li>Field label should be <b>Party Name</b>, not only Name.</li>
                        <li>Menu can be <b>Vendors & Parties</b>.</li>
                        <li>In contract screen, use <b>Contract With: Client / Vendor</b>.</li>
                    </ul>
                </x-fleetman.side-note>
                <div class="required-box">
                    <b>Required before save:</b><br>
                    Party name, party type, phone number, address, status, and at least one contact person name + phone.
                </div>
            </aside>
        </div>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetPartyBtn">Reset Form</button>
            <button type="button" class="btn secondary" id="savePartyDraftBtn">Save as Draft</button>
            <button type="button" class="btn primary" id="savePartyBtn">Save Vendor / Party</button>
        </div>
    </div>

    <div id="vendorListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Vendor / Party List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportPartiesBtn">⬇ Export CSV</button>
                <button type="button" class="btn primary" id="newPartyBtn">＋ Add Vendor / Party</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Vendor / Party List"
            subtitle="List page with sample data, search, filters, view, edit, delete, and CSV export."
        >
            <x-slot:action>
                <div class="pillbar"><div class="pill active">All Parties</div><div class="pill">Active</div><div class="pill">Suppliers</div></div>
            </x-slot:action>
        </x-fleetman.title-card>

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
                    <thead><tr><th>Vendor / Party</th><th>Type</th><th>Phone</th><th>Contact Person</th><th>Payment Terms</th><th>Documents</th><th>Status</th><th>Address</th><th>Actions</th></tr></thead>
                    <tbody id="partyTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
