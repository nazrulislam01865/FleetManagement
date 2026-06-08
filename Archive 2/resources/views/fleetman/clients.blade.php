@extends('layouts.fleetman')

@section('title', 'Clients | FleetMan')
@section('mobile-title', 'Clients')

@section('content')
<div class="page-section">
    <div id="clientAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Client']]">
            <x-slot:actions><button type="button" class="btn light" data-page-target="clientListPage">← Client List</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Add New Client" subtitle="A simple client form for non-technical users. Keep key fields up front, then add one or more contact persons below."></x-fleetman.title-card>
        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Client Information">
                    <div class="grid3">
                        <x-fleetman.input id="clientId" label="Client ID" required readonly />
                        <x-fleetman.input id="clientName" label="Client Name" required placeholder="Example: ABC Logistics Ltd." />
                        <x-fleetman.input id="clientEmail" label="Email" type="email" required placeholder="company@example.com" autocomplete="email" />
                        <x-fleetman.input id="clientPhone" label="Phone Number" type="tel" required placeholder="01XXXXXXXXX" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" autocomplete="tel" />
                        <x-fleetman.input id="clientWhatsapp" label="WhatsApp Number" type="tel" required placeholder="01XXXXXXXXX" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" />
                        <x-fleetman.input id="clientReference" label="Reference" required placeholder="Who referred this client?" />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.select id="clientType" label="Client Type" :options="$fleetman['options']['client_types']" value="Corporate" required />
                        <x-fleetman.select id="clientStatus" label="Status" :options="$fleetman['options']['client_statuses']" value="Active" required />
                        <x-fleetman.select id="clientContactMethod" label="Preferred Contact Method" :options="$fleetman['options']['client_contact_methods']" placeholder="Select Preferred Contact Method" required />
                    </div>
                    <div style="margin-top:16px"><x-fleetman.textarea id="clientAddress" label="Permanent Address" required placeholder="House / Road / Area / City" /></div>
                    <div style="margin-top:16px"><x-fleetman.textarea id="clientAbout" label="About / Notes" required placeholder="Short note about this client, operation area, service requirement, billing note, etc." /></div>
                </x-fleetman.section-card>
                <x-fleetman.section-card title="2. Contact Person(s)" class="client-contact-card">
                    <x-slot:action><button type="button" class="btn light" id="addClientContactBtn">＋ Add Contact Person</button></x-slot:action>
                    <div id="clientContacts"></div>
                </x-fleetman.section-card>
            </div>
        </div>
        <div class="save-bar"><button type="button" class="btn light" id="resetClientBtn">Reset Form</button><button type="button" class="btn secondary" id="saveClientDraftBtn">Save as Draft</button><button type="button" class="btn primary" id="saveClientBtn">Save Client</button></div>
    </div>

    <div id="clientListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Client List']]">
            <x-slot:actions><button type="button" class="btn light" id="exportClientsBtn">⬇ Export CSV</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Client List" subtitle="Simple list page with sample data, search, filters, and quick actions. Designed for easy day-to-day office use."></x-fleetman.title-card>
        <div class="kpi"><x-fleetman.kpi-card id="clientKpiTotal" label="Total Clients" /><x-fleetman.kpi-card id="clientKpiActive" label="Active Clients" /><x-fleetman.kpi-card id="clientKpiEmail" label="Clients with Email" /></div>
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
