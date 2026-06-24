<?php
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
?>

<section class="record-profile-summary record-vehicle-summary">
    <div class="record-profile-photo record-vehicle-icon" aria-hidden="true">🚗</div>

    <div class="record-summary-main">
        <h2><?php echo e($display($recordPayload['name'] ?? $recordTitle)); ?></h2>
        <p><?php echo e($display($recordPayload['category'] ?? null)); ?> · <?php echo e($display($recordPayload['subCategory'] ?? null)); ?> · <?php echo e($usage); ?></p>
        <div class="record-tags">
            <span class="record-tag success"><?php echo e($vehicleStatus); ?></span>
            <span class="record-tag primary"><?php echo e($display($recordPayload['id'] ?? $record->code)); ?></span>
            <span class="record-tag warning"><?php echo e($rentalType); ?></span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Registration No</label><div><?php echo e($display($recordPayload['regNo'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Model</label><div><?php echo e($display($recordPayload['model'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Payment Cycle</label><div><?php echo e($display($recordPayload['vehiclePaymentCycle'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div><?php echo e($formatDateTime($record->updated_at)); ?></div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🚗</div><div><h3>Vehicle Information</h3><p>Vehicle identity, registration, model and operational status.</p></div></div>
    </div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Vehicle Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Vehicle ID</th><td><?php echo e($display($recordPayload['id'] ?? $record->code)); ?></td></tr>
            <tr><th>Vehicle Name</th><td><?php echo e($display($recordPayload['name'] ?? $recordTitle)); ?></td></tr>
            <tr><th>Registration No</th><td><?php echo e($display($recordPayload['regNo'] ?? null)); ?></td></tr>
            <tr><th>Model</th><td><?php echo e($display($recordPayload['model'] ?? null)); ?></td></tr>
            <tr><th>Color</th><td><?php echo e($display($recordPayload['color'] ?? null)); ?></td></tr>
            <tr><th>Engine No</th><td><?php echo e($display($recordPayload['engineNo'] ?? null)); ?></td></tr>
            <tr><th>Odometer</th><td><?php echo e($number($recordPayload['odo'] ?? null)); ?></td></tr>
            <tr><th>Mileage</th><td><?php echo e($number($recordPayload['mileage'] ?? null)); ?></td></tr>
            <tr><th>Notes</th><td><?php echo e($display($recordPayload['notes'] ?? null)); ?></td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Category & Usage</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Category</th><td><?php echo e($display($recordPayload['category'] ?? null)); ?></td></tr>
            <tr><th>Sub Category</th><td><?php echo e($display($recordPayload['subCategory'] ?? null)); ?></td></tr>
            <tr><th>Usage</th><td><?php echo e($usage); ?></td></tr>
            <tr><th>Status</th><td><span class="record-status-pill"><?php echo e($vehicleStatus); ?></span></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">👥</div><div><h3>Driver & Vendor Information</h3><p>Assigned driver, vendor and rental type details.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Rental Type</th><td><?php echo e($rentalType); ?></td></tr>
            <tr><th><?php echo e($isDoubleShift || $hasSecondDriver ? 'Driver 1' : 'Driver'); ?></th><td><?php echo e($display($recordPayload['driver'] ?? null)); ?></td></tr>
            <?php if($isDoubleShift || $hasSecondDriver): ?>
                <tr><th>Driver 2</th><td><?php echo e($display($recordPayload['secondDriver'] ?? null)); ?></td></tr>
            <?php endif; ?>
            <tr><th>Vendor</th><td><?php echo e($display($recordPayload['vendor'] ?? null)); ?></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">💵</div><div><h3>Rental & Payment Information</h3><p>Vehicle rental amount, driver payment and payment cycle information.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-info-table"><tbody>
            <tr><th>Rent</th><td><?php echo e($number($recordPayload['rent'] ?? $recordPayload['totalRentalAmount'] ?? null)); ?></td></tr>
            <tr><th>Total Rental Amount</th><td><?php echo e($number($recordPayload['totalRentalAmount'] ?? $recordPayload['rent'] ?? null)); ?></td></tr>
            <tr><th>Vehicle Rental Amount</th><td><?php echo e($number($recordPayload['vehicleRentalAmount'] ?? null)); ?></td></tr>
            <tr><th>Vehicle Payment Cycle</th><td><?php echo e($display($recordPayload['vehiclePaymentCycle'] ?? null)); ?></td></tr>
            <tr><th><?php echo e($isDoubleShift || $hasSecondDriver ? 'Driver 1 Payment Amount' : 'Driver Payment Amount'); ?></th><td><?php echo e($number($recordPayload['driverPaymentAmount'] ?? null)); ?></td></tr>
            <tr><th><?php echo e($isDoubleShift || $hasSecondDriver ? 'Driver 1 Payment Cycle' : 'Driver Payment Cycle'); ?></th><td><?php echo e($display($recordPayload['driverPaymentCycle'] ?? null)); ?></td></tr>
            <?php if($isDoubleShift || $hasSecondDriver): ?>
                <tr><th>Driver 2 Payment Amount</th><td><?php echo e($number($recordPayload['secondDriverPaymentAmount'] ?? null)); ?></td></tr>
                <tr><th>Driver 2 Payment Cycle</th><td><?php echo e($display($recordPayload['secondDriverPaymentCycle'] ?? null)); ?></td></tr>
            <?php endif; ?>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">⛽</div><div><h3>Fuel Information</h3><p>Primary and secondary fuel setup for this vehicle.</p></div></div>
    </div>
    <div class="record-table-section">
        <?php $__empty_1 = true; $__currentLoopData = $fuels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $fuel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="record-assignment-card record-fuel-card">
                <div class="record-assignment-title">Fuel <?php echo e($index + 1); ?></div>
                <table class="record-info-table"><tbody>
                    <tr><th>Fuel Type</th><td><?php echo e($display($fuel['type'] ?? null)); ?></td></tr>
                    <tr><th>Rate</th><td><?php echo e($number($fuel['rate'] ?? null)); ?></td></tr>
                    <tr><th>Priority</th><td><?php echo e($display($fuel['priority'] ?? null)); ?></td></tr>
                </tbody></table>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="record-empty-message">No fuel information saved.</p>
        <?php endif; ?>
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
            <tr><th>Record Status</th><td><?php echo e($display($record->status)); ?></td></tr>
            <tr><th>Vehicle Validation Version</th><td><?php echo e($display($recordPayload['vehicleValidationVersion'] ?? null)); ?></td></tr>
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
        <div class="record-card-header-left"><div class="record-card-icon">📎</div><div><h3>Documents</h3><p>Uploaded vehicle documents with expiry, reminder and file information.</p></div></div>
    </div>
    <div class="record-table-section">
        <table class="record-file-table">
            <thead><tr><th>Document Name</th><th>Expiry</th><th>Reminder</th><th>File</th><th>Action</th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($documentFile = is_array($document['file'] ?? null) ? $document['file'] : []); ?>
                    <?php ($documentUrl = $fileUrl($documentFile)); ?>
                    <tr>
                        <td data-label="Document Name"><?php echo e($display($document['name'] ?? null)); ?></td>
                        <td data-label="Expiry"><?php echo e($formatDate($document['expiry'] ?? null, 'Y-m-d')); ?></td>
                        <td data-label="Reminder"><?php echo e($display($document['reminder'] ?? null)); ?></td>
                        <td data-label="File"><?php echo e($fileDescription($documentFile)); ?></td>
                        <td data-label="Action">
                            <?php if($documentUrl !== ''): ?>
                                <a href="<?php echo e($documentUrl); ?>" target="_blank" rel="noopener" class="record-open-btn">Open</a>
                            <?php else: ?>
                                <span class="record-file-unavailable">No file</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="record-empty-cell">No vehicle documents uploaded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header">
        <div class="record-card-header-left"><div class="record-card-icon">🖼️</div><div><h3>Vehicle Image</h3><p>Vehicle image or related uploaded media.</p></div></div>
    </div>
    <div class="record-table-section">
        <?php if($vehicleImageUrl !== ''): ?>
            <div class="record-vehicle-image-panel">
                <a href="<?php echo e($vehicleImageUrl); ?>" target="_blank" rel="noopener">
                    <img src="<?php echo e($vehicleImageUrl); ?>" alt="<?php echo e($display($recordPayload['name'] ?? $recordTitle)); ?> image">
                </a>
                <div>
                    <strong><?php echo e($fileDescription($vehicleImage)); ?></strong>
                    <a href="<?php echo e($vehicleImageUrl); ?>" target="_blank" rel="noopener" class="record-open-btn">Open Image</a>
                </div>
            </div>
        <?php else: ?>
            <div class="record-template-empty-state">No image information saved.</div>
        <?php endif; ?>
    </div>
</section>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/record-details/vehicles.blade.php ENDPATH**/ ?>