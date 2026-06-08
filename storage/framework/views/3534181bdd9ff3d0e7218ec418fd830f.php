<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', $brand['name'] ?? 'FleetMan'); ?></title>
    <?php
        $fleetCssVersion = filemtime(public_path('css/fleetman.css'));
        $fleetJsVersion = filemtime(public_path('js/fleetman.js'));
        $fleetNavigationJsVersion = filemtime(public_path('js/fleetman-navigation.js'));
        $fleetRbacJsVersion = filemtime(public_path('js/fleetman-rbac.js'));
        $fleetSessionJsVersion = filemtime(public_path('js/fleetman-session-timeout.js'));
    ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/fleetman.css')); ?>?v=<?php echo e($fleetCssVersion); ?>">
</head>
<body class="preload" data-page="<?php echo e($fleetman['page'] ?? ''); ?>">
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

                    if (!sidebar || !window.localStorage) {
                        return;
                    }

                    var pendingScrollValue = sessionStorage.getItem('fleetman.sidebar.pendingScrollTop');
                    var savedScrollValue = localStorage.getItem('fleetman.sidebar.scrollTop');
                    var pendingScrollTop = pendingScrollValue === null ? NaN : Number(pendingScrollValue);
                    var savedScrollTop = savedScrollValue === null ? NaN : Number(savedScrollValue);
                    var scrollTop = Number.isFinite(pendingScrollTop) && pendingScrollTop >= 0
                        ? pendingScrollTop
                        : (Number.isFinite(savedScrollTop) && savedScrollTop >= 0 ? savedScrollTop : 0);

                    window.__fleetmanSidebarScrollTarget = scrollTop;

                    sidebar.querySelectorAll('[data-menu-block]').forEach(function (block) {
                        var key = block.getAttribute('data-menu-key') || '';
                        var toggle = block.querySelector('[data-submenu-toggle]');

                        if (!key || !toggle) {
                            return;
                        }

                        var routeActive = block.getAttribute('data-route-active') === '1';
                        var saved = localStorage.getItem('fleetman.sidebar.open.' + key);
                        var isOpen = routeActive;

                        if (!routeActive && saved === '1') {
                            isOpen = true;
                        } else if (!routeActive && saved === '0') {
                            isOpen = false;
                        }

                        block.classList.toggle('open', isOpen);
                        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    });

                    sidebar.scrollTop = scrollTop;
                    requestAnimationFrame(function () {
                        sidebar.scrollTop = scrollTop;
                    });
                } catch (error) {
                    console.warn('Unable to restore the FleetMan sidebar state.', error);
                }
            })();
        </script>

        <main class="main-content">
            <div class="fleet-main-body">
                <?php echo $__env->yieldContent('content'); ?>
            </div>

            <?php if (isset($component)) { $__componentOriginal36bae408b14af0e7fcaf0db48c860d89 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal36bae408b14af0e7fcaf0db48c860d89 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.footer','data' => ['brand' => $brand]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['brand' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($brand)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal36bae408b14af0e7fcaf0db48c860d89)): ?>
<?php $attributes = $__attributesOriginal36bae408b14af0e7fcaf0db48c860d89; ?>
<?php unset($__attributesOriginal36bae408b14af0e7fcaf0db48c860d89); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal36bae408b14af0e7fcaf0db48c860d89)): ?>
<?php $component = $__componentOriginal36bae408b14af0e7fcaf0db48c860d89; ?>
<?php unset($__componentOriginal36bae408b14af0e7fcaf0db48c860d89); ?>
<?php endif; ?>
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script>
        window.FLEETMAN = <?php echo json_encode($fleetman ?? [], 15, 512) ?>;
    </script>
    <script src="<?php echo e(asset('js/fleetman.js')); ?>?v=<?php echo e($fleetJsVersion); ?>"></script>
    <script src="<?php echo e(asset('js/fleetman-navigation.js')); ?>?v=<?php echo e($fleetNavigationJsVersion); ?>"></script>
    <script src="<?php echo e(asset('js/fleetman-rbac.js')); ?>?v=<?php echo e($fleetRbacJsVersion); ?>"></script>
    <script>
        window.FLEETMAN_SESSION = {
            timeoutMs: <?php echo e((int) config('fleetman.inactivity_timeout_minutes', 15) * 60 * 1000); ?>,
            keepAliveUrl: <?php echo json_encode(route('session.keep-alive'), 15, 512) ?>,
            timeoutUrl: <?php echo json_encode(route('session.timeout'), 15, 512) ?>,
            loginUrl: <?php echo json_encode(route('login'), 15, 512) ?>
        };
    </script>
    <script src="<?php echo e(asset('js/fleetman-session-timeout.js')); ?>?v=<?php echo e($fleetSessionJsVersion); ?>"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/layouts/fleetman.blade.php ENDPATH**/ ?>