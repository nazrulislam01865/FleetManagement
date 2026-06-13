@extends('layouts.fleetman')

@section('title', 'Drivers | FleetMan')
@section('mobile-title', 'Drivers')

@section('content')
<div class="page-section">
    <div id="driverAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Driver']]">
            <x-slot:actions><button type="button" class="btn light" data-page-target="driverListPage">← Driver List</button></x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card title="Add Driver" subtitle="A clean, step-by-step form for office staff. Keep the must-have fields first. Put extra information later.">

        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Basic Driver Information">
                    <div class="grid3">
                        <x-fleetman.input id="driverId" label="Driver ID" required readonly />
                        <x-fleetman.input id="driverFullName" label="Full Name" required placeholder="Example: Md. Karim Hossain" />
                        <x-fleetman.input id="driverFatherName" label="Father's Name" required placeholder="Example: Md. Abdul Mannan" />
                        <x-fleetman.input id="driverMotherName" label="Mother's Name" required placeholder="Example: Mst. Amena Begum" />
                        <x-fleetman.input id="driverWhatsapp" label="WhatsApp Number" type="tel" placeholder="Optional: 01XXXXXXXXX" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" />
                        <x-fleetman.input id="driverEmail" label="Email" type="email" placeholder="Optional: driver@email.com" />
                        <x-fleetman.input id="driverDob" label="Date of Birth" type="date" required />
                        <x-fleetman.input id="driverAge" label="Age" type="number" placeholder="Calculated automatically" required readonly min="0" max="120" />
                        <x-fleetman.input id="driverNid" label="NID" required placeholder="Maximum 17 digits" inputmode="numeric" maxlength="17" pattern="[0-9]{1,17}" />
                        <x-fleetman.input id="driverReference" label="Reference" required placeholder="Example: Vendor / Staff reference" />
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="2. Contact Numbers" id="driverContactsSection">
                    <div id="driverContacts"></div>
                    <button type="button" class="btn secondary" id="addDriverContactBtn" style="margin-top:10px">＋ Add Contact Number</button>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="3. License & Work Setup">
                    <div class="grid3">
                        <x-fleetman.input id="driverLicenseNo" label="Driving License No." required placeholder="Enter driving license number" />
                        <x-fleetman.select id="driverLicenseType" label="License Type" required :options="$fleetman['options']['driver_license_types']" placeholder="Select license type" />
                        <x-fleetman.input id="driverLicenseValidity" label="License Validity Date" type="date" required />
                        <x-fleetman.input id="driverSalary" label="Salary" type="number" required placeholder="Example: 25000" min="0" step="0.01" />
                        <x-fleetman.select id="driverSalaryTenure" label="Salary Tenure" required :options="$fleetman['options']['driver_salary_tenures']" />
                        <x-fleetman.input id="driverOtRate" label="Overtime Rate/Hourly" type="number" value="50" required min="0" step="0.01" />
                        <x-fleetman.input id="driverWorkingHour" label="Regular Working Hour" type="number" value="270" required min="1" step="0.01" />
                        <x-fleetman.select id="driverVendor" label="Vendor" :options="array_merge(['Own Payroll'], $fleetman['options']['driver_vendors'] ?? [])" placeholder="Select vendor" required />
                        <x-fleetman.select id="driverStatus" label="Driver Status" :options="$fleetman['options']['driver_statuses']" required />
                    </div>
                    <div class="field" id="driverDutyField" style="margin-top:16px">
                        <label>Preferred Duty Type <span class="req">*</span></label>
                        <div class="choice-grid">
                            @foreach($fleetman['options']['driver_duty_types'] as $duty)
                                <label class="choice"><input type="radio" name="driverDuty" value="{{ $duty['value'] }}"><span>{{ $duty['title'] }}</span><small>{{ $duty['description'] }}</small></label>
                            @endforeach
                        </div>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="4. Address & Notes">
                    <div class="grid">
                        <x-fleetman.textarea id="driverPresentAddress" label="Present Address" required placeholder="House, road, area, district" />
                        <x-fleetman.textarea id="driverPermanentAddress" label="Permanent Address" required placeholder="Village/house, post office, thana, district" />
                    </div>
                    <div style="margin-top:16px"><x-fleetman.textarea id="driverAbout" label="About / Remarks" required placeholder="Any important note about the driver" /></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="5. Driver Photo (Optional)">
                    <div class="photo-box driver-photo-box"><div class="field" style="flex:1"><label for="driverPhoto">Driver Photo (Optional)</label><input id="driverPhoto" type="file" accept="image/jpeg,image/png,image/webp" aria-required="false"><input id="driverPhotoData" type="hidden"><div class="temp-upload-progress hidden" id="driverPhotoProgress"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div><div class="upload-meta" id="driverPhotoInfo"></div><div class="hint">Optional. Allowed: JPG, JPEG, PNG or WEBP. Maximum size: 100 KB. The image preview appears below after upload.</div></div></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="6. Documents" id="driverDocumentsSection" class="document-section-card">
                    <x-slot:action><button type="button" class="btn secondary" id="addDriverDocumentBtn">+ Add document</button></x-slot:action>
                    <div id="driverDocuments"></div>
                </x-fleetman.section-card>
            </div>
        </div>
        <div class="save-bar"><button type="button" class="btn light" id="resetDriverBtn">Clear</button><button type="button" class="btn secondary" id="saveDriverDraftBtn">Save as Draft</button><button type="button" class="btn primary" id="saveDriverBtn">Save Driver & Go to List</button></div>
    </div>

    <div id="driverListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Driver List']]">
            <x-slot:actions>
                <button type="button" class="btn light" id="exportDriversBtn">⬇ Export CSV</button>
                @if(data_get($fleetman, 'auth.pageAccess.canManage'))
                    <a href="{{ route('fleet.drivers', ['action' => 'add']) }}" class="btn primary" id="addDriverFromListBtn">＋ Add Driver</a>
                @else
                    <span class="btn primary rbac-control-muted" id="addDriverFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Driver</span>
                @endif
            </x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card title="Driver List" subtitle="All saved drivers appear here. Search, filter, view, edit or export."></x-fleetman.title-card>
        <div class="kpi"><x-fleetman.kpi-card id="driverKpiTotal" label="Total drivers" /><x-fleetman.kpi-card id="driverKpiActive" label="Active" /><x-fleetman.kpi-card id="driverKpiExpired" label="Licences Expiring soon" /><x-fleetman.kpi-card id="driverKpiDocs" label="Total documents Uploaded" /></div>
        <div class="card">
            <div class="filters">
                <input id="driverSearch" placeholder="Search by name, mobile, NID, license">
                <x-fleetman.select id="driverFilterStatus" label="" :options="$fleetman['options']['driver_statuses']" placeholder="All status" />
                <x-fleetman.select id="driverFilterLicense" label="" :options="$fleetman['options']['driver_license_types']" placeholder="All license" />
                <x-fleetman.select
                    id="driverFilterValidity"
                    label=""
                    :options="[
                        'within-180-days' => 'License review: within 180 days',
                        'expired' => 'Expired licenses',
                        'beyond-180-days' => 'Valid beyond 180 days',
                    ]"
                    placeholder="All validity"
                />
                <x-fleetman.select id="driverFilterTenure" label="" :options="$fleetman['options']['driver_salary_tenures']" placeholder="All salary type" />
                <button type="button" class="btn light" id="clearDriverFiltersBtn">Clear Filter</button>
            </div>
            <div class="table-wrap driver-table"><table><thead><tr><th>Created At</th><th>Driver</th><th>Contact</th><th>License</th><th>Validity</th><th>Salary</th><th>Working Hour</th><th>Vendor</th><th>Docs</th><th>Expiring Documents</th><th>Status</th><th>Action</th></tr></thead><tbody id="driverTbody"></tbody></table></div>
        </div>
    </div>
</div>
@endsection
