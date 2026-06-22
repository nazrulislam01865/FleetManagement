@php
    $payments = array_values(array_filter((array) ($recordPayload['payments'] ?? []), 'is_array'));
    $totalCost = is_numeric($recordPayload['totalCost'] ?? null)
        ? (float) $recordPayload['totalCost']
        : collect(['fuelCost', 'foodCost', 'tolls', 'otherCost', 'accommodationCost'])
            ->sum(fn (string $key): float => (float) ($recordPayload[$key] ?? 0));
    $calculatedPaid = collect($payments)->sum(fn (array $payment): float => (float) ($payment['amount'] ?? 0));
    $paidAmount = is_numeric($recordPayload['paidAmount'] ?? null)
        ? (float) $recordPayload['paidAmount']
        : (float) $calculatedPaid;
    $balanceDue = is_numeric($recordPayload['balanceDue'] ?? null)
        ? max(0, (float) $recordPayload['balanceDue'])
        : max(0, $totalCost - $paidAmount);
    $paymentState = trim((string) ($recordPayload['paymentState'] ?? ''));
    if ($paymentState === '') {
        $paymentState = $balanceDue <= 0.009 ? 'Paid' : ($paidAmount > 0 ? 'Partially Paid' : 'Unpaid');
    }

    $savedAs = $display($recordPayload['savedAs'] ?? $recordPayload['status'] ?? $record->status);
    $tripTitle = $display($recordPayload['tripName'] ?? $recordPayload['name'] ?? $recordPayload['purpose'] ?? $recordPayload['tripId'] ?? $recordTitle);
    $paymentClass = match (strtolower($paymentState)) {
        'paid' => 'success',
        'unpaid' => 'danger',
        default => 'warning',
    };
    $savedClass = strcasecmp($savedAs, 'Draft') === 0 ? 'warning' : 'primary';
    $money = static fn ($value): string => '৳'.number_format((float) ($value ?? 0), 2, '.', ',');
@endphp

<section class="record-profile-summary record-trip-summary">
    <div class="record-profile-photo record-trip-icon" aria-hidden="true">🧭</div>

    <div class="record-summary-main">
        <h2>{{ $tripTitle }}</h2>
        <p>{{ $display($recordPayload['tripId'] ?? $record->code) }} · {{ $display($recordPayload['details'] ?? null) }}</p>
        <div class="record-tags">
            <span class="record-tag {{ $savedClass }}">{{ $savedAs }}</span>
            <span class="record-tag {{ $paymentClass }}">{{ $paymentState }}</span>
            <span class="record-tag success">Start: {{ $formatDate($recordPayload['startDate'] ?? null) }}</span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Total Cost</label><div>{{ $money($totalCost) }}</div></div>
        <div class="record-summary-stat"><label>Paid Amount</label><div>{{ $money($paidAmount) }}</div></div>
        <div class="record-summary-stat"><label>Balance Due</label><div>{{ $money($balanceDue) }}</div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div>{{ $formatDateTime($record->updated_at) }}</div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🧭</div><div><h3>Trip Information</h3><p>Main trip purpose, detail, status and date information.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Trip Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Trip ID</th><td>{{ $display($recordPayload['tripId'] ?? $record->code) }}</td></tr>
            <tr><th>Trip Name</th><td>{{ $tripTitle }}</td></tr>
            <tr><th>Purpose</th><td>{{ $display($recordPayload['purpose'] ?? null) }}</td></tr>
            <tr><th>Details</th><td>{{ $display($recordPayload['details'] ?? null) }}</td></tr>
            <tr><th>Saved As</th><td>{{ $savedAs }}</td></tr>
            <tr><th>Start Date</th><td>{{ $formatDate($recordPayload['startDate'] ?? null, 'Y-m-d') }}</td></tr>
            <tr><th>Payment State</th><td><span class="record-payment-pill {{ $paymentClass }}">{{ $paymentState }}</span></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🚗</div><div><h3>Vehicle & Driver Information</h3><p>Assigned vehicle and driver information for this trip.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Vehicle</th><td>{{ $display($recordPayload['vehicle'] ?? null) }}</td></tr>
            <tr><th>Vehicle ID</th><td>{{ $display($recordPayload['vehicleId'] ?? null) }}</td></tr>
            <tr><th>Driver</th><td>{{ $display($recordPayload['driver'] ?? null) }}</td></tr>
            <tr><th>Driver ID</th><td>{{ $display($recordPayload['driverId'] ?? null) }}</td></tr>
            <tr><th>Client</th><td>{{ $display($recordPayload['client'] ?? null) }}</td></tr>
            <tr><th>Client ID</th><td>{{ $display($recordPayload['clientId'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">📍</div><div><h3>Route & Odometer Information</h3><p>Trip route, location and odometer readings.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>From Location</th><td>{{ $display($recordPayload['fromLocation'] ?? null) }}</td></tr>
            <tr><th>To Location</th><td>{{ $display($recordPayload['toLocation'] ?? null) }}</td></tr>
            <tr><th>Odometer Start</th><td>{{ $display($recordPayload['odoStart'] ?? null) }}</td></tr>
            <tr><th>Odometer End</th><td>{{ $display($recordPayload['odoEnd'] ?? null) }}</td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">💵</div><div><h3>Cost & Payment Information</h3><p>Trip cost, paid amount, balance due and payment state.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Total Cost</th><td>{{ $money($totalCost) }}</td></tr>
            <tr><th>Paid Amount</th><td>{{ $money($paidAmount) }}</td></tr>
            <tr><th>Balance Due</th><td>{{ $money($balanceDue) }}</td></tr>
            <tr><th>Payment State</th><td><span class="record-payment-pill {{ $paymentClass }}">{{ $paymentState }}</span></td></tr>
        </tbody></table>
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
            <tr><th>Record Status</th><td>{{ $savedAs }}</td></tr>
            <tr><th>Trip Validation Version</th><td>{{ $display($recordPayload['tripValidationVersion'] ?? null) }}</td></tr>
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
        <div class="record-card-header-left"><div class="record-card-icon">💳</div><div><h3>Payments</h3><p>Payment records captured against this trip.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-file-table">
            <thead><tr><th>Payment</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
            <tbody>
                @forelse($payments as $index => $payment)
                    <tr>
                        <td data-label="Payment">Payment {{ $index + 1 }}</td>
                        <td data-label="Amount">{{ $money($payment['amount'] ?? 0) }}</td>
                        <td data-label="Method">{{ $display($payment['method'] ?? null) }}</td>
                        <td data-label="Reference">{{ $display($payment['reference'] ?? null) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="record-empty-cell">No payment records saved.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
