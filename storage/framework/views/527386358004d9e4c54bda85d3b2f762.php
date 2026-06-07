<?php $__env->startSection('title', 'Payment Types | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Payment Types'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $paymentTypes = $fleetman['paymentTypeRows'] ?? collect();
    $editingPaymentType = $fleetman['editingPaymentType'] ?? null;
    $isEditing = $editingPaymentType !== null;
    $formName = old('name', $editingPaymentType?->name ?? '');
    $formCode = old('code', $editingPaymentType?->code ?? '');
    $formSortOrder = old('sort_order', $editingPaymentType?->sort_order ?? 0);
    $formStatus = old('status', ($editingPaymentType?->is_active ?? true) ? 'Active' : 'Inactive');
    $formDescription = old('description', $editingPaymentType?->description ?? '');
?>

<div class="page-section master-data-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Payment Types']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Payment Types']])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('fleet.trips', ['action' => 'add'])); ?>" class="btn secondary">Open Add Trip</a>
            <span class="badge soft">Saved directly by Laravel</span>
         <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => ''.e($fleetman['masterTitle'] ?? 'Payment Type Master').'','subtitle' => ''.e($fleetman['masterSubtitle'] ?? 'Manage the payment methods available on the Add Trip page.').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($fleetman['masterTitle'] ?? 'Payment Type Master').'','subtitle' => ''.e($fleetman['masterSubtitle'] ?? 'Manage the payment methods available on the Add Trip page.').'']); ?>
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
        <div class="login-error" role="alert">
            Please correct the highlighted payment type fields and submit again.
        </div>
    <?php endif; ?>

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">💳</div>
            <div><strong><?php echo e($paymentTypes->where('is_active', true)->count()); ?></strong><span>Active payment types available for Add Trip</span></div>
        </div>
    </div>

    <section class="card master-card" id="paymentTypeMasterCard">
        <div class="section-head">
            <div>
                <h2><?php echo e($isEditing ? 'Edit Payment Type' : 'Add Payment Type'); ?></h2>
                <p>These values are written directly to the Laravel database and are not rebuilt from browser JavaScript.</p>
            </div>
            <a href="<?php echo e(route('fleet.master-data.payment-types')); ?>" class="btn light"><?php echo e($isEditing ? 'Cancel Edit' : 'Reset'); ?></a>
        </div>

        <form
            method="POST"
            action="<?php echo e($isEditing ? route('fleet.master-data.payment-types.update', $editingPaymentType) : route('fleet.master-data.payment-types.store')); ?>"
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
                <label for="paymentTypeName">Payment Type Name <span class="req">*</span></label>
                <input
                    id="paymentTypeName"
                    name="name"
                    type="text"
                    value="<?php echo e($formName); ?>"
                    placeholder="Example: Cash"
                    required
                    maxlength="120"
                    aria-required="true"
                >
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
                <label for="paymentTypeCode">Code</label>
                <input
                    id="paymentTypeCode"
                    name="code"
                    type="text"
                    value="<?php echo e($formCode); ?>"
                    placeholder="Auto-generated when left empty"
                    maxlength="120"
                    pattern="[A-Za-z0-9_]+"
                >
                <div class="hint">Leave empty to generate the code in Laravel from the payment type name.</div>
                <?php $__errorArgs = ['code'];
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
                <label for="paymentTypeSortOrder">Sort Order</label>
                <input
                    id="paymentTypeSortOrder"
                    name="sort_order"
                    type="number"
                    value="<?php echo e($formSortOrder); ?>"
                    min="0"
                    max="999999"
                >
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
                <label for="paymentTypeStatus">Status <span class="req">*</span></label>
                <select id="paymentTypeStatus" name="status" required aria-required="true">
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
                <label for="paymentTypeDescription">Description / Note</label>
                <textarea
                    id="paymentTypeDescription"
                    name="description"
                    maxlength="2000"
                    placeholder="Optional internal note about this payment method."
                ><?php echo e($formDescription); ?></textarea>
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
                <button type="submit" class="btn primary"><?php echo e($isEditing ? 'Update Payment Type' : 'Save Payment Type'); ?></button>
                <?php if($isEditing): ?>
                    <a href="<?php echo e(route('fleet.master-data.payment-types')); ?>" class="btn light">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Payment Types</b><small>Only active rows appear in the Add Trip payment-method dropdown.</small></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Payment Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $paymentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><b><?php echo e($paymentType->name); ?></b></td>
                            <td><span class="master-code"><?php echo e($paymentType->code); ?></span></td>
                            <td><?php echo e($paymentType->sort_order); ?></td>
                            <td>
                                <span class="badge <?php echo e($paymentType->is_active ? 'ok' : 'warn'); ?>">
                                    <?php echo e($paymentType->is_active ? 'Active' : 'Inactive'); ?>

                                </span>
                            </td>
                            <td class="master-description"><?php echo e($paymentType->description ?: '—'); ?></td>
                            <td>
                                <div class="master-actions">
                                    <a
                                        href="<?php echo e(route('fleet.master-data.payment-types', ['edit' => $paymentType->id])); ?>"
                                        class="mini-btn"
                                    >Edit</a>
                                    <form
                                        method="POST"
                                        action="<?php echo e(route('fleet.master-data.payment-types.destroy', $paymentType)); ?>"
                                        onsubmit="return confirm('Delete this payment type? Existing trip records will keep their saved payment method.');"
                                    >
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="mini-btn danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="empty">No payment type added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/master-data/payment-types.blade.php ENDPATH**/ ?>