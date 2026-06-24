<?php
    $assignments = array_values(array_filter((array) ($recordPayload['assignments'] ?? []), 'is_array'));
    $documents = array_values(array_filter((array) ($recordPayload['documents'] ?? []), 'is_array'));
    $contractName = $recordPayload['contractName'] ?? $recordPayload['partyName'] ?? $recordTitle;
    $contractStatus = $display($recordPayload['status'] ?? null);
?>

<section class="record-contract-summary">
    <div class="record-summary-main">
        <h2><?php echo e($display($contractName)); ?></h2>
        <p>Contract No: <?php echo e($display($recordPayload['contractId'] ?? $record->code)); ?> · <?php echo e($display($recordPayload['contractWith'] ?? null)); ?> Contract · <?php echo e($display($recordPayload['details'] ?? null)); ?></p>
        <div class="record-tags">
            <span class="record-tag success"><?php echo e($contractStatus); ?></span>
            <span class="record-tag primary"><?php echo e($display($recordPayload['savedAs'] ?? $record->status)); ?></span>
            <span class="record-tag warning">Ends <?php echo e($formatDate($recordPayload['contractEnd'] ?? null)); ?></span>
        </div>
    </div>

    <div class="record-summary-stats contract-stats">
        <div class="record-summary-stat"><label>Contract Amount</label><div><?php echo e($formatMoney($recordPayload['amount'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Party</label><div><?php echo e($display($recordPayload['partyName'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Start Date</label><div><?php echo e($formatDate($recordPayload['contractStart'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div><?php echo e($formatDateTime($record->updated_at)); ?></div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">📄</div><div><h3>Contract Information</h3><p>Main contract amount, status, dates and business details.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Contract Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Contract ID</th><td><?php echo e($display($recordPayload['contractId'] ?? $record->code)); ?></td></tr>
            <tr><th>Contract Name</th><td><?php echo e($display($contractName)); ?></td></tr>
            <tr><th>Contract With</th><td><?php echo e($display($recordPayload['contractWith'] ?? null)); ?></td></tr>
            <tr><th>Amount</th><td><?php echo e($formatMoney($recordPayload['amount'] ?? null)); ?></td></tr>
            <tr><th>Status</th><td><span class="record-status-pill"><?php echo e($contractStatus); ?></span></td></tr>
            <tr><th>Details</th><td><?php echo e($display($recordPayload['details'] ?? null)); ?></td></tr>
            <tr><th>Contract Start</th><td><?php echo e($formatDate($recordPayload['contractStart'] ?? null, 'Y-m-d')); ?></td></tr>
            <tr><th>Contract End</th><td><?php echo e($formatDate($recordPayload['contractEnd'] ?? null, 'Y-m-d')); ?></td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Save & Submission Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Saved As</th><td><?php echo e($display($recordPayload['savedAs'] ?? $record->status)); ?></td></tr>
            <tr><th>Saved At</th><td><?php echo e($display($recordPayload['savedAt'] ?? null)); ?></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">👥</div><div><h3>Party Information</h3><p>Client or party linked with this contract.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Party Details</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Party ID</th><td><?php echo e($display($recordPayload['partyId'] ?? null)); ?></td></tr>
            <tr><th>Party Name</th><td><?php echo e($display($recordPayload['partyName'] ?? null)); ?></td></tr>
            <tr><th>Contract With</th><td><?php echo e($display($recordPayload['contractWith'] ?? null)); ?></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">🚗</div><div><h3>Assignments</h3><p>Assigned vehicles and drivers under this contract.</p></div></div></div>
    <div class="record-table-section">
        <div class="record-assignment-grid">
            <?php $__empty_1 = true; $__currentLoopData = $assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
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
                ?>
                <div class="record-assignment-card">
                    <div class="record-assignment-title">Assignment <?php echo e($index + 1); ?></div>
                    <table class="record-info-table"><tbody>
                        <tr><th>Shift Type</th><td><?php echo e($display($assignment['shiftType'] ?? 'Single')); ?></td></tr>
                        <tr><th>Duty</th><td><?php echo e($display($assignment['duty'] ?? null)); ?></td></tr>
                        <tr><th>Rate</th><td><?php echo e($display($assignment['rate'] ?? null)); ?></td></tr>
                        <tr><th>Vehicle</th><td><?php echo e($display($assignment['vehicle'] ?? null)); ?></td></tr>
                        <tr><th>Vehicle ID</th><td><?php echo e($display($assignment['vehicleId'] ?? null)); ?></td></tr>
                        <tr><th>Vehicle Name</th><td><?php echo e($display($assignment['vehicleName'] ?? null)); ?></td></tr>
                        <?php $__currentLoopData = $assignmentDrivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driverIndex => $driverAssignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr><th>Driver <?php echo e($driverIndex + 1); ?></th><td><?php echo e($display($driverAssignment['driver'] ?? $driverAssignment['driverName'] ?? null)); ?></td></tr>
                            <tr><th>Driver <?php echo e($driverIndex + 1); ?> ID</th><td><?php echo e($display($driverAssignment['driverId'] ?? null)); ?></td></tr>
                            <?php if(!empty($driverAssignment['shift']) || !empty($driverAssignment['shiftName']) || !empty($driverAssignment['shiftId'])): ?>
                                <tr><th>Driver <?php echo e($driverIndex + 1); ?> Shift</th><td><?php echo e($display($driverAssignment['shift'] ?? $driverAssignment['shiftName'] ?? $driverAssignment['shiftId'] ?? null)); ?></td></tr>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody></table>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="record-empty-message">No assignments saved for this contract.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">🕘</div><div><h3>Record & Audit Information</h3><p>System record, creator and update history.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Record Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Record Code</th><td><?php echo e($record->code); ?></td></tr>
            <tr><th>Record Name</th><td><?php echo e($record->name); ?></td></tr>
            <tr><th>Record Status</th><td><?php echo e($display($record->status)); ?></td></tr>
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
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">📎</div><div><h3>Documents</h3><p>Uploaded contract documents with expiry, reminder and file information.</p></div></div></div>
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
                    <tr><td colspan="5" class="record-empty-cell">No contract documents uploaded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/record-details/contracts.blade.php ENDPATH**/ ?>