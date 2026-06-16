<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', $brand['name'] ?? 'FleetMan'); ?></title>
    <?php if(!empty($brand['favicon_url'])): ?>
        <link rel="icon" href="<?php echo e($brand['favicon_url']); ?>">
        <link rel="shortcut icon" href="<?php echo e($brand['favicon_url']); ?>">
        <link rel="apple-touch-icon" href="<?php echo e($brand['favicon_url']); ?>">
    <?php endif; ?>
    <?php
        $fleetCssVersion = filemtime(public_path('css/fleetman.css'));
        $fleetPage = (string) ($fleetman['page'] ?? '');
        $fleetCoreAsset = 'js/dist/fleetman-core.min.js';
        // Keep the small record API source authoritative so pagination/export
        // safeguards work even before a production asset rebuild is run.
        $fleetRecordApiAsset = 'js/fleetman-record-api.js';
        $fleetModuleAsset = match (true) {
            in_array($fleetPage, ['vehicles', 'fuel-prices', 'fuel-recharge'], true) => 'js/dist/fleetman-operations.min.js',
            in_array($fleetPage, ['vendors', 'trips', 'drivers', 'clients', 'employees', 'driver-attendance'], true) => 'js/dist/fleetman-people.min.js',
            $fleetPage === 'master-data' => 'js/dist/fleetman-master.min.js',
            $fleetPage === 'contracts' => 'js/dist/fleetman-contracts.min.js',
            default => null,
        };
        $fleetUseSplitAssets = file_exists(public_path($fleetCoreAsset))
            && (! $fleetModuleAsset || file_exists(public_path($fleetModuleAsset)));
        $fleetCoreJsVersion = $fleetUseSplitAssets ? filemtime(public_path($fleetCoreAsset)) : filemtime(public_path('js/fleetman.js'));
        $fleetRecordApiJsVersion = filemtime(public_path($fleetRecordApiAsset));
        $fleetModuleJsVersion = $fleetUseSplitAssets && $fleetModuleAsset ? filemtime(public_path($fleetModuleAsset)) : $fleetCoreJsVersion;
        $fleetTransactionGuardJsVersion = filemtime(public_path('js/fleetman-transaction-guard.js'));
        $fleetActionLoaderJsVersion = filemtime(public_path('js/fleetman-action-loader.js'));
        $fleetSearchableDropdownJsVersion = filemtime(public_path('js/fleetman-searchable-dropdown.js'));
        $fleetNavigationJsVersion = filemtime(public_path('js/fleetman-navigation.js'));
        $fleetRbacJsVersion = filemtime(public_path('js/fleetman-rbac.js'));
        $fleetSessionJsVersion = filemtime(public_path('js/fleetman-session-timeout.js'));
        $fleetNotificationsJsVersion = filemtime(public_path('js/fleetman-notifications.js'));
        $pusherEnabled = filled(config('services.pusher.key'))
            && filled(config('services.pusher.secret'))
            && filled(config('services.pusher.app_id'))
            && filled(config('services.pusher.cluster'));
    ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/fleetman.css')); ?>?v=<?php echo e($fleetCssVersion); ?>">
</head>
<body class="preload" data-page="<?php echo e($fleetman['page'] ?? ''); ?>">
    <div class="mobile-top">
        <button type="button" id="menuBtn">☰ Menu</button>
        <a href="<?php echo e(route('fleet.dashboard')); ?>" class="mobile-brand-link" aria-label="Go to Dashboard">
            <b><?php echo e($brand['name'] ?? 'FleetMan'); ?></b>
        </a>
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
            <div class="fleet-notification-slot" aria-label="Notification and account controls">
                <?php if (isset($component)) { $__componentOriginal5e01f85f0f00ef042f7779f4f47d8fd5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5e01f85f0f00ef042f7779f4f47d8fd5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.notification-bell','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.notification-bell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5e01f85f0f00ef042f7779f4f47d8fd5)): ?>
