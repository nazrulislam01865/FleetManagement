@php
    $number = static function ($value, ?int $decimals = null): string {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        $numeric = (float) $value;
        $precision = $decimals ?? (floor($numeric) === $numeric ? 0 : 2);

        return number_format($numeric, $precision, '.', ',');
    };

    $money = static function ($value): string {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '—';
        }

        $numeric = (float) $value;
        $precision = floor($numeric) === $numeric ? 0 : 2;

        return '৳'.number_format($numeric, $precision, '.', ',');
    };

    $status = $display($recordPayload['status'] ?? $record->status);
    $statusClass = strcasecmp($status, 'Draft') === 0 ? 'warning' : 'primary';
    $totalAmount = is_numeric($recordPayload['totalAmount'] ?? null)
        ? (float) $recordPayload['totalAmount']
        : (float) ($record->total_amount ?? 0);
    $totalKm = is_numeric($recordPayload['totalKm'] ?? null)
        ? (float) $recordPayload['totalKm']
        : (float) ($record->total_km ?? 0);

    $primaryFuelName = trim((string) ($recordPayload['primaryFuelName'] ?? ''));
    if ($primaryFuelName === '') {
        $primaryFuelName = trim((string) ($recordPayload['fuelType'] ?? ''));
    }

    $primaryEntryUnit = trim((string) ($recordPayload['primaryEntryUnit'] ?? $recordPayload['primaryFuelUnit'] ?? ''));
    $primaryPricingMode = trim((string) ($recordPayload['primaryPricingMode'] ?? ''));
    $primaryEnteredValue = $recordPayload['primaryEnteredValue'] ?? null;
    if ($primaryEnteredValue === null || $primaryEnteredValue === '') {
        $primaryEnteredValue = $recordPayload['primaryQty'] ?? null;
    }
    if (($primaryEnteredValue === null || $primaryEnteredValue === '') && in_array(strtolower($primaryEntryUnit), ['taka', 'tk'], true)) {
        $primaryEnteredValue = $recordPayload['primaryAmount'] ?? $recordPayload['gas'] ?? null;
    }
    if ($primaryEnteredValue === null || $primaryEnteredValue === '') {
        $primaryEnteredValue = $recordPayload['diesel'] ?? $recordPayload['octane'] ?? null;
    }

    $primaryStation = $recordPayload['primaryFuelStation']
        ?? $recordPayload['primaryStation']
        ?? $recordPayload['fuelStation']
        ?? null;
    $primaryAmount = is_numeric($recordPayload['primaryAmount'] ?? null)
        ? (float) $recordPayload['primaryAmount']
        : $totalAmount;

    $hasSecondaryFuel = (bool) ($recordPayload['hasSecondaryFuel'] ?? false)
        || filled($recordPayload['secondaryFuelName'] ?? null)
        || (float) ($recordPayload['secondaryAmount'] ?? 0) > 0;
    $secondaryEntryUnit = trim((string) ($recordPayload['secondaryEntryUnit'] ?? $recordPayload['secondaryFuelUnit'] ?? ''));
    $secondaryEnteredValue = $recordPayload['secondaryEnteredValue'] ?? $recordPayload['secondaryQty'] ?? null;
    if (($secondaryEnteredValue === null || $secondaryEnteredValue === '') && in_array(strtolower($secondaryEntryUnit), ['taka', 'tk'], true)) {
        $secondaryEnteredValue = $recordPayload['secondaryAmount'] ?? null;
    }

    $primarySummary = $number($primaryEnteredValue);
    if ($primarySummary !== '—' && $primaryEntryUnit !== '') {
        $primarySummary .= ' '.$primaryEntryUnit;
    }

    $vehicleLabel = $recordPayload['vehicleLabel'] ?? $recordPayload['vehicle'] ?? $recordPayload['car'] ?? $record->name;
    $vehicleId = $recordPayload['vehicleId'] ?? $record->vehicle_code ?? null;
    $contractLabel = $recordPayload['contractLabel'] ?? $recordPayload['contract'] ?? null;
    $contractId = $recordPayload['contractId'] ?? $record->contract_code ?? null;
    $driverName = $recordPayload['driverName'] ?? $recordPayload['driver'] ?? null;
    $driverId = $recordPayload['driverId'] ?? $record->driver_code ?? null;
    $rechargeDate = $recordPayload['date'] ?? $record->recharge_date ?? null;

    $photoDefinitions = [
        'vehicle' => 'Vehicle Photo',
        'fuel' => 'Fuel / Dispenser Photo',
        'odo' => 'ODO Meter Photo',
        'other' => 'Other Photo',
    ];
    $photos = is_array($recordPayload['photos'] ?? null) ? $recordPayload['photos'] : [];
