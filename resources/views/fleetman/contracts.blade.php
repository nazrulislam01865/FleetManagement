@extends('layouts.fleetman')

@section('title', 'Contracts | FleetMan')
@section('mobile-title', 'Contracts')

@section('content')
<div class="page-section contract-page">
    <div id="contractCreatePage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Add Contract']]">
            <x-slot:actions>
                <button type="button" class="btn light" data-contract-page-target="contractListPage">← Contract List</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Add Contract"
            subtitle="Redesigned to be simpler and easier for non-technical users. Large sections are grouped clearly, repeating rows are card-based, and saving auto-forwards to the contract list."
        >
            <x-slot:action>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <button class="btn light" type="button" id="resetContractBtn">Clear Form</button>
                </div>
            </x-slot:action>
        </x-fleetman.title-card>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Basic Contract Information</h2>
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
                </div>
            </div>
        </section>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Vehicle & Driver Assignment</h2>
                </div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                    <button class="btn secondary small" type="button" id="addContractAssignmentBtn">+ Add Assignment</button>
                </div>
            </div>
            <div id="contractAssignments"></div>
        </section>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Documents</h2>
                </div>
                <button class="btn secondary small" type="button" id="addContractDocumentBtn">+ Add Document</button>
            </div>
            <div id="contractDocuments"></div>
        </section>

        <div class="save-bar">
            <button class="btn light" type="button" id="saveContractDraftBtn">Save Draft</button>
            <button class="btn primary" type="button" id="submitContractBtn">Submit Contract</button>
        </div>
    </div>

    <div id="contractListPage">
        <x-fleetman.topbar :items="[['label' => 'Contract List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportContractsBtn">⬇ Export CSV</button>
                <button class="btn primary" type="button" id="newContractBtn">+ Create Another Contract</button>
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card
            title="Contract List"
            subtitle="After saving or submitting, users are automatically taken to this list. Filters and a fixed scrollable table make it easier to review many contracts."
        />

        <div class="kpi">
            <x-fleetman.kpi-card id="contractKpiTotal" label="Total Contracts" />
            <x-fleetman.kpi-card id="contractKpiActive" label="Active" />
            <x-fleetman.kpi-card id="contractKpiDraft" label="Draft" />
            <x-fleetman.kpi-card id="contractKpiVehicles" label="Assigned Vehicles" />
            <x-fleetman.kpi-card id="contractKpiValue" label="Total Contract Value" />
        </div>

        <div class="card">
            <div class="filters">
                <input id="contractFilterParty" placeholder="Search party name">
                <x-fleetman.select id="contractFilterWith" label="" :options="['Client', 'Vendor']" placeholder="All contract with" />
                <x-fleetman.select id="contractFilterStatus" label="" :options="['Draft', 'Submitted', 'Initiated', 'Active', 'Completed']" placeholder="All status" />
                <select id="contractRowsPerPage">
                    <option value="10">10 rows</option>
                    <option value="20">20 rows</option>
                    <option value="30">30 rows</option>
                </select>
                <button type="button" class="btn light" id="clearContractFiltersBtn">Clear Filter</button>
            </div>
            
            <div class="table-wrap contract-table">
                <table>
                    <thead>
                        <tr>
                            <th>Contract ID</th>
                            <th>Party</th>
                            <th>Contract With</th>
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
            
            <div class="pagination" style="padding: 16px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border);">
                <div id="contractPageInfo" class="hint"></div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button class="btn light small" type="button" id="contractPrevPageBtn">Previous</button>
                    <span id="contractPageNumbers" class="hint"></span>
                    <button class="btn light small" type="button" id="contractNextPageBtn">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
