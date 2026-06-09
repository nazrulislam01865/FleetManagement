@extends('layouts.fleetman')

@section('title', 'Contact Method Master | FleetMan')
@section('mobile-title', 'Contact Method Master')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Contact Method Master']]">
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Contact Method Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage preferred contact methods for dropdowns across the application.' }}"
    />

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.party-types') }}">
            <div class="master-overview-icon">🤝</div>
            <div><strong id="masterPartyTypeCount">0</strong><span>Party types available </span></div>
        </a>
        <a class="master-overview-card master-overview-link" href="{{ route('fleet.master-data.client-types') }}">
            <div class="master-overview-icon">🏢</div>
            <div><strong id="masterClientTypeCount">0</strong><span>Client types available </span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">📞</div>
            <div><strong id="masterContactMethodCount">0</strong><span>Contact methods available</span></div>
        </div>
    </div>

    <section class="card master-card" id="contactMethodMasterCard">
        <div class="section-head">
            <div>
                <h2>Contact Method Master</h2>
            </div>
            <button type="button" class="btn light" id="resetContactMethodMasterBtn">Reset</button>
        </div>

        <form id="contactMethodMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="contactMethodEditingCode">
            <x-fleetman.input id="contactMethodMasterName" label="Contact Method Name" placeholder="Example: Phone" required />
            <x-fleetman.input id="contactMethodMasterCode" label="Code" placeholder="Example: PHONE" />
            <x-fleetman.input id="contactMethodMasterSort" label="Sort Order" type="number" value="0" min="0" />
            <x-fleetman.select id="contactMethodMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />
            <div class="master-form-full">
                <x-fleetman.textarea id="contactMethodMasterDescription" label="Description / Note" placeholder="Optional internal note about where this contact method should be used." />
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveContactMethodMasterBtn">Save Contact Method</button>
                <button type="button" class="btn light" id="cancelContactMethodEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Contact Methods</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Created At</th><th>Contact Method</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="contactMethodMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
