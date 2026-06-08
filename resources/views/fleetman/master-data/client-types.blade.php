@extends('layouts.fleetman')

@section('title', 'Client Type Master | FleetMan')
@section('mobile-title', 'Client Type Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Client Type Master']]">

    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Client Type Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage client types for dropdowns across the application.' }}"
    />

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.party-types') }}">
            <div class="master-overview-icon">🤝</div>
            <div><strong id="masterPartyTypeCount">0</strong><span>Party types available </span></div>
        </a>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.document-names') }}">
            <div class="master-overview-icon">🧾</div>
            <div><strong id="masterDocumentNameCount">0</strong><span>Document names available </span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">🏢</div>
            <div><strong id="masterClientTypeCount">0</strong><span>Client types available </span></div>
        </div>
    </div>

    <section class="card master-card" id="clientTypeMasterCard">
        <div class="section-head">
            <div>
                <h2>Client Type Master</h2>
            </div>
            <button type="button" class="btn light" id="resetClientTypeMasterBtn">Reset</button>
        </div>

        <form id="clientTypeMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="clientTypeEditingCode">
            <x-fleetman.input id="clientTypeMasterName" label="Client Type Name" placeholder="Example: Corporate" required />
            <x-fleetman.input id="clientTypeMasterCode" label="Code" placeholder="Example: CORPORATE" />
            <x-fleetman.input id="clientTypeMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="clientTypeMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="clientTypeMasterDescription" label="Description / Note" placeholder="Optional internal note about where this client type should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveClientTypeMasterBtn">Save Client Type</button>
                <button type="button" class="btn light" id="cancelClientTypeEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Client Types</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Client Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="clientTypeMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
