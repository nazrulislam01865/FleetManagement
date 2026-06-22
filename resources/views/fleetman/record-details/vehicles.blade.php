@php
    $fuels = array_values(array_filter((array) ($recordPayload['fuels'] ?? []), 'is_array'));
    $documents = array_values(array_filter((array) ($recordPayload['docs'] ?? $recordPayload['documents'] ?? []), 'is_array'));
    $vehicleImage = is_array($recordPayload['image'] ?? null) ? $recordPayload['image'] : [];
    $vehicleImageUrl = $photoUrl($vehicleImage);
    $vehicleStatus = $display($recordPayload['status'] ?? $record->status);
    $usage = $display($recordPayload['usage'] ?? null);
    $rentalType = $display($recordPayload['rentalType'] ?? null);
    $isDoubleShift = strcasecmp(trim((string) ($recordPayload['usage'] ?? '')), 'Double shift') === 0;
    $hasSecondDriver = filled($recordPayload['secondDriver'] ?? null)
        || filled($recordPayload['secondDriverPaymentAmount'] ?? null)
        || filled($recordPayload['secondDriverPaymentCycle'] ?? null);

    $number = static function ($value, ?int $decimals = null): string {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        $numeric = (float) $value;
        $precision = $decimals ?? (floor($numeric) === $numeric ? 0 : 2);

        return number_format($numeric, $precision, '.', ',');
    };
@endphp

