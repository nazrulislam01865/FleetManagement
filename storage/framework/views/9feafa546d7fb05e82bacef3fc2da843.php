<?php $__env->startSection('title', 'FleetMan Dashboard'); ?>
<?php $__env->startSection('mobile-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $stats = $fleetman['dashboard']['stats'] ?? [];
        $finance = $fleetman['dashboard']['finance'] ?? [];
        $recent = $fleetman['dashboard']['recent'] ?? [];
        $warnings = $fleetman['dashboard']['warnings'] ?? [];
        $latestFuel = $finance['fuel_rate'] ?? null;
        $access = $fleetman['dashboard']['access'] ?? [];
        $canFleet = static fn (string $permission): bool => auth()->user()?->canFleet($permission) ?? false;
    ?>

    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'DASHBOARD']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'DASHBOARD']])]); ?>
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

    <section class="dashboard-hero">
        <div>
            <span class="dashboard-eyebrow">Fleet control center</span>
            <h1>Welcome back, <?php echo e(auth()->user()->name ?? ($account['name'] ?? 'User')); ?></h1>
            <p>Monitor trips, vehicles, drivers, fuel, clients, vendors, employees, and attendance from one place.</p>
            <div class="hero-actions">
                <?php if($canFleet('driver_attendance.view') && $canFleet('driver_attendance.manage')): ?>
                    <a class="btn primary" href="<?php echo e(route('fleet.driver-attendance', ['action' => 'add'])); ?>">📝 Add Log</a>
                <?php else: ?>
                    <span class="btn primary dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">🔒 Add Log</span>
                <?php endif; ?>
                <?php if($canFleet('fuel_recharge.view') && $canFleet('fuel_recharge.manage')): ?>
                    <a class="btn secondary" href="<?php echo e(route('fleet.fuel-recharge', ['action' => 'add'])); ?>">⛽ Add Fuel</a>
                <?php else: ?>
                    <span class="btn secondary dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">🔒 Add Fuel</span>
                <?php endif; ?>
                <?php if($canFleet('trips.view') && $canFleet('trips.manage')): ?>
                    <a class="btn light" href="<?php echo e(route('fleet.trips', ['action' => 'add'])); ?>">🧭 Add Trip</a>
                <?php else: ?>
                    <span class="btn light dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">🔒 Add Trip</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="dashboard-hero-card">
            <small>Today</small>
            <strong><?php echo e(now()->format('d M Y')); ?></strong>
            <span><?php echo e(now()->format('l')); ?></span>
            <div class="hero-mini-grid">
                <div><b>৳ <?php echo e(number_format($finance['trip_cost'] ?? 0)); ?></b><small>Trip cost</small></div>
                <div><b>৳ <?php echo e(number_format($finance['payroll'] ?? 0)); ?></b><small>Payroll base</small></div>
            </div>
        </div>
    </section>

    <div class="dashboard-kpis">
        <?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($statAllowed = $canFleet($stat['permission'] ?? '')); ?>
            <?php if($statAllowed): ?>
                <a class="dashboard-kpi-card" href="<?php echo e(route($stat['route'])); ?>">
                    <div class="dashboard-kpi-icon"><?php echo e($stat['icon']); ?></div>
                    <div>
                        <strong><?php echo e($stat['value']); ?></strong>
                        <span><?php echo e($stat['label']); ?></span>
                        <small><?php echo e($stat['helper']); ?></small>
                    </div>
                </a>
            <?php else: ?>
                <div class="dashboard-kpi-card dashboard-access-muted" aria-disabled="true" title="Access not granted for your role">
                    <div class="dashboard-kpi-icon">🔒</div>
                    <div>
                        <strong>—</strong>
                        <span><?php echo e($stat['label']); ?></span>
                        <small>Access not granted</small>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="dashboard-grid dashboard-grid-wide">
        <?php if (isset($component)) { $__componentOriginal315c571ce40dc0c12ed885ba8a594408 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal315c571ce40dc0c12ed885ba8a594408 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => 'Financial & Fuel Overview','class' => 'dashboard-panel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Financial & Fuel Overview','class' => 'dashboard-panel']); ?>
            <div class="finance-grid">
                <div class="finance-box"><small>Total Trip Cost</small><b>৳ <?php echo e(number_format($finance['trip_cost'] ?? 0)); ?></b></div>
                <div class="finance-box"><small>Driver + Employee Salary</small><b>৳ <?php echo e(number_format($finance['payroll'] ?? 0)); ?></b></div>
                <div class="finance-box"><small>Attendance Distance</small><b><?php echo e(number_format($finance['attendance_km'] ?? 0)); ?> km</b></div>
                <div class="finance-box">
                    <small>Latest Fuel Rate</small>
                    <b><?php echo e($latestFuel ? (($latestFuel['fuelType'] ?? 'Fuel') . ' ৳' . number_format((float) ($latestFuel['price'] ?? 0), 2)) : '-'); ?></b>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $attributes = $__attributesOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $component = $__componentOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__componentOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal315c571ce40dc0c12ed885ba8a594408 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal315c571ce40dc0c12ed885ba8a594408 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => 'Operational Alerts','class' => 'dashboard-panel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Operational Alerts','class' => 'dashboard-panel']); ?>
            <div class="warning-list">
                <?php $__currentLoopData = $warnings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warning): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="warning-row">
                        <div><b><?php echo e($warning['title']); ?></b><span><?php echo e($warning['description']); ?></span></div>
                        <strong><?php echo e($warning['value']); ?></strong>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $attributes = $__attributesOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $component = $__componentOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__componentOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
    </div>

    <div class="dashboard-grid dashboard-grid-wide">
        <?php if (isset($component)) { $__componentOriginal315c571ce40dc0c12ed885ba8a594408 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal315c571ce40dc0c12ed885ba8a594408 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => 'Recent Trips','class' => 'dashboard-panel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Recent Trips','class' => 'dashboard-panel']); ?>
            <div class="compact-list">
                <?php if(!($access['trips'] ?? false)): ?>
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                <?php else: ?>
                <?php $__empty_1 = true; $__currentLoopData = ($recent['trips'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('fleet.trips')); ?>" class="compact-row">
                        <div class="compact-icon">🧭</div>
                        <div><b><?php echo e($trip['tripId'] ?? '-'); ?> · <?php echo e($trip['purpose'] ?? 'Trip'); ?></b><span><?php echo e($trip['vehicle'] ?? '-'); ?> / <?php echo e($trip['driver'] ?? '-'); ?></span></div>
                        <?php ($tripBalance = (float) ($trip['balanceDue'] ?? max(0, (float) ($trip['totalCost'] ?? 0) - (float) ($trip['paidAmount'] ?? 0)))); ?>
                        <span class="badge <?php echo e($tripBalance <= 0.009 ? 'ok' : 'warn'); ?>"><?php echo e($tripBalance <= 0.009 ? 'Paid' : ('Balance ৳' . number_format($tripBalance, 2))); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty compact-empty">No trips found.</div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $attributes = $__attributesOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $component = $__componentOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__componentOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal315c571ce40dc0c12ed885ba8a594408 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal315c571ce40dc0c12ed885ba8a594408 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => 'Recent Vehicles','class' => 'dashboard-panel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Recent Vehicles','class' => 'dashboard-panel']); ?>
            <div class="compact-list">
                <?php if(!($access['vehicles'] ?? false)): ?>
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                <?php else: ?>
                <?php $__empty_1 = true; $__currentLoopData = ($recent['vehicles'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vehicle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('fleet.vehicles')); ?>" class="compact-row">
                        <div class="compact-icon">🚗</div>
                        <div><b><?php echo e($vehicle['name'] ?? '-'); ?></b><span><?php echo e($vehicle['id'] ?? '-'); ?> / <?php echo e($vehicle['regNo'] ?? '-'); ?></span></div>
                        <span class="badge soft"><?php echo e($vehicle['category'] ?? 'Vehicle'); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty compact-empty">No vehicles found.</div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $attributes = $__attributesOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $component = $__componentOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__componentOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
    </div>

    <div class="dashboard-grid dashboard-grid-wide">
        <?php if (isset($component)) { $__componentOriginal315c571ce40dc0c12ed885ba8a594408 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal315c571ce40dc0c12ed885ba8a594408 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => 'Recent Drivers','class' => 'dashboard-panel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Recent Drivers','class' => 'dashboard-panel']); ?>
            <div class="compact-list">
                <?php if(!($access['drivers'] ?? false)): ?>
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                <?php else: ?>
                <?php $__empty_1 = true; $__currentLoopData = ($recent['drivers'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('fleet.drivers')); ?>" class="compact-row">
                        <div class="compact-icon">🧑‍✈️</div>
                        <div><b><?php echo e($driver['fullName'] ?? '-'); ?></b><span><?php echo e($driver['driverId'] ?? '-'); ?> / <?php echo e($driver['contact'] ?? '-'); ?></span></div>
                        <span class="badge <?php echo e(($driver['status'] ?? '') === 'Active' ? 'ok' : 'soft'); ?>"><?php echo e($driver['status'] ?? '-'); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty compact-empty">No drivers found.</div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $attributes = $__attributesOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $component = $__componentOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__componentOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal315c571ce40dc0c12ed885ba8a594408 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal315c571ce40dc0c12ed885ba8a594408 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => 'Recent Clients','class' => 'dashboard-panel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Recent Clients','class' => 'dashboard-panel']); ?>
            <div class="compact-list">
                <?php if(!($access['clients'] ?? false)): ?>
                    <div class="empty compact-empty">🔒 Access not granted for your role.</div>
                <?php else: ?>
                <?php $__empty_1 = true; $__currentLoopData = ($recent['clients'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a href="<?php echo e(route('fleet.clients')); ?>" class="compact-row">
                        <div class="compact-icon">🏢</div>
                        <div><b><?php echo e($client['clientName'] ?? '-'); ?></b><span><?php echo e($client['clientId'] ?? '-'); ?> / <?php echo e($client['phone'] ?? '-'); ?></span></div>
                        <span class="badge <?php echo e(($client['status'] ?? '') === 'Active' ? 'ok' : 'warn'); ?>"><?php echo e($client['status'] ?? '-'); ?></span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty compact-empty">No clients found.</div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $attributes = $__attributesOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__attributesOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal315c571ce40dc0c12ed885ba8a594408)): ?>
<?php $component = $__componentOriginal315c571ce40dc0c12ed885ba8a594408; ?>
<?php unset($__componentOriginal315c571ce40dc0c12ed885ba8a594408); ?>
<?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/dashboard.blade.php ENDPATH**/ ?>