@extends('layouts.fleetman')

@section('title', 'Driver Contact Type Master | FleetMan')
@section('mobile-title', 'Driver Contact Type Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Driver Contact Type Master']]">

    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Driver Contact Type Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage the contact-number types available on the Driver page.' }}"
    />

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.document-names') }}">
            <div class="master-overview-icon">🧾</div>
            <div><strong id="masterDocumentNameCount">0</strong><span>Document names available </span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">📱</div>
            <div><strong id="masterDriverContactTypeCount">0</strong><span>Contact types available </span></div>
        </div>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.licence-types') }}">
            <div class="master-overview-icon">🪪</div>
            <div><strong id="masterLicenceTypeCount">0</strong><span>Licence types available </span></div>
        </a>
    </div>

    <section class="card master-card" id="driverContactTypeMasterCard">
        <div class="section-head">
            <div>
                <h2>Driver Contact Type Master</h2>

            </div>
            <button type="button" class="btn light" id="resetDriverContactTypeMasterBtn">Reset</button>
        </div>

        <form id="driverContactTypeMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="driverContactTypeEditingCode">
            <x-fleetman.input id="driverContactTypeMasterName" label="Contact Type Name" placeholder="Example: Emergency" required />
            <x-fleetman.input id="driverContactTypeMasterCode" label="Code" placeholder="Example: EMERGENCY"  />
            <x-fleetman.input id="driverContactTypeMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="driverContactTypeMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="driverContactTypeMasterDescription" label="Description / Note" placeholder="Optional internal note about this contact-number type." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveDriverContactTypeMasterBtn">Save Contact Type</button>
                <button type="button" class="btn light" id="cancelDriverContactTypeEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Driver Contact Types</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Contact Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="driverContactTypeMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
