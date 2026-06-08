@extends('layouts.fleetman')

@section('title', 'Unit Master | FleetMan')
@section('mobile-title', 'Unit Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Unit Master']]">
        <x-slot:actions>
            <a href="{{ route('fleet.master-data.party-types') }}" class="btn secondary">Party Type Master</a>
            <span class="badge soft">Database backed dropdown values</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Unit Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage units for dropdowns across the application.' }}"
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
            <div class="master-overview-icon">🏢</div>
            <div><strong id="masterUnitCount">0</strong><span>Units available for Client dropdowns</span></div>
        </div>
    </div>

    <section class="card master-card" id="unitMasterCard">
        <div class="section-head">
            <div>
                <h2>Unit Master</h2>
                <p>Add units once and use them in unit related dropdowns across the app.</p>
            </div>
            <button type="button" class="btn light" id="resetUnitMasterBtn">Reset</button>
        </div>

        <form id="unitMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="unitEditingCode">
            <x-fleetman.input id="unitMasterName" label="Unit Name" placeholder="Example: Corporate" required />
            <x-fleetman.input id="unitMasterCode" label="Code" placeholder="Example: CORPORATE" hint="Code is auto-generated but can be edited before save." />
            <x-fleetman.input id="unitMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="unitMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="unitMasterDescription" label="Description / Note" placeholder="Optional internal note about where this unit should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveUnitMasterBtn">Save Unit</button>
                <button type="button" class="btn light" id="cancelUnitEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Units</b><small>These rows are stored in the fleet_units table.</small></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="unitMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
