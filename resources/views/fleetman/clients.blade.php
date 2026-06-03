@extends('layouts.fleetman')

@section('title', 'Clients | FleetMan')
@section('mobile-title', 'Clients')

@section('content')
<div class="page-section">
    <div id="clientAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Client']]">
            <x-slot:actions><button type="button" class="btn light" data-page-target="clientListPage">← Client List</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Add New Client" subtitle="A simple client form for non-technical users. Keep key fields up front, then add one or more contact persons below."><x-slot:action><button type="button" class="btn secondary" id="loadClientSampleBtn">Use sample data</button></x-slot:action></x-fleetman.title-card>
        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Client Information" description="Enter the client or company name first. The system generates the Client ID automatically.">
                    <div class="grid3">
                        <x-fleetman.input id="clientId" label="Client ID" required readonly />
                        <x-fleetman.input id="clientName" label="Client Name" required placeholder="Example: ABC Logistics Ltd." />
                        <x-fleetman.input id="clientEmail" label="Email" placeholder="company@example.com" />
                        <x-fleetman.input id="clientPhone" label="Phone Number" required placeholder="01XXXXXXXXX" />
                        <x-fleetman.input id="clientWhatsapp" label="WhatsApp Number" placeholder="01XXXXXXXXX" />
                        <x-fleetman.input id="clientReference" label="Reference" placeholder="Who referred this client?" />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.select id="clientType" label="Client Type" :options="$fleetman['options']['client_types']" value="Corporate" />
                        <x-fleetman.select id="clientStatus" label="Status" :options="$fleetman['options']['client_statuses']" value="Active" />
                        <x-fleetman.select id="clientContactMethod" label="Preferred Contact Method" :options="$fleetman['options']['client_contact_methods']" value="Phone" />
                    </div>
                    <div style="margin-top:16px"><x-fleetman.textarea id="clientAddress" label="Permanent Address" required placeholder="House / Road / Area / City" /></div>
                    <div style="margin-top:16px"><x-fleetman.textarea id="clientAbout" label="About / Notes" placeholder="Short note about this client, operation area, service requirement, billing note, etc." hint="Use plain language so any office user can understand the record later." /></div>
                </x-fleetman.section-card>
                <x-fleetman.section-card title="2. Contact Person(s)" description="Add one or more contact persons. Keep the primary contact first.">
                    <x-slot:action><button type="button" class="btn light" id="addClientContactBtn">＋ Add Contact Person</button></x-slot:action>
                    <div id="clientContacts"></div>
                </x-fleetman.section-card>
            </div>
            <aside>
                <x-fleetman.side-note title="Recommended form flow"><ul><li>First enter client name and phone number.</li><li>Add the main contact person at the top.</li><li>Use status <b>Prospect</b> for leads that are not active yet.</li><li>After save, the prototype redirects to the client list page.</li></ul></x-fleetman.side-note>
                <div class="required-box"><b>Required before save:</b><br>Client name, phone number, address, and at least one contact person name + phone.</div>
            </aside>
        </div>
        <div class="save-bar"><button type="button" class="btn light" id="resetClientBtn">Reset Form</button><button type="button" class="btn secondary" id="saveClientDraftBtn">Save as Draft</button><button type="button" class="btn primary" id="saveClientBtn">Save Client</button></div>
    </div>

    <div id="clientListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Client List']]">
            <x-slot:actions><button type="button" class="btn light" id="exportClientsBtn">⬇ Export CSV</button><button type="button" class="btn primary" id="newClientBtn">＋ Add Client</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Client List" subtitle="Simple list page with sample data, search, filters, and quick actions. Designed for easy day-to-day office use."><x-slot:action><div class="pillbar"><div class="pill active">All Clients</div><div class="pill">Recently Added</div><div class="pill">Active</div></div></x-slot:action></x-fleetman.title-card>
        <div class="kpi"><x-fleetman.kpi-card id="clientKpiTotal" label="Total Clients" /><x-fleetman.kpi-card id="clientKpiActive" label="Active Clients" /><x-fleetman.kpi-card id="clientKpiContacts" label="Total Contact Persons" /><x-fleetman.kpi-card id="clientKpiEmail" label="Clients with Email" /></div>
        <div class="card">
            <div class="filters">
                <input id="clientSearch" placeholder="Search by client name, phone, email, contact person, or client ID">
                <x-fleetman.select id="clientFilterStatus" label="" :options="$fleetman['options']['client_statuses']" placeholder="All Status" />
                <x-fleetman.select id="clientFilterType" label="" :options="$fleetman['options']['client_types']" placeholder="All Types" />
                <x-fleetman.select id="clientFilterMethod" label="" :options="$fleetman['options']['client_contact_methods']" placeholder="All Contact Methods" />
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyClientFiltersBtn">Apply</button><button type="button" class="btn light" id="clearClientFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap client-table"><table><thead><tr><th>Client</th><th>Main Phone</th><th>Contact Person(s)</th><th>Type</th><th>Status</th><th>Preferred Contact</th><th>Address</th><th>Actions</th></tr></thead><tbody id="clientTbody"></tbody></table></div>
        </div>
    </div>
</div>
@endsection