@endphp

<section class="record-profile-summary record-fuel-recharge-summary">
    <div class="record-profile-photo record-fuel-icon" aria-hidden="true">⛽</div>

    <div class="record-summary-main">
        <h2>{{ $display($recordPayload['rechargeId'] ?? $record->code) }}</h2>
        <p>
            Vehicle: {{ $display($vehicleLabel) }}
            · Fuel: {{ $display($recordPayload['fuelType'] ?? $primaryFuelName) }}
            · Station: {{ $display($primaryStation) }}
        </p>
        <div class="record-tags">
            <span class="record-tag {{ $statusClass }}">{{ $status }}</span>
            <span class="record-tag success">{{ $money($totalAmount) }}</span>
            <span class="record-tag warning">{{ $formatDate($rechargeDate) }}</span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Total Amount</label><div>{{ $money($totalAmount) }}</div></div>
        <div class="record-summary-stat"><label>Primary Qty</label><div>{{ $primarySummary }}</div></div>
        <div class="record-summary-stat"><label>ODO Reading</label><div>{{ $number($recordPayload['odoReading'] ?? $recordPayload['endKm'] ?? null) }}</div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div>{{ $formatDateTime($record->updated_at) }}</div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-step-no">01</div><div><h3>Contract &amp; Vehicle Information</h3><p>Contract, vehicle and assigned driver details linked with this fuel recharge.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Contract Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Contract</th><td>{{ $display($contractLabel) }}</td></tr>
            <tr><th>Contract ID</th><td>{{ $display($contractId) }}</td></tr>
            <tr><th>Contract Label</th><td>{{ $display($contractLabel) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Vehicle Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Vehicle</th><td>{{ $display($vehicleLabel) }}</td></tr>
            <tr><th>Vehicle ID</th><td>{{ $display($vehicleId) }}</td></tr>
            <tr><th>Vehicle Label</th><td>{{ $display($vehicleLabel) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Assigned Driver</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Driver</th><td>{{ $display($driverName) }}</td></tr>
            <tr><th>Driver ID</th><td>{{ $display($driverId) }}</td></tr>
            <tr><th>Driver Name</th><td>{{ $display($driverName) }}</td></tr>
            <tr><th>Driver Shift</th><td>{{ $display($recordPayload['driverShift'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-step-no">02</div><div><h3>Photo Evidence</h3><p>Captured vehicle, fuel, ODO meter and other photo evidence.</p></div></div>
    </div>
    <div class="record-table-section">
        <div class="record-fuel-photo-grid">
            @foreach($photoDefinitions as $photoKey => $photoTitle)
                @php
                    $photo = is_array($photos[$photoKey] ?? null) ? $photos[$photoKey] : [];
                    $photoFile = is_array($photo['file'] ?? null) ? $photo['file'] : $photo;
                    $photoImageUrl = $photoUrl($photoFile);
                    $photoCaptured = $photoImageUrl !== '' || (bool) ($photo['captured'] ?? false);
                    $capturedAt = $photo['capturedAt'] ?? $photo['uploadedAt'] ?? $photoFile['uploadedAt'] ?? null;
                @endphp
                <article class="record-fuel-photo-card">
                    <div class="record-fuel-photo-title">{{ $photoTitle }}</div>
                    <table class="record-fuel-photo-table"><tbody>
                        <tr><th>Time</th><td>{{ $display($photo['time'] ?? null) }}</td></tr>
                        <tr><th>Place</th><td>{{ $display($photo['place'] ?? $photo['placeName'] ?? null) }}</td></tr>
                        <tr><th>Captured</th><td>{{ $photoCaptured ? 'Yes' : 'No' }}</td></tr>
                        <tr><th>Captured At</th><td>{{ $formatDateTime($capturedAt) }}</td></tr>
                    </tbody></table>
                    <div class="record-fuel-photo-preview">
                        @if($photoImageUrl !== '')
                            <a href="{{ $photoImageUrl }}" target="_blank" rel="noopener" class="record-fuel-photo-link" aria-label="Open {{ $photoTitle }}">
                                <img src="{{ $photoImageUrl }}" alt="{{ $photoTitle }}">
                            </a>
                            <div class="record-fuel-photo-meta">
                                <strong>{{ $fileDescription($photoFile) }}</strong>
                                <a href="{{ $photoImageUrl }}" target="_blank" rel="noopener" class="record-open-btn">Open Image</a>
                            </div>
                        @else
                            <div class="record-template-empty-state">No {{ strtolower($photoTitle) }} information saved.</div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-step-no">03</div><div><h3>Fuel Amount Information</h3><p>Main fuel, optional second fuel, quantity, rate and calculated amount.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Main Fuel</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Main Fuel Name</th><td>{{ $display($primaryFuelName) }}</td></tr>
            <tr><th>Primary Fuel Station</th><td>{{ $display($primaryStation) }}</td></tr>
            <tr><th>Quantity</th><td>{{ $primarySummary }}</td></tr>
            <tr><th>Rate Per Unit</th><td>{{ $number($recordPayload['primaryRate'] ?? null) }}</td></tr>
            <tr><th>Calculated Amount</th><td>{{ $money($primaryAmount) }}</td></tr>
            <tr><th>Pricing Mode</th><td>{{ $display($primaryPricingMode) }}</td></tr>
            <tr><th>Payment Mode</th><td>{{ $display($primaryEntryUnit) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Second Fuel</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Second Fuel Name</th><td>{{ $display($hasSecondaryFuel ? ($recordPayload['secondaryFuelName'] ?? null) : null) }}</td></tr>
            <tr><th>Secondary Fuel Station</th><td>{{ $display($hasSecondaryFuel ? ($recordPayload['secondaryFuelStation'] ?? $recordPayload['secondaryStation'] ?? null) : null) }}</td></tr>
            <tr><th>Quantity</th><td>{{ $hasSecondaryFuel ? $number($secondaryEnteredValue) . ($secondaryEntryUnit !== '' ? ' '.$secondaryEntryUnit : '') : '—' }}</td></tr>
            <tr><th>Rate Per Unit</th><td>{{ $hasSecondaryFuel ? $number($recordPayload['secondaryRate'] ?? null) : '—' }}</td></tr>
            <tr><th>Calculated Amount</th><td>{{ $hasSecondaryFuel ? $money($recordPayload['secondaryAmount'] ?? null) : '—' }}</td></tr>
            <tr><th>Pricing Mode</th><td>{{ $display($hasSecondaryFuel ? ($recordPayload['secondaryPricingMode'] ?? null) : null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Total Fuel Cost</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Total Fuel Cost</th><td>{{ $money($totalAmount) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-step-no">04</div><div><h3>ODO &amp; Submission Information</h3><p>Odometer reading, mileage, distance and submission details.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">ODO Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Start KM</th><td>{{ $number($recordPayload['startKm'] ?? null) }}</td></tr>
            <tr><th>End KM</th><td>{{ $number($recordPayload['endKm'] ?? null) }}</td></tr>
            <tr><th>Total KM</th><td>{{ $number($totalKm) }}</td></tr>
            <tr><th>Mileage</th><td>{{ $number($recordPayload['mileage'] ?? null) }}</td></tr>
            <tr><th>TK/KM</th><td>{{ $number($recordPayload['tkKm'] ?? null) }}</td></tr>
            <tr><th>ODO Reading</th><td>{{ $number($recordPayload['odoReading'] ?? $recordPayload['endKm'] ?? null) }}</td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Submission Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Status</th><td><span class="record-tag {{ $statusClass }}">{{ $status }}</span></td></tr>
            <tr><th>Submitted By</th><td>{{ $display($recordPayload['submittedBy'] ?? null) }}</td></tr>
            <tr><th>Remarks</th><td>{{ $display($recordPayload['remarks'] ?? null) }}</td></tr>
            <tr><th>Date</th><td>{{ $formatDate($rechargeDate, 'Y-m-d') }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🕘</div><div><h3>Record &amp; Audit Information</h3><p>System record, creator and update history.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Record Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Record Code</th><td>{{ $record->code }}</td></tr>
            <tr><th>Record Name</th><td>{{ $display($record->name) }}</td></tr>
            <tr><th>Record Status</th><td>{{ $display($record->status) }}</td></tr>
            <tr><th>Recharge Validation Version</th><td>{{ $display($recordPayload['rechargeValidationVersion'] ?? null) }}</td></tr>
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
