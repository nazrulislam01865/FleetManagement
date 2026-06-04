<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', $brand['name'] ?? 'FleetMan'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/fleetman.css')); ?>">
</head>
<body data-page="<?php echo e($fleetman['page'] ?? ''); ?>">
    <div class="mobile-top">
        <button type="button" id="menuBtn">☰ Menu</button>
        <b><?php echo e($brand['name'] ?? 'FleetMan'); ?></b>
        <span><?php echo $__env->yieldContent('mobile-title', 'Fleet'); ?></span>
    </div>
    <div class="drawer-backdrop" id="backdrop"></div>

    <div class="app">
        <?php if (isset($component)) { $__componentOriginal3172327ea90ea5f15468496fc0af39cb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3172327ea90ea5f15468496fc0af39cb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.sidebar','data' => ['brand' => $brand,'account' => $account,'menuGroups' => $menuGroups,'activeMenu' => $activeMenu]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['brand' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($brand),'account' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($account),'menu-groups' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($menuGroups),'active-menu' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeMenu)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3172327ea90ea5f15468496fc0af39cb)): ?>
<?php $attributes = $__attributesOriginal3172327ea90ea5f15468496fc0af39cb; ?>
<?php unset($__attributesOriginal3172327ea90ea5f15468496fc0af39cb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3172327ea90ea5f15468496fc0af39cb)): ?>
<?php $component = $__componentOriginal3172327ea90ea5f15468496fc0af39cb; ?>
<?php unset($__componentOriginal3172327ea90ea5f15468496fc0af39cb); ?>
<?php endif; ?>
        <script>
            (function () {
                try {
                    var sidebar = document.getElementById('fleetSidebar');
                    if (!sidebar || !window.localStorage) return;
                    var scrollTop = Number(localStorage.getItem('fleetman.sidebar.scrollTop') || 0);
                    if (scrollTop > 0) sidebar.scrollTop = scrollTop;
                    sidebar.querySelectorAll('[data-menu-block]').forEach(function (block) {
                        var key = block.getAttribute('data-menu-key') || '';
                        var toggle = block.querySelector('[data-submenu-toggle]');
                        if (!key || !toggle) return;
                        var saved = localStorage.getItem('fleetman.sidebar.open.' + key);
                        if (saved !== '1' && saved !== '0') return;
                        var isOpen = saved === '1';
                        block.classList.toggle('open', isOpen);
                        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    });
                } catch (error) {}
            })();
        </script>

        <main class="main-content">
            <?php echo $__env->yieldContent('content'); ?>

            <footer class="fleet-footer">
                © <?php echo e(date('Y')); ?> <?php echo e($brand['name'] ?? 'FleetMan'); ?>. All Rights Reserved.<br>
                System Design, Development &amp; Intellectual Property owned by
                <a href="#"><?php echo e($brand['footer_owner'] ?? 'ITQAN Consulting'); ?></a>
            </footer>
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script>
        window.FLEETMAN = <?php echo json_encode($fleetman ?? [], 15, 512) ?>;
    </script>
    <script src="<?php echo e(asset('js/fleetman.js')); ?>"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/layouts/fleetman.blade.php ENDPATH**/ ?>