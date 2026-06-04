<?php $__env->startSection('title', 'Clients | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Clients'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-section">
    <div id="clientAddPage">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Add Client']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Add Client']])]); ?>
             <?php $__env->slot('actions', null, []); ?> <button type="button" class="btn light" data-page-target="clientListPage">← Client List</button> <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Add New Client','subtitle' => 'A simple client form for non-technical users. Keep key fields up front, then add one or more contact persons below.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Add New Client','subtitle' => 'A simple client form for non-technical users. Keep key fields up front, then add one or more contact persons below.']); ?> <?php $__env->slot('action', null, []); ?> <button type="button" class="btn secondary" id="loadClientSampleBtn">Use sample data</button> <?php $__env->endSlot(); ?> <?php echo $__env->renderComponent(); ?>
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
                <?php if (isset($component)) { $__componentOriginal315c571ce40dc0c12ed885ba8a594408 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal315c571ce40dc0c12ed885ba8a594408 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => '1. Client Information','description' => 'Enter the client or company name first. The system generates the Client ID automatically.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '1. Client Information','description' => 'Enter the client or company name first. The system generates the Client ID automatically.']); ?>
                    <div class="grid3">
                        <?php if (isset($component)) { $__componentOriginal8e448d98e7f6e76a56b5afe0e1522523 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8e448d98e7f6e76a56b5afe0e1522523 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'clientId','label' => 'Client ID','required' => true,'readonly' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientId','label' => 'Client ID','required' => true,'readonly' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'clientName','label' => 'Client Name','required' => true,'placeholder' => 'Example: ABC Logistics Ltd.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientName','label' => 'Client Name','required' => true,'placeholder' => 'Example: ABC Logistics Ltd.']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'clientEmail','label' => 'Email','placeholder' => 'company@example.com']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientEmail','label' => 'Email','placeholder' => 'company@example.com']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'clientPhone','label' => 'Phone Number','required' => true,'placeholder' => '01XXXXXXXXX']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientPhone','label' => 'Phone Number','required' => true,'placeholder' => '01XXXXXXXXX']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'clientWhatsapp','label' => 'WhatsApp Number','placeholder' => '01XXXXXXXXX']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientWhatsapp','label' => 'WhatsApp Number','placeholder' => '01XXXXXXXXX']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.input','data' => ['id' => 'clientReference','label' => 'Reference','placeholder' => 'Who referred this client?']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientReference','label' => 'Reference','placeholder' => 'Who referred this client?']); ?>
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
                    <div class="grid3" style="margin-top:16px">
                        <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'clientType','label' => 'Client Type','options' => $fleetman['options']['client_types'],'value' => 'Corporate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientType','label' => 'Client Type','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['client_types']),'value' => 'Corporate']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'clientStatus','label' => 'Status','options' => $fleetman['options']['client_statuses'],'value' => 'Active']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientStatus','label' => 'Status','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['client_statuses']),'value' => 'Active']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'clientContactMethod','label' => 'Preferred Contact Method','options' => $fleetman['options']['client_contact_methods'],'value' => 'Phone']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientContactMethod','label' => 'Preferred Contact Method','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['client_contact_methods']),'value' => 'Phone']); ?>
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
                    <div style="margin-top:16px"><?php if (isset($component)) { $__componentOriginal07268ac3e2412b39f93e549948ffa1ca = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal07268ac3e2412b39f93e549948ffa1ca = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.textarea','data' => ['id' => 'clientAddress','label' => 'Permanent Address','required' => true,'placeholder' => 'House / Road / Area / City']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientAddress','label' => 'Permanent Address','required' => true,'placeholder' => 'House / Road / Area / City']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal07268ac3e2412b39f93e549948ffa1ca)): ?>
<?php $attributes = $__attributesOriginal07268ac3e2412b39f93e549948ffa1ca; ?>
<?php unset($__attributesOriginal07268ac3e2412b39f93e549948ffa1ca); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal07268ac3e2412b39f93e549948ffa1ca)): ?>
<?php $component = $__componentOriginal07268ac3e2412b39f93e549948ffa1ca; ?>
<?php unset($__componentOriginal07268ac3e2412b39f93e549948ffa1ca); ?>
<?php endif; ?></div>
                    <div style="margin-top:16px"><?php if (isset($component)) { $__componentOriginal07268ac3e2412b39f93e549948ffa1ca = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal07268ac3e2412b39f93e549948ffa1ca = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.textarea','data' => ['id' => 'clientAbout','label' => 'About / Notes','placeholder' => 'Short note about this client, operation area, service requirement, billing note, etc.','hint' => 'Use plain language so any office user can understand the record later.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientAbout','label' => 'About / Notes','placeholder' => 'Short note about this client, operation area, service requirement, billing note, etc.','hint' => 'Use plain language so any office user can understand the record later.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal07268ac3e2412b39f93e549948ffa1ca)): ?>
