@extends('layouts.fleetman')

@section('title', 'Employees | FleetMan')
@section('mobile-title', 'Employees')

@section('content')
<div class="page-section">
    <div id="employeeAddPage">
        <x-fleetman.topbar :items="[['label' => 'Add Employee']]">
            <x-slot:actions><button type="button" class="btn light" data-page-target="employeeListPage">← Employee List</button></x-slot:actions>
        </x-fleetman.topbar>

        <x-fleetman.title-card title="Add Employee" subtitle="A clearer employee form for non-technical office users. The most important details are grouped first, then salary, address, and notes.">

        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Basic Information">
                    <div class="grid3">
                        <x-fleetman.input id="employeeId" label="Employee ID" required readonly />
                        <x-fleetman.input id="employeeFullName" label="Full Name" required placeholder="Example: Md. Rafiq Islam" />
                        <x-fleetman.input id="employeeFatherName" label="Father's Name" required />
                        <x-fleetman.input id="employeeMotherName" label="Mother's Name" required />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.input id="employeeNid" label="NID" required placeholder="National ID number" inputmode="numeric" maxlength="17" pattern="[0-9]{1,17}" />
                        <x-fleetman.input id="employeeEmail" label="Email" type="email" placeholder="employee@example.com" />
                        <x-fleetman.input id="employeeReference" label="Reference" placeholder="Who referred this employee?" />
                        <x-fleetman.input id="employeeSocialMedia" label="Social Media IDs" placeholder="Optional" />
                    </div>
                </x-fleetman.section-card>

                {{-- Section 2: Contact Numbers (dynamic) --}}
                <x-fleetman.section-card title="2. Contact Numbers">
                    <div id="employeeContacts"></div>
                    <button type="button" class="btn secondary" id="addEmployeeContactBtn" style="margin-top:10px">＋ Add Contact Number</button>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="3. Employment Information">
                    <div class="grid3">
                        <div class="field">
                            <label for="employeeDesignation">Designation <span class="req">*</span></label>
                            <input id="employeeDesignation" list="employeeDesignationList" placeholder="Example: Office Assistant" required aria-required="true">
                            <datalist id="employeeDesignationList">
                                @foreach($fleetman['options']['employee_designations'] ?? [] as $designation)
                                    <option value="{{ $designation }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <x-fleetman.input id="employeeJoiningDate" label="Joining Date" type="date" required />
                        <x-fleetman.select id="employeeStatus" label="Status" required :options="$fleetman['options']['employee_statuses'] ?? []" value="Active" />
                        <x-fleetman.input id="employeeAge" label="Age" type="number" min="0" max="120" placeholder="Optional" />
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <x-fleetman.input id="employeeSalary" label="Salary" type="number" required placeholder="Example: 18000" />
                        <x-fleetman.select id="employeeSalaryTenure" label="Salary Tenure" required :options="$fleetman['options']['employee_salary_tenures'] ?? []" value="Monthly" />
                        <x-fleetman.input id="employeeOvertimeRate" label="Overtime Rate/Hourly" type="number" min="0" step="0.01" placeholder="Optional" />
                        <div></div>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="4. Address &amp; Notes">
                    <x-fleetman.textarea id="employeePresentAddress" label="Present Address" required placeholder="Current address" />
                    <div style="margin-top:16px"><x-fleetman.textarea id="employeePermanentAddress" label="Permanent Address" required placeholder="Permanent address" /></div>
                    <div style="margin-top:16px"><x-fleetman.textarea id="employeeAbout" label="About / Notes" placeholder="Short note about the employee, duty note, or internal remarks." /></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="5. Employee Photo">
                    <div class="field employee-photo-box">
                        <label for="employeePhoto">Employee Photo <span class="req">*</span></label>
                        <input id="employeePhoto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <input id="employeePhotoData" type="hidden">
                        <div class="temp-upload-progress hidden" id="employeePhotoProgress"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div>
                        <div class="upload-meta" id="employeePhotoInfo"></div>
                        <div class="hint">Allowed: JPG, JPEG, PNG or WEBP. Maximum size: 100 KB. The preview appears below after selection.</div>
                    </div>
                </x-fleetman.section-card>

                {{-- Section 6: Documents (dynamic multi-upload) --}}
                <x-fleetman.section-card title="6. Documents">
                    <x-slot:action><button type="button" class="btn secondary" id="addEmployeeDocumentBtn">+ Add document</button></x-slot:action>
                    <div id="employeeDocuments"></div>
                </x-fleetman.section-card>
            </div>

        </div>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetEmployeeBtn">Reset Form</button>
            <button type="button" class="btn secondary" id="saveEmployeeDraftBtn">Save as Draft</button>
            <button type="button" class="btn primary" id="saveEmployeeBtn">Save Employee</button>
        </div>
    </div>

    <div id="employeeListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Employee List']]">
            <x-slot:actions><button type="button" class="btn light" id="exportEmployeesBtn">⬇ Export CSV</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Employee List" subtitle="A simple employee list with sample data, quick search, filters, and common actions. Designed for everyday office use.">
        </x-fleetman.title-card>
        <div class="kpi">
            <x-fleetman.kpi-card id="employeeKpiTotal" label="Total Employees" />
            <x-fleetman.kpi-card id="employeeKpiActive" label="Active Employees" />
            <x-fleetman.kpi-card id="employeeKpiMonthly" label="Monthly Employees" />
            <x-fleetman.kpi-card id="employeeKpiPayroll" label="Total Salary" />
        </div>
        <div class="card">
            <div class="filters employee-filters">
                <input id="employeeSearch" placeholder="Search by employee name, ID, designation, phone, or NID">
                <x-fleetman.select id="employeeFilterStatus" label="" :options="$fleetman['options']['employee_statuses'] ?? []" placeholder="All Status" />
                <x-fleetman.select id="employeeFilterTenure" label="" :options="$fleetman['options']['employee_salary_tenures'] ?? []" placeholder="All Salary Tenures" />
                <x-fleetman.select id="employeeFilterDesignation" label="" :options="$fleetman['options']['employee_designations'] ?? []" placeholder="All Designations" />
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyEmployeeFiltersBtn">Apply</button><button type="button" class="btn light" id="clearEmployeeFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap employee-table"><table><thead><tr><th>Employee</th><th>Contacts</th><th>Designation</th><th>Joining Date</th><th>Salary</th><th>Status</th><th>Present Address</th><th>Docs</th><th>Actions</th></tr></thead><tbody id="employeeTbody"></tbody></table></div>
        </div>
    </div>
</div>

{{-- Pass employee_document_templates to JS --}}
<script>
    window.FLEETMAN_EMPLOYEE_DOC_TEMPLATES = @json($fleetman['options']['employee_document_templates'] ?? []);
</script>
@endsection
