<aside class="sidebar" id="fleetSidebar">
    <div class="logo-card">
        <div class="logo-mark">
            <?php if(!empty($brand['logo_url'])): ?>
                <img src="<?php echo e($brand['logo_url']); ?>" alt="<?php echo e($brand['name'] ?? 'FleetMan Logo'); ?>" style="max-height: 96px; max-width: 100%; object-fit: contain;">
            <?php else: ?>
                🚙 <?php echo e($brand['name'] ?? 'FleetMan'); ?>

                <small><?php echo e($brand['tagline'] ?? 'Fleet Management System'); ?></small>
            <?php endif; ?>
        </div>
    </div>

    <div class="account-card">
        <div class="avatar"><?php echo e($account['avatar'] ?? '👤'); ?></div>
        <div>
            <b><?php echo e($account['title'] ?? 'My Account'); ?></b>
            <span><?php echo e($account['name'] ?? 'User'); ?></span>
        </div>
    </div>

    <nav class="menu-nav">
        <?php $__currentLoopData = $menuGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="menu-title"><?php echo e($group['title']); ?></div>
            <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $itemAllowed = (bool) ($item['allowed'] ?? true);
                    $children = $item['children'] ?? [];
                    $hasChildren = count($children) > 0;
                    $isChildActive = false;

                    foreach ($children as &$child) {
                        $childAllowed = (bool) ($child['allowed'] ?? true);
                        $child['isActive'] = $childAllowed && $activeMenu === ($child['key'] ?? null);
                        if ($childAllowed && isset($child['routeParams']['action']) && request()->query('action') === $child['routeParams']['action'] && $activeMenu === ($item['key'] ?? null)) {
                            $child['isActive'] = true;
                        } elseif ($childAllowed && !request()->query('action') && str_ends_with($child['key'] ?? '', '-list') && $activeMenu === ($item['key'] ?? null)) {
                            $child['isActive'] = true;
                        }
                        if ($child['isActive']) {
                            $isChildActive = true;
                        }
                    }
                    unset($child);

                    $isActive = $itemAllowed && $activeMenu === $item['key'];
                    $isOpen = $itemAllowed && ($isActive || $isChildActive);
                    $href = $itemAllowed && ! empty($item['route']) && Route::has($item['route'])
                        ? route($item['route'], $item['routeParams'] ?? [])
                        : '#';
                ?>

                <div
                    class="menu-block <?php echo e($isOpen ? 'open' : ''); ?> <?php echo e(! $itemAllowed ? 'rbac-menu-muted' : ''); ?>"
                    data-menu-block
                    data-menu-key="<?php echo e($item['key']); ?>"
                    data-route-active="<?php echo e($isOpen ? '1' : '0'); ?>"
                >
                    <a href="<?php echo e($href); ?>"
                       class="menu-item <?php echo e($isOpen ? 'active' : ''); ?> <?php echo e($hasChildren ? 'has-children' : ''); ?> <?php echo e(! $itemAllowed ? 'rbac-muted' : ''); ?>"
                       <?php if(! $itemAllowed): ?>
                           aria-disabled="true"
                           tabindex="-1"
                           title="Access not granted for your role"
                           data-rbac-disabled="true"
                       <?php elseif($hasChildren): ?>
                           data-submenu-toggle="<?php echo e($item['key']); ?>"
                           aria-expanded="<?php echo e($isOpen ? 'true' : 'false'); ?>"
                           aria-controls="submenu-<?php echo e($item['key']); ?>"
                       <?php endif; ?>
                    >
                        <span><?php echo e($item['icon']); ?></span>
                        <span><?php echo e($item['label']); ?></span>
                        <?php if(! $itemAllowed): ?>
                            <span class="rbac-lock" aria-hidden="true">🔒</span>
                        <?php elseif($hasChildren): ?>
                            <span class="submenu-arrow" aria-hidden="true">▾</span>
                        <?php endif; ?>
                    </a>

                    <?php if($hasChildren && $itemAllowed): ?>
                        <div class="submenu" id="submenu-<?php echo e($item['key']); ?>">
                            <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $childAllowed = (bool) ($child['allowed'] ?? true);
                                    $childHref = $childAllowed && ! empty($child['route']) && Route::has($child['route'])
                                        ? route($child['route'], $child['routeParams'] ?? [])
                                        : '#';
                                    $childActive = $childAllowed && ($child['isActive'] ?? false);
                                ?>
                                <a href="<?php echo e($childHref); ?>"
                                   class="submenu-item <?php echo e($childActive ? 'active' : ''); ?> <?php echo e(! $childAllowed ? 'rbac-muted' : ''); ?>"
                                   <?php if(! $childAllowed): ?>
                                       aria-disabled="true"
                                       tabindex="-1"
                                       title="Access not granted for your role"
                                       data-rbac-disabled="true"
                                   <?php endif; ?>
                                >
                                    <span><?php echo e($child['icon'] ?? '↳'); ?></span>
                                    <span><?php echo e($child['label']); ?></span>
                                    <?php if(! $childAllowed): ?><span class="rbac-lock" aria-hidden="true">🔒</span><?php endif; ?>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>

    <?php if(auth()->guard()->check()): ?>
        <form method="POST" action="<?php echo e(route('logout')); ?>" class="logout-form logout-form-bottom">
            <?php echo csrf_field(); ?>
            <button type="submit">↪ Logout</button>
        </form>
    <?php endif; ?>
</aside>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/sidebar.blade.php ENDPATH**/ ?>