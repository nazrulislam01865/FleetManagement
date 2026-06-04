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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Add New Vehicle','subtitle' => 'A simple guided form for non-technical users. Fill basic information first, then fuel, documents and driver assignment.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Add New Vehicle','subtitle' => 'A simple guided form for non-technical users. Fill basic information first, then fuel, documents and driver assignment.']); ?>
             <?php $__env->slot('action', null, []); ?> 
                <button type="button" class="btn secondary" id="loadVehicleSampleBtn">Use sample data</button>
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

        <div class="layout">
            <div>
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>1. Basic Vehicle Information</h2>
                            <p>Keep names familiar. Use registration number exactly as written on the vehicle papers.</p>
                        </div>
                    </div>
                    <div class="grid3">
                        <div class="field"><label for="vehicleId">Vehicle ID <span class="req">*</span></label><input id="vehicleId" readonly></div>
                        <div class="field"><label for="vehicleName">Vehicle Name <span class="req">*</span></label><input id="vehicleName" placeholder="Example: Dhaka Pickup 01"></div>
                        <div class="field"><label for="regNo">Registration Number <span class="req">*</span></label><input id="regNo" placeholder="Example: DHAKA-METRO-TA-11-2345"></div>
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'vendor','label' => 'Vendor / Owner','options' => $fleetman['options']['vendors'],'placeholder' => 'Select vendor','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'vendor','label' => 'Vendor / Owner','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['vendors']),'placeholder' => 'Select vendor','required' => true]); ?>
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
                        <div class="field"><label for="model">Model <span class="req">*</span></label><input id="model" placeholder="Example: Toyota Hiace 2021"></div>
                        <div class="field"><label for="color">Color</label><input id="color" placeholder="Example: White"></div>
                        <div class="field"><label for="engineNo">Engine Number <span class="req">*</span></label><input id="engineNo" placeholder="Example: ENG-78219"></div>
                        <div class="field"><label for="mileage">Regular Mileage Target</label><input id="mileage" type="number" placeholder="Example: 8.5"><div class="hint">Km per litre/Kg. Used later for mileage warning.</div></div>
                        <div class="field"><label for="odo">Current Odometer</label><input id="odo" type="number" placeholder="Example: 45230"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>2. Vehicle Type & Usage</h2>
                            <p>Use friendly grouped options instead of a very long checkbox list.</p>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'driver','label' => 'Driver','options' => $fleetman['options']['drivers'],'placeholder' => 'None']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'driver','label' => 'Driver','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['drivers']),'placeholder' => 'None']); ?>
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
                        <div class="field"><label for="rent">Monthly Rent / Cost <span class="req">*</span></label><input id="rent" type="number" value="0"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>3. Fuel Setup</h2>
                            <p>Add primary, secondary and tertiary fuel. Primary fuel is required.</p>
                        </div>
                        <button type="button" class="btn secondary" id="addFuelRowBtn">+ Add fuel</button>
                    </div>
                    <div id="vehicleFuelRows"></div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>4. Documents</h2>
                            <p>Write document names and expiry dates. Add as many as needed.</p>
                        </div>
                        <button type="button" class="btn secondary" id="addDocRowBtn">+ Add document</button>
                    </div>
                    <div id="vehicleDocRows"></div>
                </div>

                <div class="card">
                    <div class="section-head">
                        <div>
                            <h2>5. Photo & Notes</h2>
                            <p>Photo is optional in prototype. In real system it can be mandatory based on policy.</p>
                        </div>
                    </div>
                    <div class="grid">
                        <div class="field"><label for="image">Vehicle Image</label><input id="image" type="file" accept="image/*"><input id="vehicleImageData" type="hidden"><small class="upload-meta" id="vehicleImageUploadInfo">Choose image. It will be stored after Save Vehicle.</small><div class="hint">Allowed: jpg, png, webp. Recommended size below 5 MB.</div></div>
                        <div class="field"><label for="notes">Notes</label><textarea id="notes" placeholder="Any special note about vehicle condition or assignment"></textarea></div>
                    </div>
                </div>

                <div class="save-bar">
                    <button type="button" class="btn light" id="clearVehicleBtn">Clear</button>
                    <button type="button" class="btn primary" id="saveVehicleBtn">Save Vehicle & Go to List</button>
                </div>
            </div>

            <aside>
                <div class="side-note">
                    <h3>Design changes made</h3>
                    <ul>
                        <li>Long vehicle category list changed to category + sub-category.</li>
                        <li>Fuel supports primary, secondary and tertiary.</li>
                        <li>Documents can be added one by one with expiry and reminder.</li>
                        <li>Important fields grouped into small steps.</li>
                        <li>Plain labels, larger fields and clearer actions.</li>
                    </ul>
                </div>
                <div class="side-note">
                    <h3>Recommended validation</h3>
                    <ul>
                        <li>Registration number should be unique.</li>
                        <li>One fuel must be marked Primary.</li>
                        <li>Expiry date warning before 30 days.</li>
                        <li>Odometer cannot be lower than last reading.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    <div id="vehicleListPage" class="hidden">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Vehicles']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Vehicles']])]); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <button type="button" class="btn primary" id="newVehicleBtn">+ Add Vehicle</button>
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
             <?php $__env->slot('action', null, []); ?> 
                <button type="button" class="btn secondary" id="exportVehiclesBtn">Export CSV</button>
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