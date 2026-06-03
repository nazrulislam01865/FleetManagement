@extends('layouts.fleetman')

@section('title', 'Licence Type Master | FleetMan')
@section('mobile-title', 'Licence Type Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Licence Type Master']]">
        <x-slot:actions>
            <a href="{{ route('fleet.master-data.party-types') }}" class="btn secondary">Party Type Master</a>
            <span class="badge soft">Database backed dropdown values</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Licence Type Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage driver licence types for dropdowns across the application.' }}"
    />

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.party-types') }}">
            <div class="master-overview-icon">🤝</div>
            <div><strong id="masterPartyTypeCount">0</strong><span>Party types available for Vendor / Party dropdowns</span></div>
        </a>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.document-names') }}">
            <div class="master-overview-icon">🧾</div>
            <div><strong id="masterDocumentNameCount">0</strong><span>Document names available for document dropdowns</span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">🪪</div>
            <div><strong id="masterLicenceTypeCount">0</strong><span>Licence types available for Driver dropdowns</span></div>
        </div>
    </div>

    <section class="card master-card" id="licenceTypeMasterCard">
        <div class="section-head">
            <div>
                <h2>Licence Type Master</h2>
                <p>Add licence types once and use them in driver related dropdowns across the app.</p>
            </div>
            <button type="button" class="btn light" id="resetLicenceTypeMasterBtn">Reset</button>
        </div>

        <form id="licenceTypeMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="licenceTypeEditingCode">
            <x-fleetman.input id="licenceTypeMasterName" label="Licence Type Name" placeholder="Example: Heavy" required />
            <x-fleetman.input id="licenceTypeMasterCode" label="Code" placeholder="Example: HEAVY" hint="Code is auto-generated but can be edited before save." />
            <x-fleetman.input id="licenceTypeMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="licenceTypeMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="licenceTypeMasterDescription" label="Description / Note" placeholder="Optional internal note about where this licence type should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveLicenceTypeMasterBtn">Save Licence Type</button>
                <button type="button" class="btn light" id="cancelLicenceTypeEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Licence Types</b><small>These rows are stored in the fleet_licence_types table.</small></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Licence Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="licenceTypeMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
