@extends('layouts.fleetman')

@section('title', 'Party Type Master | FleetMan')
@section('mobile-title', 'Party Type Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Party Type Master']]">
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Party Type Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Add party types once and use them across the app.' }}"
    />

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">🤝</div>
            <div><strong id="masterPartyTypeCount">0</strong><span>Party types available</span></div>
        </div>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.document-names') }}">
            <div class="master-overview-icon">🧾</div>
            <div><strong id="masterDocumentNameCount">0</strong><span>Document names available</span></div>
        </a>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.licence-types') }}">
            <div class="master-overview-icon">🪪</div>
            <div><strong id="masterLicenceTypeCount">0</strong><span>Licence types available</span></div>
        </a>
    </div>

    <section class="card master-card" id="partyTypeMasterCard">
        <div class="section-head">
            <div>
                <h2>Party Type Master</h2>

            </div>
            <button type="button" class="btn light" id="resetPartyTypeMasterBtn">Reset</button>
        </div>

        <form id="partyTypeMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="partyTypeEditingCode">
            <x-fleetman.input id="partyTypeMasterName" label="Party Type Name" placeholder="Example: Fuel Station" required />
            <x-fleetman.input id="partyTypeMasterCode" label="Code" placeholder="Example: FUEL_STATION" />
            <x-fleetman.input id="partyTypeMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="partyTypeMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="partyTypeMasterDescription" label="Description / Note" placeholder="Optional internal note about where this party type should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="savePartyTypeMasterBtn">Save Party Type</button>
                <button type="button" class="btn light" id="cancelPartyTypeEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Party Types</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Party Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="partyTypeMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
