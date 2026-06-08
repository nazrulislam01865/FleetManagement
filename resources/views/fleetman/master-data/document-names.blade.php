@extends('layouts.fleetman')

@section('title', 'Document Name Master | FleetMan')
@section('mobile-title', 'Document Name Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Document Name Master']]">

    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Document Name Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Add document names once and reuse them across FleetMan forms.' }}"
    />

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.party-types') }}">
            <div class="master-overview-icon">🤝</div>
            <div><strong id="masterPartyTypeCount">0</strong><span>Party types available </span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">🧾</div>
            <div><strong id="masterDocumentNameCount">0</strong><span>Document names available</span></div>
        </div>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.licence-types') }}">
            <div class="master-overview-icon">🪪</div>
            <div><strong id="masterLicenceTypeCount">0</strong><span>Licence types available </span></div>
        </a>
    </div>

    <section class="card master-card" id="documentNameMasterCard">
        <div class="section-head">
            <div>
                <h2>Document Name Master</h2>
            </div>
            <button type="button" class="btn light" id="resetDocumentNameMasterBtn">Reset</button>
        </div>

        <form id="documentNameMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="documentNameEditingCode">
            <x-fleetman.input id="documentNameMasterName" label="Document Name" placeholder="Example: Driving License Copy" required />
            <x-fleetman.select id="documentNameMasterType" label="Document Type / Used For" :options="['All Modules', 'Vehicles', 'Drivers', 'Vendors', 'Vendors & Parties', 'Employees', 'Clients', 'Contracts']" placeholder="Select who will use this document" required />
            <x-fleetman.input id="documentNameMasterCode" label="Code" placeholder="Example: DRIVING_LICENSE_COPY" hint="Used internally to keep the dropdown value stable." />
            <x-fleetman.input id="documentNameMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="documentNameMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="documentNameMasterDescription" label="Description / Note" placeholder="Optional note, such as required for vendor onboarding or vehicle renewal." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveDocumentNameMasterBtn">Save Document Name</button>
                <button type="button" class="btn light" id="cancelDocumentNameEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Document Names</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Used For</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="documentNameMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
