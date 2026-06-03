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
            <x-slot:action><button type="button" class="btn secondary" id="loadEmployeeSampleBtn">Use sample data</button></x-slot:action>
        </x-fleetman.title-card>

        <div class="layout">
            <div>
                <x-fleetman.section-card title="1. Basic Information" description="Use clear labels and plain language so HR or operations users can enter data easily.">
                    <div class="grid4">
                        <x-fleetman.input id="employeeId" label="Employee ID" required readonly />
                        <x-fleetman.input id="employeeFullName" label="Full Name" required placeholder="Example: Md. Rafiq Islam" />
                        <x-fleetman.input id="employeeFatherName" label="Father's Name" required />
                        <x-fleetman.input id="employeeMotherName" label="Mother's Name" required />
                    </div>
                    <div class="grid4" style="margin-top:16px">
                        <x-fleetman.input id="employeeNid" label="NID" required placeholder="National ID number" />
                        <x-fleetman.input id="employeeEmail" label="Email" placeholder="employee@example.com" />
                        <x-fleetman.input id="employeeReference" label="Reference" placeholder="Who referred this employee?" />
                        <x-fleetman.input id="employeeSocialMedia" label="Social Media IDs" placeholder="Optional" />
                    </div>
                </x-fleetman.section-card>

                {{-- Section 2: Contact Numbers (dynamic) --}}
                <x-fleetman.section-card title="2. Contact Numbers" description="Add one or more contact numbers. Choose the type for each number. If the contact is a relative, a relationship field will appear.">
                    <div id="employeeContacts"></div>
                    <button type="button" class="btn secondary" id="addEmployeeContactBtn" style="margin-top:10px">＋ Add Contact Number</button>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="3. Employment Information" description="Keep service-related information together so the employee setup is easier to review later.">
                    <div class="grid4">
                        <div class="field">
                            <label for="employeeDesignation">Designation <span class="req">*</span></label>
                            <input id="employeeDesignation" list="employeeDesignationList" placeholder="Example: Office Assistant">
                            <datalist id="employeeDesignationList">
                                @foreach($fleetman['options']['employee_designations'] ?? [] as $designation)
                                    <option value="{{ $designation }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <x-fleetman.input id="employeeJoiningDate" label="Joining Date" type="date" required />
                        <x-fleetman.select id="employeeStatus" label="Status" required :options="$fleetman['options']['employee_statuses'] ?? []" value="Active" />
                        <x-fleetman.input id="employeeAge" label="Age" type="number" placeholder="Optional" />
                    </div>
                    <div class="grid4" style="margin-top:16px">
                        <x-fleetman.input id="employeeSalary" label="Salary" type="number" required placeholder="Example: 18000" />
                        <x-fleetman.select id="employeeSalaryTenure" label="Salary Tenure" required :options="$fleetman['options']['employee_salary_tenures'] ?? []" value="Monthly" />
                        <x-fleetman.input id="employeeOvertimeRate" label="Overtime Rate" type="number" placeholder="Optional" />
                        <div></div>
                    </div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="4. Address &amp; Notes" description="Keep both addresses visible together so users do not miss them.">
                    <x-fleetman.textarea id="employeePresentAddress" label="Present Address" required placeholder="Current address" />
                    <div style="margin-top:16px"><x-fleetman.textarea id="employeePermanentAddress" label="Permanent Address" required placeholder="Permanent address" /></div>
                    <div style="margin-top:16px"><x-fleetman.textarea id="employeeAbout" label="About / Notes" placeholder="Short note about the employee, duty note, or internal remarks." /></div>
                </x-fleetman.section-card>

                <x-fleetman.section-card title="5. Employee Photo" description="Upload the employee's profile photo. Supported formats: JPG, JPEG, PNG, WEBP. Max 5 MB.">
                    <div class="photo-box employee-photo-box">
                        <div class="photo-avatar" id="employeePhotoPreview">👤</div>
                        <div class="field" style="flex:1">
                            <label for="employeePhoto">Employee Image</label>
                            <input id="employeePhoto" type="file" accept="image/jpg,image/jpeg,image/png,image/webp">
                            <div class="hint">Accepted: JPG, JPEG, PNG, WEBP. Max size: 5 MB. The photo will be stored under the employee's folder in storage.</div>
                        </div>
                    </div>
                </x-fleetman.section-card>

                {{-- Section 6: Documents (dynamic multi-upload) --}}
                <x-fleetman.section-card title="6. Documents" description="Upload employee documents such as NID scan copy, educational certificates, or any required paperwork. Each file is stored under the employee's ID folder in storage.">
                    <div id="employeeDocuments"></div>
                    <button type="button" class="btn secondary" id="addEmployeeDocumentBtn" style="margin-top:10px">＋ Add Document</button>
                    <div class="hint" style="margin-top:10px">Accepted: JPG, JPEG, PNG, WEBP, PDF. Max 5 MB per file. Files are saved under <code>storage/fleet/employees/{employeeId}/documents/</code>.</div>
                </x-fleetman.section-card>
            </div>

            <aside>
                <x-fleetman.side-note title="Recommended form flow">
                    <ul>
                        <li>Enter employee name and at least one contact number first.</li>
                        <li>Set the contact type: <b>Office</b>, <b>Home</b>, or <b>Relative</b>.</li>
                        <li>For relative contacts, fill in the relationship (e.g., Brother, Wife).</li>
                        <li>Then add designation, joining date, and salary information.</li>
                        <li>Upload documents like NID scan, certificates, etc.</li>
                        <li>Use <b>Draft</b> if the employee profile is not completed yet.</li>
                        <li>After save, the system redirects to the employee list page.</li>
                    </ul>
                </x-fleetman.side-note>
                <div class="required-box"><b>Required before save:</b><br>Full name, father's name, mother's name, NID, at least one contact number, designation, joining date, salary, salary tenure, present address, and permanent address.</div>

                <div class="side-note" style="margin-top:14px">
                    <h3>📁 Document Storage</h3>
                    <p style="margin:0;color:#475467;line-height:1.65;font-size:13px">Documents are stored under:<br><code style="background:#eef4ff;border-radius:6px;padding:2px 6px;font-size:12px">storage/fleet/employees/<br>{employeeId}/documents/</code><br><br>Each employee has their own isolated folder.</p>
                </div>
            </aside>
        </div>

        <div class="save-bar">
            <button type="button" class="btn light" id="resetEmployeeBtn">Reset Form</button>
            <button type="button" class="btn secondary" id="saveEmployeeDraftBtn">Save as Draft</button>
            <button type="button" class="btn primary" id="saveEmployeeBtn">Save Employee</button>
        </div>
    </div>

    <div id="employeeListPage" class="hidden">
        <x-fleetman.topbar :items="[['label' => 'Employee List']]">
            <x-slot:actions><button type="button" class="btn light" id="exportEmployeesBtn">⬇ Export CSV</button><button type="button" class="btn primary" id="newEmployeeBtn">＋ Add Employee</button></x-slot:actions>
        </x-fleetman.topbar>
        <x-fleetman.title-card title="Employee List" subtitle="A simple employee list with sample data, quick search, filters, and common actions. Designed for everyday office use.">
            <x-slot:action><div class="pillbar"><div class="pill active">All Employees</div><div class="pill">Active</div><div class="pill">Recent Joins</div></div></x-slot:action>
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
