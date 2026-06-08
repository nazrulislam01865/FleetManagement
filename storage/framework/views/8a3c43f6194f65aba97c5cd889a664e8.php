<?php $__env->startSection('title', 'Monthly Driver & Fuel Summary Report | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Monthly Report'); ?>

<?php $__env->startSection('content'); ?>
<div class="report-page" data-report-page="monthly">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Reports', 'route' => 'fleet.reports'], ['label' => 'Monthly Driver & Fuel Summary']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Reports', 'route' => 'fleet.reports'], ['label' => 'Monthly Driver & Fuel Summary']])]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Monthly Driver & Fuel Summary Report','subtitle' => 'On screen, users see a monthly summary list. The Excel export downloads a wide date-wise monthly report from the first day to the last day of the selected month, similar to the weekly Excel format.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Monthly Driver & Fuel Summary Report','subtitle' => 'On screen, users see a monthly summary list. The Excel export downloads a wide date-wise monthly report from the first day to the last day of the selected month, similar to the weekly Excel format.']); ?>
         <?php $__env->slot('action', null, []); ?> 
            <div class="report-title-actions">
                <button class="btn green" type="button" data-report-export="monthly-datewise-excel">⬇ Export Date-wise Monthly Excel</button>
                <button class="btn secondary" type="button" data-report-export="csv">⬇ Export Summary CSV</button>
            </div>
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

    <?php if (isset($component)) { $__componentOriginalbaa57ff6e3263e46ce69c1edc04926fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbaa57ff6e3263e46ce69c1edc04926fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.report-filter-card','data' => ['title' => 'Report Filters','subtitle' => 'Select a month and optional filters. The screen list stays summarized; the exported Excel contains all date-wise columns.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.report-filter-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Report Filters','subtitle' => 'Select a month and optional filters. The screen list stays summarized; the exported Excel contains all date-wise columns.']); ?>
        <div class="report-filter-grid monthly-filter-grid">
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="monthFilter">Month</label><span class="search-tag">Searchable</span></div><input id="monthFilter" list="monthFilterList" placeholder="Select month" autocomplete="off"><datalist id="monthFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="contractFilter">Contract</label><span class="search-tag">Searchable</span></div><input id="contractFilter" list="contractFilterList" placeholder="All contracts" autocomplete="off"><datalist id="contractFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="vehicleFilter">Car / Vehicle</label><span class="search-tag">Filtered</span></div><input id="vehicleFilter" list="vehicleFilterList" placeholder="All vehicles" autocomplete="off"><datalist id="vehicleFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="driverFilter">Driver</label><span class="search-tag">Filtered</span></div><input id="driverFilter" list="driverFilterList" placeholder="All drivers" autocomplete="off"><datalist id="driverFilterList"></datalist></div>
            <div class="field searchable report-searchable-field"><div class="search-label"><label for="statusFilter">Status</label><span class="search-tag">Searchable</span></div><input id="statusFilter" list="statusFilterList" placeholder="All statuses" autocomplete="off"><datalist id="statusFilterList"></datalist></div>
            <div class="field"><label for="pageSize">Rows per page</label><select id="pageSize"><option value="10">Load 10 rows</option><option value="20">Load 20 rows</option><option value="30">Load 30 rows</option><option value="50">Load 50 rows</option></select></div>
        </div>
        <div class="action-row">
            <button class="btn primary" type="button" data-report-apply>Apply Report</button>
            <button class="btn light" type="button" data-report-reset>Clear Filters</button>
        </div>
        <div id="progressWrap" class="report-progress"><div class="bar-outer"><div id="barInner" class="bar-inner"></div></div><div id="progressText" class="progress-text">Preparing date-wise monthly Excel...</div></div>
        <div class="note-panel"><b>Excel export format:</b> Entry ID, Contract, Car, Driver, Total Hours, then date-wise columns for every day of the selected month: Diesel (L), CNG/LPG Cost, Octane (L), then monthly totals, Tk(KM), and odometer summary.</div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbaa57ff6e3263e46ce69c1edc04926fa)): ?>
