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
                    $children = $item['children'] ?? [];
                    $hasChildren = count($children) > 0;
                    $isChildActive = false;

                    foreach ($children as &$child) {
                        $child['isActive'] = $activeMenu === ($child['key'] ?? null);
                        if (isset($child['routeParams']['action']) && request()->query('action') === $child['routeParams']['action'] && $activeMenu === ($item['key'] ?? null)) {
                            $child['isActive'] = true;
                        } elseif (!request()->query('action') && str_ends_with($child['key'] ?? '', '-list') && $activeMenu === ($item['key'] ?? null)) {
                            $child['isActive'] = true;
                        }
                        if ($child['isActive']) {
                            $isChildActive = true;
                        }
                    }
                    unset($child);

                    $isActive = $activeMenu === $item['key'];
                    $isOpen = $isActive || $isChildActive;
                    $href = ! empty($item['route']) && Route::has($item['route']) ? route($item['route'], $item['routeParams'] ?? []) : '#';
                ?>

                <div
                    class="menu-block <?php echo e($isOpen ? 'open' : ''); ?>"
                    data-menu-block
                    data-menu-key="<?php echo e($item['key']); ?>"
                    data-route-active="<?php echo e($isOpen ? '1' : '0'); ?>"
                >
                    <a href="<?php echo e($href); ?>"
                       class="menu-item <?php echo e($isOpen ? 'active' : ''); ?> <?php echo e($hasChildren ? 'has-children' : ''); ?>"
                       <?php if($hasChildren): ?>
                           data-submenu-toggle="<?php echo e($item['key']); ?>"
                           aria-expanded="<?php echo e($isOpen ? 'true' : 'false'); ?>"
                           aria-controls="submenu-<?php echo e($item['key']); ?>"
                       <?php endif; ?>
                    >
                        <span><?php echo e($item['icon']); ?></span>
                        <span><?php echo e($item['label']); ?></span>
                        <?php if($hasChildren): ?>
                            <span class="submenu-arrow" aria-hidden="true">▾</span>
                        <?php endif; ?>
                    </a>

                    <?php if($hasChildren): ?>
                        <div class="submenu" id="submenu-<?php echo e($item['key']); ?>">
                            <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $childHref = ! empty($child['route']) && Route::has($child['route']) ? route($child['route'], $child['routeParams'] ?? []) : '#';
                                    $childActive = $child['isActive'] ?? false;
                                ?>
                                <a href="<?php echo e($childHref); ?>" class="submenu-item <?php echo e($childActive ? 'active' : ''); ?>">
                                    <span><?php echo e($child['icon'] ?? '↳'); ?></span>
                                    <span><?php echo e($child['label']); ?></span>
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