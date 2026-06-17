@php
    $contacts = array_values(array_filter((array) ($recordPayload['contacts'] ?? []), 'is_array'));
    $documents = array_values(array_filter((array) ($recordPayload['documents'] ?? []), 'is_array'));
    $employeePhotoUrl = $photoUrl($recordPayload['photo'] ?? []);
    $employeeStatus = $display($recordPayload['status'] ?? $record->status);
    $primaryContact = $recordPayload['contactNumber'] ?? data_get($contacts, '0.number', data_get($contacts, '0.phone'));
    $emergencyContact = collect($contacts)->first(fn (array $contact): bool => strcasecmp((string) ($contact['type'] ?? ''), 'Relative') === 0)
        ?? ($contacts[1] ?? $contacts[0] ?? []);
@endphp

<section class="record-profile-summary">
    <div class="record-profile-photo">
        @if($employeePhotoUrl !== '')
            <img src="{{ $employeePhotoUrl }}" alt="{{ $recordTitle }} photo">
        @else
            <span aria-hidden="true">👤</span>
        @endif
    </div>

    <div class="record-summary-main">
        <h2>{{ $display($recordPayload['fullName'] ?? $recordTitle) }}</h2>
        <p>{{ $display($recordPayload['designation'] ?? null) }}</p>
        <div class="record-tags">
            <span class="record-tag success">{{ $employeeStatus }}</span>
            <span class="record-tag primary">{{ $display($recordPayload['employeeId'] ?? $record->code) }}</span>
            <span class="record-tag warning">{{ $display($recordPayload['salaryTenure'] ?? null) }} Salary</span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Joining Date</label><div>{{ $formatDate($recordPayload['joiningDate'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Salary</label><div>{{ $formatMoney($recordPayload['salary'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Contact</label><div>{{ $display($primaryContact) }}</div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div>{{ $formatDateTime($record->updated_at) }}</div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">👤</div><div><h3>Personal Information</h3><p>Identity, contact, emergency contact and address details.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Full Name</th><td>{{ $display($recordPayload['fullName'] ?? $recordTitle) }}</td></tr>
            <tr><th>Age</th><td>{{ $display($recordPayload['age'] ?? null) }}</td></tr>
            <tr><th>NID</th><td>{{ $display($recordPayload['nid'] ?? null) }}</td></tr>
            <tr><th>Father Name</th><td>{{ $display($recordPayload['fatherName'] ?? null) }}</td></tr>
            <tr><th>Mother Name</th><td>{{ $display($recordPayload['motherName'] ?? null) }}</td></tr>
            <tr><th>Reference</th><td>{{ $display($recordPayload['reference'] ?? null) }}</td></tr>
            <tr><th>About</th><td>{{ $display($recordPayload['about'] ?? null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Contact Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Email</th><td>{{ $display($recordPayload['email'] ?? null) }}</td></tr>
            <tr><th>Contact Number</th><td>{{ $display($primaryContact) }}</td></tr>
            <tr><th>Social Media</th><td>{{ $display($recordPayload['socialMedia'] ?? null) }}</td></tr>
            <tr><th>Emergency Contact Type</th><td>{{ $display($emergencyContact['type'] ?? null) }}</td></tr>
            <tr><th>Emergency Relationship</th><td>{{ $display($emergencyContact['relationship'] ?? null) }}</td></tr>
            <tr><th>Emergency Number</th><td>{{ $display($emergencyContact['number'] ?? $emergencyContact['phone'] ?? null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Address Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Present Address</th><td>{{ $display($recordPayload['presentAddress'] ?? null) }}</td></tr>
            <tr><th>Permanent Address</th><td>{{ $display($recordPayload['permanentAddress'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">💼</div><div><h3>Employment Information</h3><p>Job, salary, status and system audit information.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Job Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Employee ID</th><td>{{ $display($recordPayload['employeeId'] ?? $record->code) }}</td></tr>
            <tr><th>Designation</th><td>{{ $display($recordPayload['designation'] ?? null) }}</td></tr>
            <tr><th>Status</th><td><span class="record-status-pill">{{ $employeeStatus }}</span></td></tr>
            <tr><th>Joining Date</th><td>{{ $formatDate($recordPayload['joiningDate'] ?? null, 'Y-m-d') }}</td></tr>
            <tr><th>Record Code</th><td>{{ $record->code }}</td></tr>
            <tr><th>Record Name</th><td>{{ $record->name }}</td></tr>
            <tr><th>Record Status</th><td>{{ $display($record->status) }}</td></tr>
            <tr><th>Validation Version</th><td>{{ $display($recordPayload['employeeValidationVersion'] ?? null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Salary Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Salary</th><td>{{ $formatMoney($recordPayload['salary'] ?? null) }}</td></tr>
            <tr><th>Salary Tenure</th><td>{{ $display($recordPayload['salaryTenure'] ?? null) }}</td></tr>
            <tr><th>Overtime Rate</th><td>{{ $display($recordPayload['overtimeRate'] ?? null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Audit Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Created At</th><td>{{ $formatDateTime($record->created_at) }}</td></tr>
            <tr><th>Created By</th><td>{{ $display($recordCreatorName ?? null, 'System / Legacy') }}</td></tr>
            <tr><th>Last Updated</th><td>{{ $formatDateTime($record->updated_at) }}</td></tr>
            <tr><th>Updated By</th><td>—</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">📎</div><div><h3>Documents</h3><p>Uploaded employee documents with expiry, reminder and file information.</p></div></div></div>
    <div class="record-table-section">
        <table class="record-file-table">
            <thead><tr><th>Document Name</th><th>Expiry</th><th>Reminder</th><th>Reference</th><th>File</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($documents as $document)
                    @php($documentFile = is_array($document['file'] ?? null) ? $document['file'] : [])
                    @php($documentUrl = $fileUrl($documentFile))
                    <tr>
                        <td data-label="Document Name">{{ $display($document['name'] ?? null) }}</td>
                        <td data-label="Expiry">{{ $formatDate($document['expiry'] ?? null, 'Y-m-d') }}</td>
                        <td data-label="Reminder">{{ $display($document['reminder'] ?? null) }}</td>
                        <td data-label="Reference">{{ $display($document['reference'] ?? null) }}</td>
                        <td data-label="File">{{ $fileDescription($documentFile) }}</td>
                        <td data-label="Action">
                            @if($documentUrl !== '')
                                <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="record-open-btn">Open</a>
                            @else
                                <span class="record-file-unavailable">No file</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="record-empty-cell">No employee documents uploaded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
