@extends('layouts.fleetman')

@section('title', 'Master Data | FleetMan')
@section('mobile-title', 'Master Data')

@section('content')
<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data']]">
        <x-slot:actions>
            <span class="badge soft">Database backed dropdown values</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="Master Data"
        subtitle="Manage reusable dropdown values from one place. Document Types saved here are loaded dynamically across FleetMan forms."
    />

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">🤝</div>
            <div><strong id="masterPartyTypeCount">0</strong><span>Party types available for Vendor / Party dropdowns</span></div>
        </div>
        <div class="master-overview-card">
            <div class="master-overview-icon">🧾</div>
            <div><strong id="masterDocumentNameCount">0</strong><span>Document types available for document dropdowns</span></div>
        </div>
        <div class="master-overview-card">
            <div class="master-overview-icon">🪪</div>
            <div><strong id="masterLicenceTypeCount">0</strong><span>Licence types available for Driver dropdowns</span></div>
        </div>
    </div>

    <div class="master-data-grid">
        <section class="card master-card" id="partyTypeMasterCard">
            <div class="section-head">
                <div>
                    <h2>Party Type Master</h2>
                    <p>Add party types once and use them in vendor / party related dropdowns across the app.</p>
                </div>
                <button type="button" class="btn light" id="resetPartyTypeMasterBtn">Reset</button>
            </div>

            <form id="partyTypeMasterForm" class="master-form" autocomplete="off">
                <input type="hidden" id="partyTypeEditingCode">
                <x-fleetman.input id="partyTypeMasterName" label="Party Type Name" placeholder="Example: Fuel Station" required />
                <x-fleetman.input id="partyTypeMasterCode" label="Code" placeholder="Example: FUEL_STATION" hint="Code is auto-generated but can be edited before save." />
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
                <div><b>Added Party Types</b><small>These rows are stored in the database lookup table.</small></div>
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

        <section class="card master-card" id="documentNameMasterCard">
            <div class="section-head">
                <div>
                    <h2>Document Type</h2>
                </div>
                <button type="button" class="btn light" id="resetDocumentNameMasterBtn">Reset</button>
            </div>

            <form id="documentNameMasterForm" class="master-form document-type-master-form" autocomplete="off">
                <input type="hidden" id="documentNameEditingCode">
                <x-fleetman.input id="documentNameMasterName" label="Document Name" placeholder="Example: Trade License Copy" required />
                <x-fleetman.input id="documentNameMasterCode" label="Code" placeholder="Example: TRADE_LICENSE_COPY" />
                <x-fleetman.input id="documentNameMasterSort" label="Sort Order" type="number" value="0" min="0" />
                <x-fleetman.select id="documentNameMasterStatus" label="Status" :options="['Active', 'Inactive']" value="Active" />

                <fieldset class="master-form-full document-type-check-field" id="documentNameMasterTypesField">
                    <legend>Document Type / Used For <span class="req">*</span></legend>
                    <div class="document-type-check-grid" id="documentNameMasterTypes">
                        @foreach (['All Modules', 'Vehicles', 'Drivers', 'Vendors', 'Vendors & Parties', 'Employees', 'Clients', 'Contracts'] as $documentType)
                            @php($typeId = 'documentNameMasterType'.$loop->index)
                            <label class="document-type-check" for="{{ $typeId }}">
                                <input type="checkbox" id="{{ $typeId }}" name="documentNameMasterTypes[]" value="{{ $documentType }}" @checked($documentType === 'All Modules')>
                                <span>{{ $documentType }}</span>
                            </label>
                        @endforeach
                    </div>
                    <small class="document-type-check-error" id="documentNameMasterTypesError" hidden>Select at least one document type.</small>
                </fieldset>

                <div class="master-form-full">
                    <x-fleetman.textarea id="documentNameMasterDescription" label="Description / Note" placeholder="Optional note, such as required for vendor onboarding or vehicle renewal." />
                </div>
                <div class="master-form-actions">
                    <button type="submit" class="btn primary" id="saveDocumentNameMasterBtn">Save Document Type</button>
                    <button type="button" class="btn light" id="cancelDocumentNameEditBtn">Cancel Edit</button>
                </div>
            </form>

            <div class="master-table-title">
                <div><b>Added Document Types</b></div>
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
                <div><b>Added Licence Types</b><small>Active rows appear in driver dropdowns across the system.</small></div>
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
</div>
@endsection
