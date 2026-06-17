@php
    $contacts = array_values(array_filter((array) ($recordPayload['contacts'] ?? []), 'is_array'));
    $documents = array_values(array_filter((array) ($recordPayload['documents'] ?? []), 'is_array'));
    $primaryContact = $recordPayload['contact'] ?? data_get($contacts, '0.phone', data_get($contacts, '0.number'));
    $secondaryContact = $recordPayload['secondaryContact'] ?? data_get($contacts, '1.phone', data_get($contacts, '1.number'));
    $driverPhotoUrl = $photoUrl($recordPayload['photo'] ?? []);
    $driverStatus = $display($recordPayload['status'] ?? $record->status);
@endphp

<section class="record-profile-summary">
    <div class="record-profile-photo">
        @if($driverPhotoUrl !== '')
            <img src="{{ $driverPhotoUrl }}" alt="{{ $recordTitle }} photo">
        @else
            <span aria-hidden="true">🧑‍✈️</span>
        @endif
    </div>

    <div class="record-summary-main">
        <h2>{{ $display($recordPayload['fullName'] ?? $recordTitle) }}</h2>
        <p>Driver · {{ $display($recordPayload['duty'] ?? null) }} · {{ $display($recordPayload['vendor'] ?? null, 'Own Payroll') }}</p>
        <div class="record-tags">
            <span class="record-tag success">{{ $driverStatus }}</span>
            <span class="record-tag primary">{{ $display($recordPayload['driverId'] ?? $record->code) }}</span>
            <span class="record-tag warning">License Valid Until {{ $formatDate($recordPayload['licenseValidity'] ?? null) }}</span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Contact</label><div>{{ $display($primaryContact) }}</div></div>
        <div class="record-summary-stat"><label>License Type</label><div>{{ $display($recordPayload['licenseType'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Salary</label><div>{{ $formatMoney($recordPayload['salary'] ?? null, 2) }}</div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div>{{ $formatDateTime($record->updated_at) }}</div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">👤</div><div><h3>Personal Information</h3><p>Basic identity, family, contact and address details.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Full Name</th><td>{{ $display($recordPayload['fullName'] ?? $recordTitle) }}</td></tr>
            <tr><th>Age</th><td>{{ $display($recordPayload['age'] ?? null) }}</td></tr>
            <tr><th>Date of Birth</th><td>{{ $formatDate($recordPayload['dob'] ?? null, 'Y-m-d') }}</td></tr>
            <tr><th>NID</th><td>{{ $display($recordPayload['nid'] ?? null) }}</td></tr>
            <tr><th>Father Name</th><td>{{ $display($recordPayload['fatherName'] ?? null) }}</td></tr>
            <tr><th>Mother Name</th><td>{{ $display($recordPayload['motherName'] ?? null) }}</td></tr>
            <tr><th>Reference</th><td>{{ $display($recordPayload['reference'] ?? null) }}</td></tr>
            <tr><th>About</th><td>{{ $display($recordPayload['about'] ?? null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Contact Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Email</th><td>{{ $display($recordPayload['email'] ?? null) }}</td></tr>
            <tr><th>Primary Contact</th><td>{{ $display($primaryContact) }}</td></tr>
            <tr><th>WhatsApp</th><td>{{ $display($recordPayload['whatsapp'] ?? null) }}</td></tr>
            <tr><th>Secondary Contact</th><td>{{ $display($secondaryContact) }}</td></tr>
            @foreach($contacts as $index => $contact)
                <tr>
                    <th>Contact {{ $index + 1 }}</th>
                    <td>
                        Type: {{ $display($contact['type'] ?? null) }} ·
                        Phone: {{ $display($contact['phone'] ?? $contact['number'] ?? null) }} ·
                        Relationship: {{ $display($contact['relationship'] ?? null) }}
                    </td>
                </tr>
            @endforeach
        </tbody></table>

        <h4 class="record-sub-title">Address Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Present Address</th><td>{{ $display($recordPayload['presentAddress'] ?? null) }}</td></tr>
            <tr><th>Permanent Address</th><td>{{ $display($recordPayload['permanentAddress'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">🚘</div><div><h3>License & Duty Information</h3><p>Driving license, duty, working hour and operational assignment details.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">License Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Driver ID</th><td>{{ $display($recordPayload['driverId'] ?? $record->code) }}</td></tr>
            <tr><th>License No</th><td>{{ $display($recordPayload['licenseNo'] ?? null) }}</td></tr>
            <tr><th>License Type</th><td><span class="record-license-pill">{{ $display($recordPayload['licenseType'] ?? null) }}</span></td></tr>
            <tr><th>License Validity</th><td>{{ $formatDate($recordPayload['licenseValidity'] ?? null, 'Y-m-d') }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Duty Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Duty</th><td>{{ $display($recordPayload['duty'] ?? null) }}</td></tr>
            <tr><th>Working Hour</th><td>{{ $display($recordPayload['workingHour'] ?? null) }}</td></tr>
            <tr><th>Status</th><td><span class="record-status-pill">{{ $driverStatus }}</span></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">💼</div><div><h3>Payroll & Vendor Information</h3><p>Salary, overtime, vendor and payroll related details.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Payroll Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Vendor</th><td>{{ $display($recordPayload['vendor'] ?? null, 'Own Payroll') }}</td></tr>
            <tr><th>Salary</th><td>{{ $formatMoney($recordPayload['salary'] ?? null, 2) }}</td></tr>
            <tr><th>Salary Tenure</th><td>{{ $display($recordPayload['salaryTenure'] ?? null) }}</td></tr>
            <tr><th>Overtime Rate</th><td>{{ $display($recordPayload['otRate'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">🕘</div><div><h3>Record & Audit Information</h3><p>System record, creator and update history.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Record Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Record Code</th><td>{{ $record->code }}</td></tr>
            <tr><th>Record Name</th><td>{{ $record->name }}</td></tr>
            <tr><th>Record Status</th><td>{{ $display($record->status) }}</td></tr>
            <tr><th>Driver Validation Version</th><td>{{ $display($recordPayload['driverValidationVersion'] ?? null) }}</td></tr>
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
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">📎</div><div><h3>Documents</h3><p>Uploaded driver documents with expiry, reminder and file information.</p></div></div></div>
    <div class="record-table-section">
        <table class="record-file-table">
            <thead><tr><th>Document Name</th><th>Number</th><th>Expiry</th><th>Reminder</th><th>File</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($documents as $document)
                    @php($documentFile = is_array($document['file'] ?? null) ? $document['file'] : [])
                    @php($documentUrl = $fileUrl($documentFile))
                    <tr>
                        <td data-label="Document Name">{{ $display($document['name'] ?? null) }}</td>
                        <td data-label="Number">{{ $display($document['number'] ?? null) }}</td>
                        <td data-label="Expiry">{{ $formatDate($document['expiry'] ?? null, 'Y-m-d') }}</td>
                        <td data-label="Reminder">{{ $display($document['reminder'] ?? null) }}</td>
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
                    <tr><td colspan="6" class="record-empty-cell">No driver documents uploaded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