<?php $attributes = $__attributesOriginal07268ac3e2412b39f93e549948ffa1ca; ?>
<?php unset($__attributesOriginal07268ac3e2412b39f93e549948ffa1ca); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal07268ac3e2412b39f93e549948ffa1ca)): ?>
<?php $component = $__componentOriginal07268ac3e2412b39f93e549948ffa1ca; ?>
<?php unset($__componentOriginal07268ac3e2412b39f93e549948ffa1ca); ?>
<?php endif; ?></div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.section-card','data' => ['title' => '2. Contact Person(s)','description' => 'Add one or more contact persons. Keep the primary contact first.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '2. Contact Person(s)','description' => 'Add one or more contact persons. Keep the primary contact first.']); ?>
                     <?php $__env->slot('action', null, []); ?> <button type="button" class="btn light" id="addClientContactBtn">＋ Add Contact Person</button> <?php $__env->endSlot(); ?>
                    <div id="clientContacts"></div>
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
            <aside>
                <?php if (isset($component)) { $__componentOriginal17352d969f30114bfa82aca2f3d9e309 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal17352d969f30114bfa82aca2f3d9e309 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.side-note','data' => ['title' => 'Recommended form flow']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.side-note'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Recommended form flow']); ?><ul><li>First enter client name and phone number.</li><li>Add the main contact person at the top.</li><li>Use status <b>Prospect</b> for leads that are not active yet.</li><li>After save, the prototype redirects to the client list page.</li></ul> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal17352d969f30114bfa82aca2f3d9e309)): ?>
<?php $attributes = $__attributesOriginal17352d969f30114bfa82aca2f3d9e309; ?>
<?php unset($__attributesOriginal17352d969f30114bfa82aca2f3d9e309); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal17352d969f30114bfa82aca2f3d9e309)): ?>
<?php $component = $__componentOriginal17352d969f30114bfa82aca2f3d9e309; ?>
<?php unset($__componentOriginal17352d969f30114bfa82aca2f3d9e309); ?>
<?php endif; ?>
                <div class="required-box"><b>Required before save:</b><br>Client name, phone number, address, and at least one contact person name + phone.</div>
            </aside>
        </div>
        <div class="save-bar"><button type="button" class="btn light" id="resetClientBtn">Reset Form</button><button type="button" class="btn secondary" id="saveClientDraftBtn">Save as Draft</button><button type="button" class="btn primary" id="saveClientBtn">Save Client</button></div>
    </div>

    <div id="clientListPage" class="hidden">
        <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'Client List']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'Client List']])]); ?>
             <?php $__env->slot('actions', null, []); ?> <button type="button" class="btn light" id="exportClientsBtn">⬇ Export CSV</button><button type="button" class="btn primary" id="newClientBtn">＋ Add Client</button> <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Client List','subtitle' => 'Simple list page with sample data, search, filters, and quick actions. Designed for easy day-to-day office use.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Client List','subtitle' => 'Simple list page with sample data, search, filters, and quick actions. Designed for easy day-to-day office use.']); ?> <?php $__env->slot('action', null, []); ?> <div class="pillbar"><div class="pill active">All Clients</div><div class="pill">Recently Added</div><div class="pill">Active</div></div> <?php $__env->endSlot(); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $attributes = $__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__attributesOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3)): ?>
<?php $component = $__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3; ?>
<?php unset($__componentOriginal33f656cc9906d43d1f23d50a43b5f3b3); ?>
<?php endif; ?>
        <div class="kpi"><?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'clientKpiTotal','label' => 'Total Clients']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientKpiTotal','label' => 'Total Clients']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'clientKpiActive','label' => 'Active Clients']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientKpiActive','label' => 'Active Clients']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'clientKpiContacts','label' => 'Total Contact Persons']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientKpiContacts','label' => 'Total Contact Persons']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.kpi-card','data' => ['id' => 'clientKpiEmail','label' => 'Clients with Email']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.kpi-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientKpiEmail','label' => 'Clients with Email']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $attributes = $__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__attributesOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6)): ?>
<?php $component = $__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6; ?>
<?php unset($__componentOriginalf0c0d749c1d866d6dda6395f8c5d46f6); ?>
<?php endif; ?></div>
        <div class="card">
            <div class="filters">
                <input id="clientSearch" placeholder="Search by client name, phone, email, contact person, or client ID">
                <?php if (isset($component)) { $__componentOriginal4b244ece64768724078120db372595a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4b244ece64768724078120db372595a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'clientFilterStatus','label' => '','options' => $fleetman['options']['client_statuses'],'placeholder' => 'All Status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientFilterStatus','label' => '','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['client_statuses']),'placeholder' => 'All Status']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'clientFilterType','label' => '','options' => $fleetman['options']['client_types'],'placeholder' => 'All Types']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientFilterType','label' => '','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['client_types']),'placeholder' => 'All Types']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.select','data' => ['id' => 'clientFilterMethod','label' => '','options' => $fleetman['options']['client_contact_methods'],'placeholder' => 'All Contact Methods']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'clientFilterMethod','label' => '','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fleetman['options']['client_contact_methods']),'placeholder' => 'All Contact Methods']); ?>
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
                <div style="display:flex;gap:10px"><button type="button" class="btn secondary" id="applyClientFiltersBtn">Apply</button><button type="button" class="btn light" id="clearClientFiltersBtn">Clear</button></div>
            </div>
            <div class="table-wrap client-table"><table><thead><tr><th>Client</th><th>Main Phone</th><th>Contact Person(s)</th><th>Type</th><th>Status</th><th>Preferred Contact</th><th>Address</th><th>Actions</th></tr></thead><tbody id="clientTbody"></tbody></table></div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/clients.blade.php ENDPATH**/ ?>