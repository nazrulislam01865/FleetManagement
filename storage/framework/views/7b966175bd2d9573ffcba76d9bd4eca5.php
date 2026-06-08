<?php $__env->startSection('title', 'Add Fuel | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Fuel Entry'); ?>

<?php $__env->startSection('content'); ?>
<div class="fuel-recharge-dynamic-page">
    <div id="rechargeAddPage" class="page-section recharge-page">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Add Recharge']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Add Recharge']])]); ?>
             <?php $__env->slot('actions', null, []); ?> <button type="button" class="btn light" data-page-target="rechargeListPage">← Recharge List</button> <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['class' => 'desktop-title','title' => 'Add Recharge','subtitle' => 'Select contract, load the assigned vehicle, take required photos, enter liquid fuel in liters or CNG/Gas/LPG cost in Taka, confirm ODO, and submit. Compatible stations load automatically.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'desktop-title','title' => 'Add Recharge','subtitle' => 'Select contract, load the assigned vehicle, take required photos, enter liquid fuel in liters or CNG/Gas/LPG cost in Taka, confirm ODO, and submit. Compatible stations load automatically.']); ?>
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

    <div class="help-card">
        <div>
            <h1>Fuel Entry</h1>
        </div>
        <div class="capture-counter"><small>Photo Status</small><div id="photoCount">0 / 3 required</div></div>
    </div>

    <section class="step-card">
        <div class="step-head"><div class="step-no">1</div><div><h2>Select contract and vehicle</h2></div></div>
        <div class="grid">
            <div class="field">
                <label for="contractSelect">Contract <span class="req">*</span></label>
                <select id="contractSelect" required aria-required="true">
                    <option value="">- Select contract -</option>
                    <?php $__currentLoopData = ($fleetman['contracts'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($contract['id']); ?>"><?php echo e($contract['label']); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="field searchable">
                <div class="search-label">
                    <label for="vehicleSelect">Vehicle <span class="req">*</span></label>
                    <span class="search-tag">Searchable &amp; Filtered</span>
                </div>
                <input
                    id="vehicleSelect"
                    list="vehicleSelectList"
                    placeholder="Select contract first"
                    autocomplete="off"
                    required
                    aria-required="true"
                    disabled
                >
                <datalist id="vehicleSelectList"></datalist>
            </div>
        </div>
        <div class="vehicle-note" id="vehicleSetupNote">Select a contract first. Vehicle, driver, fuel setup and latest ODO will load from saved records.</div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">2</div><div><h2>Take photos</h2></div></div>
        <div class="photo-list" id="photoList"></div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">3</div><div><h2>Enter fuel amount</h2></div></div>
        <div class="fuel-panel">
            <div class="fuel-panel-head"><div><b>Main Fuel <span class="req">*</span></b></div><span class="fuel-chip required">Required</span></div>
            <div class="grid">
                <div class="field"><label for="primaryFuelName">Main Fuel Name <span class="req">*</span></label><input id="primaryFuelName" readonly required aria-required="true" value=""></div>
                <div class="field">
                    <label for="primaryStation">Primary Fuel Station <span class="req">*</span></label>
                    <select id="primaryStation" disabled required aria-required="true">
                        <option value="">- Select vehicle first -</option>
                    </select>
                    <small class="upload-meta" id="primaryStationHint" hidden></small>
                </div>
                <div class="field">
                    <label for="primaryQty" id="primaryQtyLabel">Quantity (Liter) <span class="req">*</span></label>
                    <input id="primaryQty" type="number" min="0.01" step="0.01" value="" inputmode="decimal" placeholder="Enter liters" required aria-required="true">
                    <small class="upload-meta" id="primaryQtyHint" hidden></small>
                </div>
                <div class="field" id="primaryRateField">
                    <label for="primaryRate" id="primaryRateLabel">Rate per Liter</label>
                    <input id="primaryRate" readonly value="">
                    <small class="upload-meta" id="primaryRateHint" hidden></small>
                </div>
                <div class="field"><label for="primaryAmount" id="primaryAmountLabel">Calculated Amount</label><input id="primaryAmount" readonly value="৳ 0.00"></div>
            </div>
        </div>
        <div class="secondary-toggle">
            <label class="switch-line"><input id="hasSecondaryFuel" type="checkbox"><span class="switch-ui"></span><strong>Add second fuel, like CNG or LPG</strong></label>
        </div>
        <div class="fuel-panel secondary" id="secondaryFuelBlock" style="display:none">
            <div class="fuel-panel-head"><div><b>Second Fuel</b></div><span class="fuel-chip optional">Optional</span></div>
            <div class="grid">
                <div class="field"><label for="secondaryFuelName">Second Fuel Name</label><input id="secondaryFuelName" readonly value=""></div>
                <div class="field">
                    <label for="secondaryStation">Secondary Fuel Station</label>
                    <select id="secondaryStation" disabled>
                        <option value="">- Select second fuel first -</option>
                    </select>
                    <small class="upload-meta" id="secondaryStationHint" hidden></small>
                </div>
                <div class="field">
                    <label for="secondaryQty" id="secondaryQtyLabel">Quantity (Liter)</label>
                    <input id="secondaryQty" type="number" min="0" step="0.01" value="0" inputmode="decimal" placeholder="Enter liters">
                    <small class="upload-meta" id="secondaryQtyHint" hidden></small>
                </div>
                <div class="field" id="secondaryRateField">
                    <label for="secondaryRate" id="secondaryRateLabel">Rate per Liter</label>
                    <input id="secondaryRate" readonly value="">
                    <small class="upload-meta" id="secondaryRateHint" hidden></small>
                </div>
                <div class="field"><label for="secondaryAmount" id="secondaryAmountLabel">Calculated Amount</label><input id="secondaryAmount" readonly value="৳ 0.00"></div>
            </div>
        </div>
        <div class="amount-strip"><div><small>Total Fuel Cost</small><b id="totalAmount">৳ 0.00</b></div><button class="btn light" type="button" id="recalculateFuelRechargeBtn">Recalculate</button></div>
    </section>

    <section class="step-card">
        <div class="step-head"><div class="step-no">4</div><div><h2>ODO reading and submit</h2></div></div>
        <div class="grid">
            <div class="field"><label for="startKm">Start KM <span class="req">*</span></label><input id="startKm" type="number" min="0" step="1" required aria-required="true" value="" placeholder="Starting ODO reading" inputmode="numeric"></div>
            <div class="field"><label for="endKm">End KM <span class="req">*</span></label><input id="endKm" type="number" min="0" step="1" value="" placeholder="Latest ODO reading" inputmode="numeric" required aria-required="true"></div>
        </div>
        <div class="grid4" style="margin-top:12px">
            <div class="field"><label for="totalKm">Total KM</label><input id="totalKm" readonly value=""></div>
            <div class="field"><label for="mileage">Mileage (KM/L)</label><input id="mileage" readonly value=""></div>
            <div class="field"><label for="tkKm">TK (KM)</label><input id="tkKm" readonly value=""></div>
            <div class="field"><label for="submittedBy">Submitted By <span class="req">*</span></label><input id="submittedBy" readonly required aria-required="true" value="<?php echo e($account['name'] ?? 'Logged-in User'); ?>"></div>
        </div>
        <div class="field" style="margin-top:12px"><label for="rechargeRemarks">Remarks</label><textarea id="rechargeRemarks" placeholder="Write any note about the fuel recharge."></textarea></div>
        <div class="bottom-submit"><button class="btn light" id="resetRechargeBtn" type="button">Reset Form</button><button class="btn secondary" id="draftRechargeBtn" type="button">Save Draft</button><button class="btn green" id="submitRechargeBtn" type="button">Submit</button></div>
    </section>
    </div>

    <div id="rechargeListPage" class="hidden" style="max-width: 100%; padding: 0 10px;">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Recharge List']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Recharge List']])]); ?>
             <?php $__env->slot('actions', null, []); ?> <button type="button" class="btn light" id="exportRechargesBtn">⬇ Export CSV</button> <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Recharge List','subtitle' => 'Clean list view with quick search, filters, and sample data. Designed to replace the spreadsheet-like screen with something easier to scan and use.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Recharge List','subtitle' => 'Clean list view with quick search, filters, and sample data. Designed to replace the spreadsheet-like screen with something easier to scan and use.']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $attributes = $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $component = $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>

        <div class="kpi">
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'rechargeKpiTotal','label' => 'Total Entries']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'rechargeKpiTotal','label' => 'Total Entries']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'rechargeKpiSubmitted','label' => 'Submitted']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'rechargeKpiSubmitted','label' => 'Submitted']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'rechargeKpiDraft','label' => 'Drafts']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'rechargeKpiDraft','label' => 'Drafts']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'rechargeKpiMileage','label' => 'Average Mileage']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'rechargeKpiMileage','label' => 'Average Mileage']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
        </div>

        <div class="card">
            <div class="filters">
                <input id="rechargeSearch" placeholder="Search by entry ID, contract, vehicle, driver, or station">
                <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'rechargeFilterStatus','label' => '','options' => ['Draft', 'Submitted'],'placeholder' => 'All Status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'rechargeFilterStatus','label' => '','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['Draft', 'Submitted']),'placeholder' => 'All Status']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4b244ece64768724078120db372595a2)): ?>
