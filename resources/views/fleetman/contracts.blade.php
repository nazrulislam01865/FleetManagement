@extends('layouts.fleetman')

@section('title', 'Contracts | FleetMan')
@section('mobile-title', 'Contracts')

@section('content')
<div class="page-section contract-page">
    <div id="contractCreatePage">
        <x-fleetman.topbar :items="[['label' => 'Business'], ['label' => 'Contracts'], ['label' => 'Create Contract']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-contract-page-target="contractListPage">← Contract List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Create Contract"
            subtitle="Redesigned to be simpler and easier for non-technical users. Large sections are grouped clearly, repeating rows are card-based, and saving auto-forwards to the contract list."
        >
            <x-slot:action>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <button class="btn secondary" type="button" id="loadContractExistingBtn">Use Existing Contract</button>
                    <button class="btn light" type="button" id="resetContractBtn">Clear Form</button>
                </div>
            </x-slot:action>
        </x-fleetman.title-card>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Basic Contract Information</h2>
                    <p>Start with who the contract is with, its amount, dates, and the overall details.</p>
                </div>
                <span class="badge soft">Simple step-by-step layout</span>
            </div>

            <div class="contract-grid">
                <div class="field contract-col-4">
                    <label for="contractId">Contract ID <span class="req">*</span></label>
                    <input id="contractId" readonly>
                </div>
                <div class="field contract-col-4">
                    <label>Contract With <span class="req">*</span></label>
                    <div class="segmented" id="contractWithGroup">
                        <button class="chip active" type="button" data-contract-chip="contractWith" data-value="Client">Client</button>
                        <button class="chip" type="button" data-contract-chip="contractWith" data-value="Vendor">Vendor</button>
                    </div>
                    <div class="hint">Large button selection is easier than a small dropdown.</div>
                </div>
                <div class="field contract-col-4">
                    <label for="contractParty">Contract Party <span class="req">*</span></label>
                    <select id="contractParty"></select>
                    <input type="hidden" id="contractPartyId">
                </div>
                <div class="field contract-col-3">
                    <label for="contractAmount">Contract Amount <span class="req">*</span></label>
                    <input id="contractAmount" type="number" step="0.01" placeholder="0">
                </div>
                <div class="field contract-col-3">
                    <label>Status <span class="req">*</span></label>
                    <div class="segmented" id="contractStatusGroup">
                        <button class="chip active" type="button" data-contract-chip="status" data-value="Initiated">Initiated</button>
                        <button class="chip" type="button" data-contract-chip="status" data-value="Active">Active</button>
                        <button class="chip" type="button" data-contract-chip="status" data-value="Completed">Completed</button>
                    </div>
                </div>
                <div class="field contract-col-3">
                    <label for="contractStart">Contract Start <span class="req">*</span></label>
                    <input id="contractStart" type="date">
                </div>
                <div class="field contract-col-3">
                    <label for="contractEnd">Contract End <span class="req">*</span></label>
                    <input id="contractEnd" type="date">
                </div>
                <div class="field contract-col-12">
                    <label for="contractDetails">Details <span class="req">*</span></label>
                    <textarea id="contractDetails" placeholder="Write the main purpose, scope, route/service details, and any important terms."></textarea>
                    <div class="hint">Write the main purpose, scope, route/service details, and any important terms.</div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Vehicle & Driver Assignment</h2>
                    <p>Each assignment is shown as a clear card. Vehicles come from the vehicle table and drivers come from the driver table.</p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                    <span class="badge soft">Auto summary on list page</span>
                    <button class="btn secondary small" type="button" id="addContractAssignmentBtn">+ Add Assignment</button>
                </div>
            </div>
            <div id="contractAssignments"></div>
        </section>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Documents</h2>
                    <p>Add supporting files with a clear document name. Files are stored by Contract ID after save.</p>
                </div>
                <button class="btn secondary small" type="button" id="addContractDocumentBtn">+ Add Document</button>
            </div>
            <div id="contractDocuments"></div>
            <div class="notice">Each document row has a <b>Document Name</b>, <b>Document Type</b>, and <b>File</b> field so users know what they uploaded.</div>
        </section>

        <div class="save-bar">
            <button class="btn light" type="button" id="saveContractDraftBtn">Save Draft</button>
            <button class="btn primary" type="button" id="submitContractBtn">Submit Contract</button>
        </div>
    </div>

    <div id="contractListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Business'], ['label' => 'Contracts'], ['label' => 'Contract List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportContractsBtn">⬇ Export CSV</button>
                <button class="btn primary" type="button" id="newContractBtn">+ Create Another Contract</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Contract List"
            subtitle="After saving or submitting, users are automatically taken to this list. Filters and a fixed scrollable table make it easier to review many contracts."
        >
            <x-slot:action>
                <div class="pillbar"><div class="pill active">All Contracts</div><div class="pill">Active</div><div class="pill">Draft</div></div>
            </x-slot:action>
        </x-fleetman.title-card>

        <section class="contract-kpis">
            <div class="contract-kpi"><strong id="contractKpiTotal">0</strong><span>Total Contracts</span></div>
            <div class="contract-kpi"><strong id="contractKpiActive">0</strong><span>Active</span></div>
            <div class="contract-kpi"><strong id="contractKpiDraft">0</strong><span>Draft</span></div>
            <div class="contract-kpi"><strong id="contractKpiVehicles">0</strong><span>Assigned Vehicles</span></div>
            <div class="contract-kpi"><strong id="contractKpiValue">৳ 0</strong><span>Total Contract Value</span></div>
        </section>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Filters</h2>
                    <p>Use simple filters to find contracts quickly.</p>
                </div>
            </div>
            <div class="contract-grid">
                <div class="field contract-col-3"><label for="contractFilterStatus">Status</label><select id="contractFilterStatus"><option value="">All</option><option value="Draft">Draft</option><option value="Submitted">Submitted</option><option value="Initiated">Initiated</option><option value="Active">Active</option><option value="Completed">Completed</option></select></div>
                <div class="field contract-col-3"><label for="contractFilterWith">Contract With</label><select id="contractFilterWith"><option value="">All</option><option>Client</option><option>Vendor</option></select></div>
                <div class="field contract-col-3"><label for="contractFilterParty">Party Name</label><input id="contractFilterParty" placeholder="Search party name"></div>
                <div class="field contract-col-3"><label for="contractRowsPerPage">Rows per page</label><select id="contractRowsPerPage"><option value="10">Load 10 rows</option><option value="20">Load 20 rows</option><option value="30">Load 30 rows</option></select></div>
            </div>
        </section>

        <section class="contract-list-shell">
            <div class="contract-list-toolbar">
                <div>
                    <h2>Contract Records</h2>
                    <p>Only the table box scrolls horizontally. The page remains fixed.</p>
                </div>
                <span class="badge soft">Auto-forward after save</span>
            </div>
            <div class="contract-fixed-table-box">
                <div class="contract-table-scroller">
                    <table>
                        <thead>
                            <tr>
                                <th class="contract-sticky-1">Contract ID</th>
                                <th class="contract-sticky-2">Party</th>
                                <th class="contract-sticky-3">Contract With</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Vehicle Count</th>
                                <th>Driver Count</th>
                                <th>Documents</th>
                                <th>Saved As</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="contractListBody"></tbody>
                    </table>
                </div>
                <div class="contract-pagination">
                    <div id="contractPageInfo"></div>
                    <div class="contract-page-buttons">
                        <button class="mini-btn" type="button" id="contractPrevPageBtn">Previous</button>
                        <span id="contractPageNumbers"></span>
                        <button class="mini-btn" type="button" id="contractNextPageBtn">Next</button>
                    </div>
                </div>
            </div>
            <div class="contract-mobile-cards" id="contractMobileCards"></div>
        </section>
    </div>
</div>
@endsection
