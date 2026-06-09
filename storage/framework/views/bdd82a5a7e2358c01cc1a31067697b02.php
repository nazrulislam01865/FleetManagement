<?php $__env->startSection('title', 'Vehicles | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Vehicles'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section">
    <div id="vehicleAddPage">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Add Vehicle']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Add Vehicle']])]); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <button type="button" class="btn light" data-page-target="vehicleListPage">← Vehicle List</button>
             <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Add Vehicle','subtitle' => 'A simple guided form for non-technical users. Fill basic information first, then fuel, documents and driver assignment.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Add Vehicle','subtitle' => 'A simple guided form for non-technical users. Fill basic information first, then fuel, documents and driver assignment.']); ?>

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

        <div class="layout">
            <div>
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>1. Basic Vehicle Information</h2>
                        </div>
                    </div>
                    <div class="grid3">
                        <div class="field"><label for="vehicleId">Vehicle ID <span class="req">*</span></label><input id="vehicleId" readonly></div>
                        <div class="field"><label for="vehicleName">Vehicle Name <span class="req">*</span></label><input id="vehicleName" placeholder="Example: Dhaka Pickup 01" required></div>
                        <div class="field"><label for="regNo">Registration Number <span class="req">*</span></label><input id="regNo" maxlength="14" pattern="[A-Za-z]{3}-[A-Za-z]{2}-[0-9]{2}-[0-9]{4}" placeholder="Example: DHA-AB-12-3456" required></div>
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'vendor','label' => 'Vendor / Owner','options' => $fleetman['options']['vehicle_vendors'],'placeholder' => 'Select vendor/owner']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'vendor','label' => 'Vendor / Owner','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['vehicle_vendors']),'placeholder' => 'Select vendor/owner']); ?>
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
                        <div class="field"><label for="model">Model <span class="req">*</span></label><input id="model" placeholder="Example: Toyota Hiace 2021" required></div>
                        <div class="field"><label for="color">Color</label><input id="color" placeholder="Example: White"></div>
                        <div class="field"><label for="engineNo">Engine Number <span class="req">*</span></label><input id="engineNo" maxlength="17" pattern="[A-Za-z0-9]{17}" placeholder="Exactly 17 letters or digits" required></div>
                        <div class="field"><label for="mileage">Regular Mileage Target</label><input id="mileage" type="number" placeholder="Example: 8.5"></div>
                        <div class="field"><label for="odo">Current Odometer</label><input id="odo" type="number" placeholder="Example: 45230"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>2. Vehicle Type & Usage</h2>
                        </div>
                    </div>
                    <div class="grid">
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'category','label' => 'Vehicle Category','options' => $fleetman['options']['vehicle_categories'],'placeholder' => 'Select category','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'category','label' => 'Vehicle Category','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['vehicle_categories']),'placeholder' => 'Select category','required' => true]); ?>
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
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'subCategory','label' => 'Vehicle Sub-category','options' => [],'placeholder' => 'Select sub-category']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'subCategory','label' => 'Vehicle Sub-category','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([]),'placeholder' => 'Select sub-category']); ?>
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
                    </div>
                    <div class="field" style="margin-top:14px">
                        <label>Usage Type <span class="req">*</span></label>
                        <div class="choice-grid">
                            <?php $__currentLoopData = $fleetman['options']['usage_types']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="choice">
                                    <input type="radio" name="usage" value="<?php echo e($usage['value']); ?>">
                                    <span><?php echo e($usage['title']); ?></span>
                                    <small><?php echo e($usage['description']); ?></small>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="grid" style="margin-top:16px">
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'rentalType','label' => 'Rental Type','options' => ['With Driver', 'Without Driver'],'placeholder' => 'Select rental type','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'rentalType','label' => 'Rental Type','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['With Driver', 'Without Driver']),'placeholder' => 'Select rental type','required' => true]); ?>
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
                        <div class="field searchable">
                            <div class="search-label">
                                <label for="driver">Driver <span class="req">*</span></label>
                                <span class="search-tag">Searchable</span>
                            </div>
                            <input id="driver" list="vehicleDriverList" placeholder="Type to search and select a driver" autocomplete="off" required aria-required="true">
                            <datalist id="vehicleDriverList">
                                <?php $__currentLoopData = $fleetman['options']['drivers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driverOptionValue => $driverOptionLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $driverValue = is_int($driverOptionValue) ? $driverOptionLabel : $driverOptionValue;
                                        $driverLabel = is_array($driverOptionLabel) ? ($driverOptionLabel['label'] ?? $driverValue) : $driverOptionLabel;
                                    ?>
                                    <option value="<?php echo e($driverValue); ?>"><?php echo e($driverLabel); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </datalist>
                        </div>
                    </div>
                    <div id="driverPaymentFields" class="grid" style="margin-top:16px">
                        <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'driverPaymentAmount','label' => 'Driver Payment Amount','type' => 'number','min' => '0','step' => '0.01','placeholder' => '0.00','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'driverPaymentAmount','label' => 'Driver Payment Amount','type' => 'number','min' => '0','step' => '0.01','placeholder' => '0.00','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $attributes = $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $component = $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'driverPaymentCycle','label' => 'Driver Payment Cycle','options' => $fleetman['options']['rental_payment_cycles'],'placeholder' => 'Select payment cycle','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'driverPaymentCycle','label' => 'Driver Payment Cycle','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['rental_payment_cycles']),'placeholder' => 'Select payment cycle','required' => true]); ?>
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
                    </div>
                    <div class="grid3" style="margin-top:16px">
                        <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'vehicleRentalAmount','label' => 'Vehicle Rental Amount','type' => 'number','min' => '0','step' => '0.01','placeholder' => '0.00','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'vehicleRentalAmount','label' => 'Vehicle Rental Amount','type' => 'number','min' => '0','step' => '0.01','placeholder' => '0.00','required' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $attributes = $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $component = $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'vehiclePaymentCycle','label' => 'Vehicle Payment Cycle','options' => $fleetman['options']['rental_payment_cycles'],'placeholder' => 'Select payment cycle','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'vehiclePaymentCycle','label' => 'Vehicle Payment Cycle','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['rental_payment_cycles']),'placeholder' => 'Select payment cycle','required' => true]); ?>
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
                        <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'totalRentalAmount','label' => 'Total Rental Amount','type' => 'number','min' => '0','step' => '0.01','value' => '0.00','readonly' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'totalRentalAmount','label' => 'Total Rental Amount','type' => 'number','min' => '0','step' => '0.01','value' => '0.00','readonly' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $attributes = $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523)): ?>
