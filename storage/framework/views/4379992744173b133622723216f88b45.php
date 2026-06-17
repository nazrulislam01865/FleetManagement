<?php
    $contacts = array_values(array_filter((array) ($recordPayload['contacts'] ?? []), 'is_array'));
    $documents = array_values(array_filter((array) ($recordPayload['documents'] ?? []), 'is_array'));
    $employeePhotoUrl = $photoUrl($recordPayload['photo'] ?? []);
    $employeeStatus = $display($recordPayload['status'] ?? $record->status);
    $primaryContact = $recordPayload['contactNumber'] ?? data_get($contacts, '0.number', data_get($contacts, '0.phone'));
    $emergencyContact = collect($contacts)->first(fn (array $contact): bool => strcasecmp((string) ($contact['type'] ?? ''), 'Relative') === 0)
        ?? ($contacts[1] ?? $contacts[0] ?? []);
?>

<section class="record-profile-summary">
    <div class="record-profile-photo">
        <?php if($employeePhotoUrl !== ''): ?>
            <img src="<?php echo e($employeePhotoUrl); ?>" alt="<?php echo e($recordTitle); ?> photo">
        <?php else: ?>
            <span aria-hidden="true">👤</span>
        <?php endif; ?>
    </div>

    <div class="record-summary-main">
        <h2><?php echo e($display($recordPayload['fullName'] ?? $recordTitle)); ?></h2>
        <p><?php echo e($display($recordPayload['designation'] ?? null)); ?></p>
        <div class="record-tags">
            <span class="record-tag success"><?php echo e($employeeStatus); ?></span>
            <span class="record-tag primary"><?php echo e($display($recordPayload['employeeId'] ?? $record->code)); ?></span>
            <span class="record-tag warning"><?php echo e($display($recordPayload['salaryTenure'] ?? null)); ?> Salary</span>
        </div>
    </div>

    <div class="record-summary-stats">
        <div class="record-summary-stat"><label>Joining Date</label><div><?php echo e($formatDate($recordPayload['joiningDate'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Salary</label><div><?php echo e($formatMoney($recordPayload['salary'] ?? null)); ?></div></div>
        <div class="record-summary-stat"><label>Contact</label><div><?php echo e($display($primaryContact)); ?></div></div>
        <div class="record-summary-stat"><label>Last Updated</label><div><?php echo e($formatDateTime($record->updated_at)); ?></div></div>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">👤</div><div><h3>Personal Information</h3><p>Identity, contact, emergency contact and address details.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Basic Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Full Name</th><td><?php echo e($display($recordPayload['fullName'] ?? $recordTitle)); ?></td></tr>
            <tr><th>Age</th><td><?php echo e($display($recordPayload['age'] ?? null)); ?></td></tr>
            <tr><th>NID</th><td><?php echo e($display($recordPayload['nid'] ?? null)); ?></td></tr>
            <tr><th>Father Name</th><td><?php echo e($display($recordPayload['fatherName'] ?? null)); ?></td></tr>
            <tr><th>Mother Name</th><td><?php echo e($display($recordPayload['motherName'] ?? null)); ?></td></tr>
            <tr><th>Reference</th><td><?php echo e($display($recordPayload['reference'] ?? null)); ?></td></tr>
            <tr><th>About</th><td><?php echo e($display($recordPayload['about'] ?? null)); ?></td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Contact Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Email</th><td><?php echo e($display($recordPayload['email'] ?? null)); ?></td></tr>
            <tr><th>Contact Number</th><td><?php echo e($display($primaryContact)); ?></td></tr>
            <tr><th>Social Media</th><td><?php echo e($display($recordPayload['socialMedia'] ?? null)); ?></td></tr>
            <tr><th>Emergency Contact Type</th><td><?php echo e($display($emergencyContact['type'] ?? null)); ?></td></tr>
            <tr><th>Emergency Relationship</th><td><?php echo e($display($emergencyContact['relationship'] ?? null)); ?></td></tr>
            <tr><th>Emergency Number</th><td><?php echo e($display($emergencyContact['number'] ?? $emergencyContact['phone'] ?? null)); ?></td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Address Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Present Address</th><td><?php echo e($display($recordPayload['presentAddress'] ?? null)); ?></td></tr>
            <tr><th>Permanent Address</th><td><?php echo e($display($recordPayload['permanentAddress'] ?? null)); ?></td></tr>
        </tbody></table>
    </div>
</section>

<section class="record-detail-card">
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">💼</div><div><h3>Employment Information</h3><p>Job, salary, status and system audit information.</p></div></div></div>
    <div class="record-table-section">
        <h4 class="record-sub-title">Job Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Employee ID</th><td><?php echo e($display($recordPayload['employeeId'] ?? $record->code)); ?></td></tr>
            <tr><th>Designation</th><td><?php echo e($display($recordPayload['designation'] ?? null)); ?></td></tr>
            <tr><th>Status</th><td><span class="record-status-pill"><?php echo e($employeeStatus); ?></span></td></tr>
            <tr><th>Joining Date</th><td><?php echo e($formatDate($recordPayload['joiningDate'] ?? null, 'Y-m-d')); ?></td></tr>
            <tr><th>Record Code</th><td><?php echo e($record->code); ?></td></tr>
            <tr><th>Record Name</th><td><?php echo e($record->name); ?></td></tr>
            <tr><th>Record Status</th><td><?php echo e($display($record->status)); ?></td></tr>
            <tr><th>Validation Version</th><td><?php echo e($display($recordPayload['employeeValidationVersion'] ?? null)); ?></td></tr>
        </tbody></table>

        <h4 class="record-sub-title">Salary Information</h4>
        <table class="record-info-table"><tbody>
            <tr><th>Salary</th><td><?php echo e($formatMoney($recordPayload['salary'] ?? null)); ?></td></tr>
            <tr><th>Salary Tenure</th><td><?php echo e($display($recordPayload['salaryTenure'] ?? null)); ?></td></tr>
            <tr><th>Overtime Rate</th><td><?php echo e($display($recordPayload['overtimeRate'] ?? null)); ?></td></tr>
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
    <div class="record-card-header"><div class="record-card-header-left"><div class="record-card-icon">📎</div><div><h3>Documents</h3><p>Uploaded employee documents with expiry, reminder and file information.</p></div></div></div>
    <div class="record-table-section">
        <table class="record-file-table">
            <thead><tr><th>Document Name</th><th>Expiry</th><th>Reminder</th><th>Reference</th><th>File</th><th>Action</th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($documentFile = is_array($document['file'] ?? null) ? $document['file'] : []); ?>
                    <?php ($documentUrl = $fileUrl($documentFile)); ?>
                    <tr>
                        <td data-label="Document Name"><?php echo e($display($document['name'] ?? null)); ?></td>
                        <td data-label="Expiry"><?php echo e($formatDate($document['expiry'] ?? null, 'Y-m-d')); ?></td>
                        <td data-label="Reminder"><?php echo e($display($document['reminder'] ?? null)); ?></td>
                        <td data-label="Reference"><?php echo e($display($document['reference'] ?? null)); ?></td>
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
                    <tr><td colspan="6" class="record-empty-cell">No employee documents uploaded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/record-details/employees.blade.php ENDPATH**/ ?>