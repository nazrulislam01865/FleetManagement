<?php
    $isEditing = isset($release) && $release?->exists;
    $formTitle = $isEditing ? 'Edit Release' : 'Add Release';
?>

<?php $__env->startSection('title', $formTitle.' | FleetMan'); ?>
<?php $__env->startSection('mobile-title', $formTitle); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section release-tracker-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'System'], ['label' => 'Release Tracker / Notes'], ['label' => $formTitle]]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'System'], ['label' => 'Release Tracker / Notes'], ['label' => $formTitle]])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <span class="badge danger">Super Admin Only</span>
            <a href="<?php echo e(route('fleet.release-tracker')); ?>" class="btn light">Release List</a>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => $formTitle,'subtitle' => $isEditing
            ? 'Update the selected application release, issue category, initiator, deployment status, and release notes.'
            : 'Record a new application release, issue category, initiator, deployment status, and release notes.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($formTitle),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isEditing
            ? 'Update the selected application release, issue category, initiator, deployment status, and release notes.'
            : 'Record a new application release, issue category, initiator, deployment status, and release notes.')]); ?>
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

    <?php if(session('status')): ?>
        <div class="role-alert role-alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="role-alert role-alert-danger">
            <b>Could not save the release entry.</b>
            <span><?php echo e($errors->first()); ?></span>
        </div>
    <?php endif; ?>

    <div class="kpi release-kpis">
        <div class="card"><strong><?php echo e($counts['total']); ?></strong><span>Total Releases</span></div>
        <div class="card"><strong><?php echo e($counts['released']); ?></strong><span>Released</span></div>
        <div class="card"><strong><?php echo e($counts['scheduled']); ?></strong><span>Scheduled</span></div>
        <div class="card"><strong><?php echo e($counts['draft']); ?></strong><span>Draft</span></div>
    </div>

    <section class="card release-entry-card">
        <div class="section-head">
            <div>
                <h2><?php echo e($isEditing ? 'Edit Release Entry' : 'Release Entry Form'); ?></h2>
                <p><?php echo e($isEditing ? 'Review the current values and save only the required changes.' : 'All required fields must be completed before the release is added to the release list.'); ?></p>
            </div>
            <span class="badge soft">System history</span>
        </div>

        <form method="POST" action="<?php echo e($isEditing ? route('fleet.release-tracker.update', $release) : route('fleet.release-tracker.store')); ?>">
            <?php echo csrf_field(); ?>
            <?php if($isEditing): ?>
                <?php echo method_field('PUT'); ?>
            <?php endif; ?>
            <div class="release-form-grid">
                <div class="field">
                    <label for="releaseVersion">Version <span class="req">*</span></label>
                    <input id="releaseVersion" name="version" value="<?php echo e(old('version', $release?->version)); ?>" placeholder="Example: v1.4.0" maxlength="60" required>
                </div>

                <div class="field release-title-field">
                    <label for="releaseTitle">Release Title <span class="req">*</span></label>
                    <input id="releaseTitle" name="title" value="<?php echo e(old('title', $release?->title)); ?>" placeholder="Example: Driver image and permission update" maxlength="255" required>
                </div>

                <div class="field">
                    <label for="releaseIssueType">Issue Type <span class="req">*</span></label>
                    <select id="releaseIssueType" name="issue_type" required>
                        <option value="">Select issue type</option>
                        <?php $__currentLoopData = $issueTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('issue_type', $release?->issue_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="field">
                    <label for="releaseInitiatedBy">Initiated By <span class="req">*</span></label>
                    <select id="releaseInitiatedBy" name="initiated_by_user_id" required>
                        <option value="">Select active user</option>
                        <?php $__currentLoopData = $activeUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activeUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($activeUser->id); ?>" <?php if((string) old('initiated_by_user_id', $release?->initiated_by_user_id) === (string) $activeUser->id): echo 'selected'; endif; ?>>
                                <?php echo e($activeUser->name); ?><?php echo e($activeUser->fleetRole?->name ? ' — '.$activeUser->fleetRole->name : ''); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="field fleet-form-temporal-field">
                    <label for="releaseDate">Release Date <span class="req">*</span></label>
                    <input id="releaseDate" type="date" name="release_date" value="<?php echo e(old('release_date', $release?->release_date?->format('Y-m-d') ?? now('Asia/Dhaka')->format('Y-m-d'))); ?>" required>
                </div>

                <div class="field">
                    <label for="releaseEnvironment">Environment <span class="req">*</span></label>
                    <select id="releaseEnvironment" name="environment" required>
                        <?php $__currentLoopData = $environmentOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('environment', $release?->environment ?? 'production') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="field">
                    <label for="releaseStatus">Status <span class="req">*</span></label>
                    <select id="releaseStatus" name="status" required>
                        <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('status', $release?->status ?? 'draft') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="field release-summary-field">
                    <label for="releaseSummary">Summary</label>
                    <textarea id="releaseSummary" name="summary" maxlength="2000" placeholder="Briefly describe this release."><?php echo e(old('summary', $release?->summary)); ?></textarea>
                </div>

                <div class="field release-notes-field">
                    <label for="releaseChanges">Changes / Release Notes</label>
                    <textarea id="releaseChanges" name="changes" maxlength="20000" placeholder="List completed features, fixes, and technical changes."><?php echo e(old('changes', $release?->changes)); ?></textarea>
                </div>

                <div class="field release-notes-field">
                    <label for="releaseKnownIssues">Known Issues</label>
                    <textarea id="releaseKnownIssues" name="known_issues" maxlength="20000" placeholder="List unresolved issues or write None."><?php echo e(old('known_issues', $release?->known_issues)); ?></textarea>
                </div>
            </div>

            <div class="release-form-actions">
                <?php if($isEditing): ?>
                    <a href="<?php echo e(route('fleet.release-tracker')); ?>" class="btn light">Cancel</a>
                    <button type="reset" class="btn light">Reset Changes</button>
                    <button type="submit" class="btn primary">Save Changes</button>
                <?php else: ?>
                    <button type="reset" class="btn light">Clear</button>
                    <button type="submit" class="btn primary">Add Release</button>
                <?php endif; ?>
            </div>
        </form>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/system/release-tracker-form.blade.php ENDPATH**/ ?>