<?php $attributes = $__attributesOriginalbaa57ff6e3263e46ce69c1edc04926fa; ?>
<?php unset($__attributesOriginalbaa57ff6e3263e46ce69c1edc04926fa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbaa57ff6e3263e46ce69c1edc04926fa)): ?>
<?php $component = $__componentOriginalbaa57ff6e3263e46ce69c1edc04926fa; ?>
<?php unset($__componentOriginalbaa57ff6e3263e46ce69c1edc04926fa); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.report-kpis','data' => ['items' => [
        ['id' => 'kpiRows', 'label' => 'Summary Rows'],
        ['id' => 'kpiDays', 'label' => 'Total Active Days'],
        ['id' => 'kpiHours', 'label' => 'Total Work Hours'],
        ['id' => 'kpiDiesel', 'label' => 'Total Diesel (L)'],
        ['id' => 'kpiGas', 'label' => 'Total CNG/LPG Cost'],
        ['id' => 'kpiKm', 'label' => 'Total KM'],
    ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.report-kpis'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
        ['id' => 'kpiRows', 'label' => 'Summary Rows'],
        ['id' => 'kpiDays', 'label' => 'Total Active Days'],
        ['id' => 'kpiHours', 'label' => 'Total Work Hours'],
        ['id' => 'kpiDiesel', 'label' => 'Total Diesel (L)'],
        ['id' => 'kpiGas', 'label' => 'Total CNG/LPG Cost'],
        ['id' => 'kpiKm', 'label' => 'Total KM'],
    ])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa)): ?>
<?php $attributes = $__attributesOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa; ?>
<?php unset($__attributesOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa)): ?>
<?php $component = $__componentOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa; ?>
<?php unset($__componentOriginal82eea7f6eef8dc37c1d6e2b3b2f8e9aa); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalb634d0c2cde516913b755de8cc7596a3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb634d0c2cde516913b755de8cc7596a3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.report-shell','data' => ['title' => 'Monthly Summary List','subtitle' => 'Only this report box has horizontal scrolling. Vertical space is fixed for 10 rows.','tableMinWidth' => '1900px']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.report-shell'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Monthly Summary List','subtitle' => 'Only this report box has horizontal scrolling. Vertical space is fixed for 10 rows.','table-min-width' => '1900px']); ?>
         <?php $__env->slot('table', null, []); ?> 
            <thead>
                <tr>
                    <th rowspan="2" class="sticky-left sticky-1">Summary ID</th>
                    <th rowspan="2" class="sticky-left sticky-2">Month</th>
                    <th rowspan="2" class="sticky-left sticky-3">Contract</th>
                    <th rowspan="2" class="sticky-left sticky-4">Car</th>
                    <th rowspan="2">Driver</th>
                    <th colspan="2" class="group-head">Usage Summary</th>
                    <th colspan="3" class="group-head">Fuel Summary</th>
                    <th colspan="5" class="group-head">Odometer Summary</th>
                    <th rowspan="2">Last Status</th>
                    <th rowspan="2">Submitted By</th>
                </tr>
                <tr>
                    <th class="sub-head">Active Days</th>
                    <th class="sub-head">Total Hours</th>
                    <th class="sub-head">Diesel (L)</th>
                    <th class="sub-head">CNG/LPG Cost (৳)</th>
                    <th class="sub-head">Octane (L)</th>
                    <th class="sub-head">Month Start KM</th>
                    <th class="sub-head">Month End KM</th>
                    <th class="sub-head">Total KM</th>
                    <th class="sub-head">Tk(KM)</th>
                    <th class="sub-head">Avg Mileage</th>
                </tr>
            </thead>
            <tbody id="tbody"></tbody>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb634d0c2cde516913b755de8cc7596a3)): ?>
<?php $attributes = $__attributesOriginalb634d0c2cde516913b755de8cc7596a3; ?>
<?php unset($__attributesOriginalb634d0c2cde516913b755de8cc7596a3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb634d0c2cde516913b755de8cc7596a3)): ?>
<?php $component = $__componentOriginalb634d0c2cde516913b755de8cc7596a3; ?>
<?php unset($__componentOriginalb634d0c2cde516913b755de8cc7596a3); ?>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    window.FLEETMAN.report = <?php echo json_encode($report, 15, 512) ?>;
</script>
<script src="<?php echo e(asset('js/fleetman-reports.js')); ?>?v=<?php echo e(filemtime(public_path('js/fleetman-reports.js'))); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/reports/monthly-driver-fuel.blade.php ENDPATH**/ ?>