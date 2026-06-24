<?php $__env->startSection('title', 'Notifications - '.($brand['name'] ?? 'FleetMan')); ?>
<?php $__env->startSection('mobile-title', 'Notifications'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section notification-center-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Notifications']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Notifications']])]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Notification Center','subtitle' => 'Your reminders and system activity notifications are stored here even when real-time delivery is unavailable.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Notification Center','subtitle' => 'Your reminders and system activity notifications are stored here even when real-time delivery is unavailable.']); ?>
         <?php $__env->slot('action', null, []); ?> 
            <form method="POST" action="<?php echo e(route('fleet.notifications.read-all')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn secondary">Mark All as Read</button>
            </form>
         <?php $__env->endSlot(); ?>
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
        <div class="fleet-notification-page-alert"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <section class="card">
        <div class="section-head">
            <div>
                <h2>All Notifications</h2>
                <p>Reminders are delivered to the responsible user and administrators. Activity notifications are delivered to Admin and Super Admin accounts.</p>
            </div>
        </div>

        <div class="fleet-notification-page-list">
            <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php ($data = is_array($notification->data) ? $notification->data : []); ?>
                <article class="fleet-notification-page-item <?php echo e($notification->read_at ? '' : 'unread'); ?>">
                    <div class="fleet-notification-page-icon"><?php echo e($data['icon'] ?? '🔔'); ?></div>
                    <div class="fleet-notification-page-copy">
                        <div class="fleet-notification-page-title-row">
                            <strong><?php echo e($data['title'] ?? 'FleetMan Notification'); ?></strong>
                            <?php if (! ($notification->read_at)): ?><span class="badge soft">New</span><?php endif; ?>
                        </div>
                        <p><?php echo e($data['message'] ?? ''); ?></p>
                        <small><?php echo e(optional($notification->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A')); ?></small>
                    </div>
                    <div class="fleet-notification-page-actions">
                        <?php if(! empty($data['url'])): ?>
                            <a class="mini-btn" href="<?php echo e($data['url']); ?>">Open</a>
                        <?php endif; ?>
                        <?php if (! ($notification->read_at)): ?>
                            <button
                                type="button"
                                class="mini-btn fleet-page-mark-read"
                                data-notification-id="<?php echo e($notification->id); ?>"
                            >Mark Read</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty">No notifications yet.</div>
            <?php endif; ?>
        </div>

        <?php if(method_exists($notifications, 'links')): ?>
            <div class="fleet-notification-pagination"><?php echo e($notifications->links()); ?></div>
        <?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/notifications/index.blade.php ENDPATH**/ ?>