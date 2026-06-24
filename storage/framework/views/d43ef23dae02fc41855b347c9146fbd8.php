<?php $__env->startSection('title', 'Shifts | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Shifts'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $shifts = $fleetman['shiftRows'] ?? collect();
    $editingShift = $fleetman['editingShift'] ?? null;
    $isEditing = $editingShift !== null;
    $formName = old('name', $editingShift?->name ?? '');
    $formCode = old('code', $editingShift?->code ?? '');
    $formStartTime = old('start_time', $editingShift?->start_time ? substr((string) $editingShift->start_time, 0, 5) : '');
    $formEndTime = old('end_time', $editingShift?->end_time ? substr((string) $editingShift->end_time, 0, 5) : '');
    $formSortOrder = old('sort_order', $editingShift?->sort_order ?? 0);
    $formStatus = old('status', ($editingShift?->is_active ?? true) ? 'Active' : 'Inactive');
    $formDescription = old('description', $editingShift?->description ?? '');
?>

<div class="page-section master-data-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Shifts']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Shifts']])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $attributes = $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b)): ?>
<?php $component = $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b; ?>
<?php unset($__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => ''.e($fleetman['masterTitle'] ?? 'Shift Master').'','subtitle' => ''.e($fleetman['masterSubtitle'] ?? 'Manage the shifts used when a double-shift vehicle is assigned to a contract.').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($fleetman['masterTitle'] ?? 'Shift Master').'','subtitle' => ''.e($fleetman['masterSubtitle'] ?? 'Manage the shifts used when a double-shift vehicle is assigned to a contract.').'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $attributes = $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $component = $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>

    <?php if(session('success')): ?>
        <div class="login-success" role="status"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="login-error" role="alert">Please correct the highlighted shift fields and submit again.</div>
    <?php endif; ?>

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">🕒</div>
            <div><strong><?php echo e($shifts->where('is_active', true)->count()); ?></strong><span>Active shifts available for contract assignments</span></div>
        </div>
    </div>

    <section class="card master-card">
        <div class="section-head">
            <div><h2><?php echo e($isEditing ? 'Edit Shift' : 'Add Shift'); ?></h2></div>
            <a href="<?php echo e(route('fleet.master-data.shifts')); ?>" class="btn light"><?php echo e($isEditing ? 'Cancel Edit' : 'Reset'); ?></a>
        </div>

        <form
            method="POST"
            action="<?php echo e($isEditing ? route('fleet.master-data.shifts.update', $editingShift) : route('fleet.master-data.shifts.store')); ?>"
            class="master-form"
            autocomplete="off"
        >
            <?php echo csrf_field(); ?>
            <?php if($isEditing): ?>
                <?php echo method_field('PUT'); ?>
            <?php endif; ?>

            <div class="field <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <label for="shiftName">Shift Name <span class="req">*</span></label>
                <input id="shiftName" name="name" type="text" value="<?php echo e($formName); ?>" placeholder="Example: Day Shift" required maxlength="120">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="field <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <label for="shiftCode">Code</label>
                <input id="shiftCode" name="code" type="text" value="<?php echo e($formCode); ?>" placeholder="Auto-generated when left empty" maxlength="120" pattern="[A-Za-z0-9_]+">
                <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="field fleet-form-temporal-field <?php $__errorArgs = ['start_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <label for="shiftStartTime">Start Time</label>
                <input id="shiftStartTime" name="start_time" type="time" value="<?php echo e($formStartTime); ?>">
                <?php $__errorArgs = ['start_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="field fleet-form-temporal-field <?php $__errorArgs = ['end_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <label for="shiftEndTime">End Time</label>
                <input id="shiftEndTime" name="end_time" type="time" value="<?php echo e($formEndTime); ?>">
                <?php $__errorArgs = ['end_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="field <?php $__errorArgs = ['sort_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <label for="shiftSortOrder">Sort Order</label>
                <input id="shiftSortOrder" name="sort_order" type="number" value="<?php echo e($formSortOrder); ?>" min="0" max="999999">
                <?php $__errorArgs = ['sort_order'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="field <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <label for="shiftStatus">Status <span class="req">*</span></label>
                <select id="shiftStatus" name="status" required>
                    <option value="Active" <?php if($formStatus === 'Active'): echo 'selected'; endif; ?>>Active</option>
                    <option value="Inactive" <?php if($formStatus === 'Inactive'): echo 'selected'; endif; ?>>Inactive</option>
                </select>
                <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="master-form-full field <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> field-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <label for="shiftDescription">Description / Note</label>
                <textarea id="shiftDescription" name="description" maxlength="2000" placeholder="Optional note about this shift."><?php echo e($formDescription); ?></textarea>
                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="field-error"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="master-form-actions">
                <button type="submit" class="btn primary"><?php echo e($isEditing ? 'Update Shift' : 'Save Shift'); ?></button>
                <?php if($isEditing): ?>
                    <a href="<?php echo e(route('fleet.master-data.shifts')); ?>" class="btn light">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="master-table-title"><div><b>Added Shifts</b></div></div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr><th>Created At</th><th>Shift</th><th>Code</th><th>Time</th><th>Sort</th><th>Status</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $shifts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shift): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="created-at-cell">
                                    <span class="created-at-date"><?php echo e(optional($shift->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A')); ?></span>
                                    <small class="created-at-creator">Created by: <?php echo e($shift->creatorName ?? 'System / Legacy'); ?></small>
                                </div>
                            </td>
                            <td><b><?php echo e($shift->name); ?></b></td>
                            <td><span class="master-code"><?php echo e($shift->code); ?></span></td>
                            <td><?php echo e($shift->start_time ? substr((string) $shift->start_time, 0, 5) : '—'); ?> – <?php echo e($shift->end_time ? substr((string) $shift->end_time, 0, 5) : '—'); ?></td>
                            <td><?php echo e($shift->sort_order); ?></td>
                            <td><span class="badge <?php echo e($shift->is_active ? 'ok' : 'warn'); ?>"><?php echo e($shift->is_active ? 'Active' : 'Inactive'); ?></span></td>
                            <td class="master-description"><?php echo e($shift->description ?: '—'); ?></td>
                            <td>
                                <div class="master-actions">
                                    <a href="<?php echo e(route('fleet.master-data.shifts', ['edit' => $shift->id])); ?>" class="mini-btn">Edit</a>
                                    <form method="POST" action="<?php echo e(route('fleet.master-data.shifts.destroy', $shift)); ?>" onsubmit="return confirm('Delete this shift? Existing contracts will keep their saved shift information.');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="mini-btn danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8" class="empty">No shift added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/master-data/shifts.blade.php ENDPATH**/ ?>