<?php
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
?>

<section class="record-profile-summary record-trip-summary">
    <div class="record-profile-photo record-trip-icon" aria-hidden="true">🧭</div>

    <div class="record-summary-main">
        <h2><?php echo e($tripTitle); ?></h2>
        <p><?php echo e($display($recordPayload['tripId'] ?? $record->code)); ?> · <?php echo e($display($recordPayload['details'] ?? null)); ?></p>
        <div class="record-tags">
            <span class="record-tag <?php echo e($savedClass); ?>"><?php echo e($savedAs); ?></span>
            <span class="record-tag <?php echo e($paymentClass); ?>"><?php echo e($paymentState); ?></span>
            <span class="record-tag success">Start: <?php echo e($formatDate($recordPayload['startDate'] ?? null)); ?></span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Total Cost</label><div><?php echo e($money($totalCost)); ?></div></div>
        <div class="record-summary-stat"><label>Paid Amount</label><div><?php echo e($money($paidAmount)); ?></div></div>
        <div class="record-summary-stat"><label>Balance Due</label><div><?php echo e($money($balanceDue)); ?></div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div><?php echo e($formatDateTime($record->updated_at)); ?></div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🧭</div><div><h3>Trip Information</h3><p>Main trip purpose, detail, status and date information.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Trip Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Trip ID</th><td><?php echo e($display($recordPayload['tripId'] ?? $record->code)); ?></td></tr>
            <tr><th>Trip Name</th><td><?php echo e($tripTitle); ?></td></tr>
            <tr><th>Purpose</th><td><?php echo e($display($recordPayload['purpose'] ?? null)); ?></td></tr>
            <tr><th>Details</th><td><?php echo e($display($recordPayload['details'] ?? null)); ?></td></tr>
            <tr><th>Saved As</th><td><?php echo e($savedAs); ?></td></tr>
            <tr><th>Start Date</th><td><?php echo e($formatDate($recordPayload['startDate'] ?? null, 'Y-m-d')); ?></td></tr>
            <tr><th>Payment State</th><td><span class="record-payment-pill <?php echo e($paymentClass); ?>"><?php echo e($paymentState); ?></span></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🚗</div><div><h3>Vehicle & Driver Information</h3><p>Assigned vehicle and driver information for this trip.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Vehicle</th><td><?php echo e($display($recordPayload['vehicle'] ?? null)); ?></td></tr>
            <tr><th>Vehicle ID</th><td><?php echo e($display($recordPayload['vehicleId'] ?? null)); ?></td></tr>
            <tr><th>Driver</th><td><?php echo e($display($recordPayload['driver'] ?? null)); ?></td></tr>
            <tr><th>Driver ID</th><td><?php echo e($display($recordPayload['driverId'] ?? null)); ?></td></tr>
            <tr><th>Client</th><td><?php echo e($display($recordPayload['client'] ?? null)); ?></td></tr>
            <tr><th>Client ID</th><td><?php echo e($display($recordPayload['clientId'] ?? null)); ?></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">📍</div><div><h3>Route & Odometer Information</h3><p>Trip route, location and odometer readings.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>From Location</th><td><?php echo e($display($recordPayload['fromLocation'] ?? null)); ?></td></tr>
            <tr><th>To Location</th><td><?php echo e($display($recordPayload['toLocation'] ?? null)); ?></td></tr>
            <tr><th>Odometer Start</th><td><?php echo e($display($recordPayload['odoStart'] ?? null)); ?></td></tr>
            <tr><th>Odometer End</th><td><?php echo e($display($recordPayload['odoEnd'] ?? null)); ?></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">💵</div><div><h3>Cost & Payment Information</h3><p>Trip cost, paid amount, balance due and payment state.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Total Cost</th><td><?php echo e($money($totalCost)); ?></td></tr>
            <tr><th>Paid Amount</th><td><?php echo e($money($paidAmount)); ?></td></tr>
            <tr><th>Balance Due</th><td><?php echo e($money($balanceDue)); ?></td></tr>
            <tr><th>Payment State</th><td><span class="record-payment-pill <?php echo e($paymentClass); ?>"><?php echo e($paymentState); ?></span></td></tr>
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
            <tr><th>Record Code</th><td><?php echo e($record->code); ?></td></tr>
            <tr><th>Record Name</th><td><?php echo e($display($record->name)); ?></td></tr>
            <tr><th>Record Status</th><td><?php echo e($savedAs); ?></td></tr>
            <tr><th>Trip Validation Version</th><td><?php echo e($display($recordPayload['tripValidationVersion'] ?? null)); ?></td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Audit Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Created At</th><td><?php echo e($formatDateTime($record->created_at)); ?></td></tr>
            <tr><th>Created By</th><td><?php echo e($display($recordCreatorName ?? null, 'System / Legacy')); ?></td></tr>
            <tr><th>Last Updated</th><td><?php echo e($formatDateTime($record->updated_at)); ?></td></tr>
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
                <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td data-label="Payment">Payment <?php echo e($index + 1); ?></td>
                        <td data-label="Amount"><?php echo e($money($payment['amount'] ?? 0)); ?></td>
                        <td data-label="Method"><?php echo e($display($payment['method'] ?? null)); ?></td>
                        <td data-label="Reference"><?php echo e($display($payment['reference'] ?? null)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="record-empty-cell">No payment records saved.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/record-details/trips.blade.php ENDPATH**/ ?>