@php
    $assignments = array_values(array_filter((array) ($recordPayload['assignments'] ?? []), 'is_array'));
    $documents = array_values(array_filter((array) ($recordPayload['documents'] ?? []), 'is_array'));
    $contractName = $recordPayload['contractName'] ?? $recordPayload['partyName'] ?? $recordTitle;
    $contractStatus = $display($recordPayload['status'] ?? null);
@endphp

<section class="record-contract-summary">
    <div class="record-summary-main">
        <h2>{{ $display($contractName) }}</h2>
        <p>Contract No: {{ $display($recordPayload['contractId'] ?? $record->code) }} · {{ $display($recordPayload['contractWith'] ?? null) }} Contract · {{ $display($recordPayload['details'] ?? null) }}</p>
        <div class="record-tags">
            <span class="record-tag success">{{ $contractStatus }}</span>
            <span class="record-tag primary">{{ $display($recordPayload['savedAs'] ?? $record->status) }}</span>
            <span class="record-tag warning">Ends {{ $formatDate($recordPayload['contractEnd'] ?? null) }}</span>
        </div>
    </div>

    <div class="record-summary-stats contract-stats">
        <div class="record-summary-stat"><label>Contract Amount</label><div>{{ $formatMoney($recordPayload['amount'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Party</label><div>{{ $display($recordPayload['partyName'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Start Date</label><div>{{ $formatDate($recordPayload['contractStart'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div>{{ $formatDateTime($record->updated_at) }}</div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">📄</div><div><h3>Contract Information</h3><p>Main contract amount, status, dates and business details.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Contract Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Contract ID</th><td>{{ $display($recordPayload['contractId'] ?? $record->code) }}</td></tr>
            <tr><th>Contract Name</th><td>{{ $display($contractName) }}</td></tr>
            <tr><th>Contract With</th><td>{{ $display($recordPayload['contractWith'] ?? null) }}</td></tr>
            <tr><th>Amount</th><td>{{ $formatMoney($recordPayload['amount'] ?? null) }}</td></tr>
            <tr><th>Status</th><td><span class="record-status-pill">{{ $contractStatus }}</span></td></tr>
            <tr><th>Details</th><td>{{ $display($recordPayload['details'] ?? null) }}</td></tr>
            <tr><th>Contract Start</th><td>{{ $formatDate($recordPayload['contractStart'] ?? null, 'Y-m-d') }}</td></tr>
            <tr><th>Contract End</th><td>{{ $formatDate($recordPayload['contractEnd'] ?? null, 'Y-m-d') }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Save & Submission Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Saved As</th><td>{{ $display($recordPayload['savedAs'] ?? $record->status) }}</td></tr>
            <tr><th>Saved At</th><td>{{ $display($recordPayload['savedAt'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">👥</div><div><h3>Party Information</h3><p>Client or party linked with this contract.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Party Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Party ID</th><td>{{ $display($recordPayload['partyId'] ?? null) }}</td></tr>
            <tr><th>Party Name</th><td>{{ $display($recordPayload['partyName'] ?? null) }}</td></tr>
            <tr><th>Contract With</th><td>{{ $display($recordPayload['contractWith'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">🚗</div><div><h3>Assignments</h3><p>Assigned vehicles and drivers under this contract.</p></div></div></div>
    <div class="record-table-section">
        <div class="record-assignment-grid">
            @forelse($assignments as $index => $assignment)
                @php
                    $assignmentDrivers = array_values(array_filter((array) ($assignment['drivers'] ?? []), 'is_array'));
                    if ($assignmentDrivers === []) {
                        $assignmentDrivers[] = [
                            'driver' => $assignment['driver'] ?? null,
                            'driverId' => $assignment['driverId'] ?? null,
                            'driverName' => $assignment['driverName'] ?? null,
                            'shift' => $assignment['shift'] ?? null,
                            'shiftId' => $assignment['shiftId'] ?? null,
                        ];
                    }
                    if (!empty($assignment['secondDriverId']) || !empty($assignment['secondDriver'])) {
                        $assignmentDrivers[] = [
                            'driver' => $assignment['secondDriver'] ?? null,
                            'driverId' => $assignment['secondDriverId'] ?? null,
                            'driverName' => $assignment['secondDriverName'] ?? null,
                            'shift' => $assignment['secondShift'] ?? null,
                            'shiftId' => $assignment['secondShiftId'] ?? null,
                        ];
                    }
                @endphp
                <div class="record-assignment-card">
                    <div class="record-assignment-title">Assignment {{ $index + 1 }}</div>
                    <table class="record-info-table"><tbody>
                        <tr><th>Shift Type</th><td>{{ $display($assignment['shiftType'] ?? 'Single') }}</td></tr>
                        <tr><th>Duty</th><td>{{ $display($assignment['duty'] ?? null) }}</td></tr>
                        <tr><th>Rate</th><td>{{ $display($assignment['rate'] ?? null) }}</td></tr>
                        <tr><th>Vehicle</th><td>{{ $display($assignment['vehicle'] ?? null) }}</td></tr>
                        <tr><th>Vehicle ID</th><td>{{ $display($assignment['vehicleId'] ?? null) }}</td></tr>
                        <tr><th>Vehicle Name</th><td>{{ $display($assignment['vehicleName'] ?? null) }}</td></tr>
                        @foreach($assignmentDrivers as $driverIndex => $driverAssignment)
                            <tr><th>Driver {{ $driverIndex + 1 }}</th><td>{{ $display($driverAssignment['driver'] ?? $driverAssignment['driverName'] ?? null) }}</td></tr>
                            <tr><th>Driver {{ $driverIndex + 1 }} ID</th><td>{{ $display($driverAssignment['driverId'] ?? null) }}</td></tr>
                            @if(!empty($driverAssignment['shift']) || !empty($driverAssignment['shiftName']) || !empty($driverAssignment['shiftId']))
                                <tr><th>Driver {{ $driverIndex + 1 }} Shift</th><td>{{ $display($driverAssignment['shift'] ?? $driverAssignment['shiftName'] ?? $driverAssignment['shiftId'] ?? null) }}</td></tr>
                            @endif
                        @endforeach
                    </tbody></table>
                </div>
            @empty
                <p class="record-empty-message">No assignments saved for this contract.</p>
            @endforelse
        </div>
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
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">📎</div><div><h3>Documents</h3><p>Uploaded contract documents with expiry, reminder and file information.</p></div></div></div>
    <div class="record-table-section">
        <table class="record-file-table">
            <thead><tr><th>Document Name</th><th>Expiry</th><th>Reminder</th><th>File</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($documents as $document)
                    @php($documentFile = is_array($document['file'] ?? null) ? $document['file'] : [])
                    @php($documentUrl = $fileUrl($documentFile))
                    <tr>
                        <td data-label="Document Name">{{ $display($document['name'] ?? null) }}</td>
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
                    <tr><td colspan="5" class="record-empty-cell">No contract documents uploaded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
