<?php $__env->startSection('title', 'Contact Method Master | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Contact Method Master'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section master-data-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Contact Method Master']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Contact Method Master']])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('fleet.master-data.client-types')); ?>" class="btn secondary">Client Type Master</a>
            <span class="badge soft">Database backed dropdown values</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => ''.e($fleetman['masterTitle'] ?? 'Contact Method Master').'','subtitle' => ''.e($fleetman['masterSubtitle'] ?? 'Manage preferred contact methods for dropdowns across the application.').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($fleetman['masterTitle'] ?? 'Contact Method Master').'','subtitle' => ''.e($fleetman['masterSubtitle'] ?? 'Manage preferred contact methods for dropdowns across the application.').'']); ?>
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

    <div class="master-overview-grid">
        <a class="master-overview-card master-overview-link" href="<?php echo e(route('fleet.master-data.party-types')); ?>">
            <div class="master-overview-icon">🤝</div>
            <div><strong id="masterPartyTypeCount">0</strong><span>Party types available for Vendor / Party dropdowns</span></div>
        </a>
        <a class="master-overview-card master-overview-link" href="<?php echo e(route('fleet.master-data.client-types')); ?>">
            <div class="master-overview-icon">🏢</div>
            <div><strong id="masterClientTypeCount">0</strong><span>Client types available for Client dropdowns</span></div>
        </a>
        <div class="master-overview-card">
            <div class="master-overview-icon">📞</div>
            <div><strong id="masterContactMethodCount">0</strong><span>Contact methods available for dropdowns</span></div>
        </div>
    </div>

    <section class="card master-card" id="contactMethodMasterCard">
        <div class="section-head">
            <div>
                <h2>Contact Method Master</h2>
                <p>Add contact methods once and use them in related dropdowns across the app.</p>
            </div>
            <button type="button" class="btn light" id="resetContactMethodMasterBtn">Reset</button>
        </div>

        <form id="contactMethodMasterForm" class="master-form" autocomplete="off">
            <input type="hidden" id="contactMethodEditingCode">
            <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'contactMethodMasterName','label' => 'Contact Method Name','placeholder' => 'Example: Phone','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contactMethodMasterName','label' => 'Contact Method Name','placeholder' => 'Example: Phone','required' => true]); ?>
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
            <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'contactMethodMasterCode','label' => 'Code','placeholder' => 'Example: PHONE','hint' => 'Code is auto-generated but can be edited before save.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contactMethodMasterCode','label' => 'Code','placeholder' => 'Example: PHONE','hint' => 'Code is auto-generated but can be edited before save.']); ?>
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
            <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'contactMethodMasterSort','label' => 'Sort Order','type' => 'number','value' => '0','min' => '0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contactMethodMasterSort','label' => 'Sort Order','type' => 'number','value' => '0','min' => '0']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'contactMethodMasterStatus','label' => 'Status','options' => ['Active', 'Inactive'],'value' => 'Active']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contactMethodMasterStatus','label' => 'Status','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['Active', 'Inactive']),'value' => 'Active']); ?>
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
            <div class="master-form-full">
                <?php if (isset($component)) { $__componentOriginal07268ac3e2412b39f93e549948ffa1ca = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal07268ac3e2412b39f93e549948ffa1ca = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.textarea','data' => ['id' => 'contactMethodMasterDescription','label' => 'Description / Note','placeholder' => 'Optional internal note about where this contact method should be used.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'contactMethodMasterDescription','label' => 'Description / Note','placeholder' => 'Optional internal note about where this contact method should be used.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal07268ac3e2412b39f93e549948ffa1ca)): ?>
<?php $attributes = $__attributesOriginal07268ac3e2412b39f93e549948ffa1ca; ?>
<?php unset($__attributesOriginal07268ac3e2412b39f93e549948ffa1ca); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal07268ac3e2412b39f93e549948ffa1ca)): ?>
<?php $component = $__componentOriginal07268ac3e2412b39f93e549948ffa1ca; ?>
<?php unset($__componentOriginal07268ac3e2412b39f93e549948ffa1ca); ?>
<?php endif; ?>
            </div>
            <div class="master-form-actions">
                <button type="submit" class="btn primary" id="saveContactMethodMasterBtn">Save Contact Method</button>
                <button type="button" class="btn light" id="cancelContactMethodEditBtn">Cancel Edit</button>
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Contact Methods</b><small>These rows are stored in the fleet_contact_methods table.</small></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Contact Method</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="contactMethodMasterTbody"></tbody>
            </table>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/master-data/contact-methods.blade.php ENDPATH**/ ?>