<?php $component = $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523; ?>
<?php unset($__componentOriginal8e448d98e7f6e76a56b5afe0e1522523); ?>
<?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>3. Fuel Setup</h2>
                        </div>
                        <button type="button" class="btn secondary" id="addFuelRowBtn">+ Add fuel</button>
                    </div>
                    <div id="vehicleFuelRows"></div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>4. Documents</h2>
                        </div>
                        <button type="button" class="btn secondary" id="addDocRowBtn">+ Add document</button>
                    </div>
                    <div id="vehicleDocRows"></div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>5. Photo & Notes</h2>
                        </div>
                    </div>
                    <div class="grid">
                        <div class="field"><label for="image">Vehicle Image</label><input id="image" type="file" accept="image/jpeg,image/png,image/webp"><input id="vehicleImageData" type="hidden"><div class="temp-upload-progress hidden" id="vehicleImageProgress"><div class="temp-upload-progress-track"><div class="temp-upload-progress-bar"></div></div><small class="temp-upload-progress-label"></small></div><div class="upload-meta" id="vehicleImageUploadInfo"></div><div class="hint">Allowed: JPG, PNG or WEBP. Maximum size: 100 KB.</div></div>
                        <div class="field"><label for="notes">Notes</label><textarea id="notes" placeholder="Any special note about vehicle condition or assignment"></textarea></div>
                    </div>
                </div>

                <div class="save-bar">
                    <button type="button" class="btn light" id="clearVehicleBtn">Clear</button>
                    <button type="button" class="btn primary" id="saveVehicleBtn">Save Vehicle & Go to List</button>
                </div>
            </div>

        </div>
    </div>

    <div id="vehicleListPage" class="hidden">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Vehicle List']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Vehicle List']])]); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <button type="button" class="btn light" id="exportVehiclesBtn">⬇ Export CSV</button>
                <?php if(data_get($fleetman, 'auth.pageAccess.canManage')): ?>
                    <a href="<?php echo e(route('fleet.vehicles', ['action' => 'add'])); ?>" class="btn primary" id="addVehicleFromListBtn">＋ Add Vehicle</a>
                <?php else: ?>
                    <span class="btn primary rbac-control-muted" id="addVehicleFromListBtn" aria-disabled="true" tabindex="-1" title="Your role has read-only access to this module." data-rbac-disabled="true">🔒 Add Vehicle</span>
                <?php endif; ?>
             <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Vehicle List','subtitle' => 'All created vehicles will appear here. Search, filter, view documents and check fuel setup quickly.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Vehicle List','subtitle' => 'All created vehicles will appear here. Search, filter, view documents and check fuel setup quickly.']); ?>
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

        <div class="kpi">
            <div class="card"><strong id="vehicleKpiTotal">0</strong><span>Total vehicles</span></div>
            <div class="card"><strong id="vehicleKpiActive">0</strong><span>Active vehicles</span></div>
            <div class="card"><strong id="vehicleKpiDocs">0</strong><span>Expiring documents</span></div>
            <div class="card"><strong id="vehicleKpiFuel">0</strong><span>Multi-fuel vehicles</span></div>
        </div>

        <div class="card">
            <div class="filters">
                <input id="vehicleSearch" placeholder="Search by vehicle, registration, driver">
                <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'vehicleFilterCategory','label' => '','options' => $fleetman['options']['vehicle_categories'],'placeholder' => 'All categories']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'vehicleFilterCategory','label' => '','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['vehicle_categories']),'placeholder' => 'All categories']); ?>
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
                <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'vehicleFilterFuel','label' => '','options' => $fleetman['options']['fuel_types'],'placeholder' => 'All fuel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'vehicleFilterFuel','label' => '','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['fuel_types']),'placeholder' => 'All fuel']); ?>
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
                <select id="vehicleFilterStatus"><option value="">All status</option><option>Active</option><option>Needs document review</option></select>
                <button type="button" class="btn light" id="clearVehicleFiltersBtn">Clear</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Vehicle</th><th>Registration</th><th>Category</th><th>Fuel Setup</th><th>Driver</th><th>Documents</th><th>Rent</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="vehicleTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/vehicles.blade.php ENDPATH**/ ?>