<?php $attributes = $__attributesOriginal5e01f85f0f00ef042f7779f4f47d8fd5; ?>
<?php unset($__attributesOriginal5e01f85f0f00ef042f7779f4f47d8fd5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5e01f85f0f00ef042f7779f4f47d8fd5)): ?>
<?php $component = $__componentOriginal5e01f85f0f00ef042f7779f4f47d8fd5; ?>
<?php unset($__componentOriginal5e01f85f0f00ef042f7779f4f47d8fd5); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginalb97e3b734f0ee9386246bace7c8a3000 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb97e3b734f0ee9386246bace7c8a3000 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.user-menu','data' => ['account' => $account]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.user-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['account' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($account)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb97e3b734f0ee9386246bace7c8a3000)): ?>
<?php $attributes = $__attributesOriginalb97e3b734f0ee9386246bace7c8a3000; ?>
<?php unset($__attributesOriginalb97e3b734f0ee9386246bace7c8a3000); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb97e3b734f0ee9386246bace7c8a3000)): ?>
<?php $component = $__componentOriginalb97e3b734f0ee9386246bace7c8a3000; ?>
<?php unset($__componentOriginalb97e3b734f0ee9386246bace7c8a3000); ?>
<?php endif; ?>
            </div>

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
    <script src="<?php echo e(asset('js/fleetman-transaction-guard.js')); ?>?v=<?php echo e($fleetTransactionGuardJsVersion); ?>"></script>
    <script src="<?php echo e(asset('js/fleetman-searchable-dropdown.js')); ?>?v=<?php echo e($fleetSearchableDropdownJsVersion); ?>"></script>
    <script src="<?php echo e(asset($fleetRecordApiAsset)); ?>?v=<?php echo e($fleetRecordApiJsVersion); ?>"></script>
    <?php if($fleetUseSplitAssets): ?>
        <script src="<?php echo e(asset($fleetCoreAsset)); ?>?v=<?php echo e($fleetCoreJsVersion); ?>"></script>
        <?php if($fleetModuleAsset): ?>
            <script src="<?php echo e(asset($fleetModuleAsset)); ?>?v=<?php echo e($fleetModuleJsVersion); ?>"></script>
        <?php endif; ?>
    <?php else: ?>
        <script src="<?php echo e(asset('js/fleetman.js')); ?>?v=<?php echo e($fleetCoreJsVersion); ?>"></script>
    <?php endif; ?>
    <?php if(session('login_notice')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var loginToast = document.getElementById('toast');

                if (!loginToast) {
                    return;
                }

                loginToast.textContent = <?php echo json_encode(session('login_notice'), 15, 512) ?>;
                loginToast.classList.add('show');
                window.setTimeout(function () {
                    loginToast.classList.remove('show');
                }, 5200);
            }, { once: true });
        </script>
    <?php endif; ?>
    <script src="<?php echo e(asset('js/fleetman-navigation.js')); ?>?v=<?php echo e($fleetNavigationJsVersion); ?>"></script>
    <script src="<?php echo e(asset('js/fleetman-rbac.js')); ?>?v=<?php echo e($fleetRbacJsVersion); ?>"></script>
    <script>
        window.FLEETMAN_NOTIFICATIONS = {
            userId: <?php echo e((int) auth()->id()); ?>,
            feedUrl: <?php echo json_encode(route('fleet.notifications.feed'), 15, 512) ?>,
            readAllUrl: <?php echo json_encode(route('fleet.notifications.read-all'), 15, 512) ?>,
            readUrlTemplate: <?php echo json_encode(route('fleet.notifications.read', ['notification' => '__ID__']), 512) ?>,
            pusherAuthUrl: <?php echo json_encode(route('fleet.notifications.pusher-auth'), 15, 512) ?>,
            pusherEnabled: <?php echo json_encode($pusherEnabled, 15, 512) ?>,
            pusherKey: <?php echo json_encode(config('services.pusher.key'), 15, 512) ?>,
            pusherCluster: <?php echo json_encode(config('services.pusher.cluster'), 15, 512) ?>,
            pollIntervalMs: 60000
        };
    </script>
    <?php if($pusherEnabled): ?>
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <?php endif; ?>
    <script src="<?php echo e(asset('js/fleetman-notifications.js')); ?>?v=<?php echo e($fleetNotificationsJsVersion); ?>"></script>
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
    <script src="<?php echo e(asset('js/fleetman-action-loader.js')); ?>?v=<?php echo e($fleetActionLoaderJsVersion); ?>"></script>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/layouts/fleetman.blade.php ENDPATH**/ ?>