<section class="record-profile-summary record-vehicle-summary">
    <div class="record-profile-photo record-vehicle-icon" aria-hidden="true">🚗</div>

    <div class="record-summary-main">
        <h2>{{ $display($recordPayload['name'] ?? $recordTitle) }}</h2>
        <p>{{ $display($recordPayload['category'] ?? null) }} · {{ $display($recordPayload['subCategory'] ?? null) }} · {{ $usage }}</p>
        <div class="record-tags">
            <span class="record-tag success">{{ $vehicleStatus }}</span>
            <span class="record-tag primary">{{ $display($recordPayload['id'] ?? $record->code) }}</span>
            <span class="record-tag warning">{{ $rentalType }}</span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Registration No</label><div>{{ $display($recordPayload['regNo'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Model</label><div>{{ $display($recordPayload['model'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Payment Cycle</label><div>{{ $display($recordPayload['vehiclePaymentCycle'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div>{{ $formatDateTime($record->updated_at) }}</div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🚗</div><div><h3>Vehicle Information</h3><p>Vehicle identity, registration, model and operational status.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Vehicle Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Vehicle ID</th><td>{{ $display($recordPayload['id'] ?? $record->code) }}</td></tr>
            <tr><th>Vehicle Name</th><td>{{ $display($recordPayload['name'] ?? $recordTitle) }}</td></tr>
            <tr><th>Registration No</th><td>{{ $display($recordPayload['regNo'] ?? null) }}</td></tr>
            <tr><th>Model</th><td>{{ $display($recordPayload['model'] ?? null) }}</td></tr>
            <tr><th>Color</th><td>{{ $display($recordPayload['color'] ?? null) }}</td></tr>
            <tr><th>Engine No</th><td>{{ $display($recordPayload['engineNo'] ?? null) }}</td></tr>
            <tr><th>Odometer</th><td>{{ $number($recordPayload['odo'] ?? null) }}</td></tr>
            <tr><th>Mileage</th><td>{{ $number($recordPayload['mileage'] ?? null) }}</td></tr>
            <tr><th>Notes</th><td>{{ $display($recordPayload['notes'] ?? null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Category & Usage</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Category</th><td>{{ $display($recordPayload['category'] ?? null) }}</td></tr>
            <tr><th>Sub Category</th><td>{{ $display($recordPayload['subCategory'] ?? null) }}</td></tr>
            <tr><th>Usage</th><td>{{ $usage }}</td></tr>
            <tr><th>Status</th><td><span class="record-status-pill">{{ $vehicleStatus }}</span></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">👥</div><div><h3>Driver & Vendor Information</h3><p>Assigned driver, vendor and rental type details.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Rental Type</th><td>{{ $rentalType }}</td></tr>
            <tr><th>{{ $isDoubleShift || $hasSecondDriver ? 'Driver 1' : 'Driver' }}</th><td>{{ $display($recordPayload['driver'] ?? null) }}</td></tr>
            @if($isDoubleShift || $hasSecondDriver)
                <tr><th>Driver 2</th><td>{{ $display($recordPayload['secondDriver'] ?? null) }}</td></tr>
            @endif
            <tr><th>Vendor</th><td>{{ $display($recordPayload['vendor'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">💵</div><div><h3>Rental & Payment Information</h3><p>Vehicle rental amount, driver payment and payment cycle information.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Rent</th><td>{{ $number($recordPayload['rent'] ?? $recordPayload['totalRentalAmount'] ?? null) }}</td></tr>
            <tr><th>Total Rental Amount</th><td>{{ $number($recordPayload['totalRentalAmount'] ?? $recordPayload['rent'] ?? null) }}</td></tr>
            <tr><th>Vehicle Rental Amount</th><td>{{ $number($recordPayload['vehicleRentalAmount'] ?? null) }}</td></tr>
            <tr><th>Vehicle Payment Cycle</th><td>{{ $display($recordPayload['vehiclePaymentCycle'] ?? null) }}</td></tr>
            <tr><th>{{ $isDoubleShift || $hasSecondDriver ? 'Driver 1 Payment Amount' : 'Driver Payment Amount' }}</th><td>{{ $number($recordPayload['driverPaymentAmount'] ?? null) }}</td></tr>
            <tr><th>{{ $isDoubleShift || $hasSecondDriver ? 'Driver 1 Payment Cycle' : 'Driver Payment Cycle' }}</th><td>{{ $display($recordPayload['driverPaymentCycle'] ?? null) }}</td></tr>
            @if($isDoubleShift || $hasSecondDriver)
                <tr><th>Driver 2 Payment Amount</th><td>{{ $number($recordPayload['secondDriverPaymentAmount'] ?? null) }}</td></tr>
                <tr><th>Driver 2 Payment Cycle</th><td>{{ $display($recordPayload['secondDriverPaymentCycle'] ?? null) }}</td></tr>
            @endif
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">⛽</div><div><h3>Fuel Information</h3><p>Primary and secondary fuel setup for this vehicle.</p></div></div>
    </div>
    <div class="record-table-section">
        @forelse($fuels as $index => $fuel)
            <div class="record-assignment-card record-fuel-card">
                <div class="record-assignment-title">Fuel {{ $index + 1 }}</div>
                <table class="record-info-table"><tbody>
                    <tr><th>Fuel Type</th><td>{{ $display($fuel['type'] ?? null) }}</td></tr>
                    <tr><th>Rate</th><td>{{ $number($fuel['rate'] ?? null) }}</td></tr>
                    <tr><th>Priority</th><td>{{ $display($fuel['priority'] ?? null) }}</td></tr>
                </tbody></table>
            </div>
        @empty
            <p class="record-empty-message">No fuel information saved.</p>
        @endforelse
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🕘</div><div><h3>Record & Audit Information</h3><p>System record, creator and update history.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Record Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Record Code</th><td>{{ $record->code }}</td></tr>
            <tr><th>Record Name</th><td>{{ $display($record->name) }}</td></tr>
            <tr><th>Record Status</th><td>{{ $display($record->status) }}</td></tr>
            <tr><th>Vehicle Validation Version</th><td>{{ $display($recordPayload['vehicleValidationVersion'] ?? null) }}</td></tr>
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
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">📎</div><div><h3>Documents</h3><p>Uploaded vehicle documents with expiry, reminder and file information.</p></div></div>
    </div>
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
                    <tr><td colspan="5" class="record-empty-cell">No vehicle documents uploaded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🖼️</div><div><h3>Vehicle Image</h3><p>Vehicle image or related uploaded media.</p></div></div>
    </div>
    <div class="record-table-section">
        @if($vehicleImageUrl !== '')
            <div class="record-vehicle-image-panel">
                <a href="{{ $vehicleImageUrl }}" target="_blank" rel="noopener">
                    <img src="{{ $vehicleImageUrl }}" alt="{{ $display($recordPayload['name'] ?? $recordTitle) }} image">
                </a>
                <div>
                    <strong>{{ $fileDescription($vehicleImage) }}</strong>
                    <a href="{{ $vehicleImageUrl }}" target="_blank" rel="noopener" class="record-open-btn">Open Image</a>
                </div>
            </div>
        @else
            <div class="record-template-empty-state">No image information saved.</div>
        @endif
    </div>
</section>