<?php $attributes = $__attributesOriginal4b244ece64768724078120db372595a2; ?>
<?php unset($__attributesOriginal4b244ece64768724078120db372595a2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4b244ece64768724078120db372595a2)): ?>
<?php $component = $__componentOriginal4b244ece64768724078120db372595a2; ?>
<?php unset($__componentOriginal4b244ece64768724078120db372595a2); ?>
<?php endif; ?>
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyRechargeFiltersBtn">Apply</button><button type="button" class="btn light" id="clearRechargeFiltersBtn">Clear</button></div>
            </div>

            <div class="table-wrap wide-table" style="max-height: calc(100vh - 300px); overflow-y: auto;">
                <table>
                    <thead style="position: sticky; top: 0; z-index: 10; background: #f8fafc; outline: 1px solid #edf0f5; outline-offset: -1px;">
                        <tr>
                            <th>Entry</th>
                            <th>Date</th>
                            <th>Contract</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Primary Fuel</th>
                            <th>Primary Station</th>
                            <th>Secondary Fuel</th>
                            <th>Secondary Station</th>
                            <th>Start KM</th>
                            <th>End KM</th>
                            <th>Total KM</th>
                            <th>Mileage</th>
                            <th>Images Taken?</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rechargeTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/fuel-recharge.blade.php ENDPATH**/ ?>