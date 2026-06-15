<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => 'Report Result', 'subtitle' => 'Only this report box has horizontal scrolling.', 'tableMinWidth' => '1800px']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['title' => 'Report Result', 'subtitle' => 'Only this report box has horizontal scrolling.', 'tableMinWidth' => '1800px']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<section <?php echo e($attributes->merge(['class' => 'report-shell'])); ?>>
    <div class="report-toolbar">
        <div>
            <h2><?php echo e($title); ?></h2>
            <p><?php echo e($subtitle); ?></p>
        </div>

    </div>

    <div class="fixed-report-box">
        <div class="table-scroller">
            <table style="min-width: <?php echo e($tableMinWidth); ?>">
                <?php echo e($table); ?>

            </table>
        </div>
        <div class="report-pagination">
            <div id="pageInfo"></div>
            <div class="page-btns">
                <button class="mini-btn report-prev-page" type="button">Previous</button>
                <span id="pageNumbers"></span>
                <button class="mini-btn report-next-page" type="button">Next</button>
            </div>
        </div>
    </div>

    <div class="mobile-cards" id="mobileCards"></div>
</section>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/components/fleetman/report-shell.blade.php ENDPATH**/ ?>