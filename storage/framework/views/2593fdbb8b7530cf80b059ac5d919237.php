<?php $__env->startSection('title', 'Release List | FleetMan'); ?>
<?php $__env->startSection('mobile-title', 'Release List'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $releasePayload = $releases->mapWithKeys(function ($release) {
        return [(string) $release->id => [
            'id' => $release->id,
            'version' => $release->version,
            'title' => $release->title,
            'issue_type' => $release->issue_type ?: 'Not specified',
            'initiated_by' => $release->initiatedBy?->name ?? 'Not specified',
            'release_date_label' => optional($release->release_date)->format('d M Y'),
            'environment_label' => $release->environmentLabel(),
            'status_label' => $release->statusLabel(),
            'summary' => $release->summary ?? '',
            'changes' => $release->changes ?? '',
            'known_issues' => $release->known_issues ?? '',
            'created_by' => $release->createdBy?->name ?? 'System / Legacy',
            'updated_by' => $release->updatedBy?->name ?? $release->createdBy?->name ?? 'Unknown',
            'created_at' => optional($release->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
            'updated_at' => optional($release->updated_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
        ]];
    });

    $isSuperAdmin = auth()->user()?->isFleetSuperAdmin() ?? false;

    $statusBadge = static fn (string $status): string => match ($status) {
        'released' => 'ok',
        'scheduled' => 'soft',
        'rolled_back' => 'danger',
        default => 'warn',
    };
?>

<div class="page-section release-tracker-page">
    <?php if (isset($component)) { $__componentOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1bf3ca5b4372ced6ff0d503060f43b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.topbar','data' => ['items' => [['label' => 'System'], ['label' => 'Release Tracker / Notes'], ['label' => 'Release List']]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['label' => 'System'], ['label' => 'Release Tracker / Notes'], ['label' => 'Release List']])]); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <span class="badge <?php echo e($isSuperAdmin ? 'danger' : 'soft'); ?>"><?php echo e($isSuperAdmin ? 'Super Admin Management' : 'Read Only'); ?></span>
            <?php if($isSuperAdmin): ?>
                <a href="<?php echo e(route('fleet.release-tracker.form')); ?>" class="btn primary">Add Release</a>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fleetman.title-card','data' => ['title' => 'Release List','subtitle' => 'View application versions, issue types, initiators, release dates, deployment environments, and release notes.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fleetman.title-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Release List','subtitle' => 'View application versions, issue types, initiators, release dates, deployment environments, and release notes.']); ?>
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

    <?php if(session('status')): ?>
        <div class="role-alert role-alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <div class="kpi release-kpis">
        <div class="card"><strong><?php echo e($counts['total']); ?></strong><span>Total Releases</span></div>
        <div class="card"><strong><?php echo e($counts['released']); ?></strong><span>Released</span></div>
        <div class="card"><strong><?php echo e($counts['scheduled']); ?></strong><span>Scheduled</span></div>
        <div class="card"><strong><?php echo e($counts['draft']); ?></strong><span>Draft</span></div>
    </div>

    <section class="card release-list-card">
        <div class="section-head">
            <div>
                <h2>Release History</h2>
                <p><?php echo e($isSuperAdmin ? 'Newest release date appears first. Super Admin can add and edit release entries.' : 'Newest release date appears first. This page is view-only for your account.'); ?></p>
            </div>
            <span class="badge soft"><?php echo e($releases->count()); ?> result<?php echo e($releases->count() === 1 ? '' : 's'); ?></span>
        </div>

        <form method="GET" action="<?php echo e(route('fleet.release-tracker')); ?>" class="release-filter-grid">
            <div class="field release-search-field">
                <label for="releaseSearch">Search</label>
                <input id="releaseSearch" name="q" value="<?php echo e($filters['search']); ?>" placeholder="Version, title, initiator, notes, or issue">
            </div>
            <div class="field">
                <label for="releaseIssueTypeFilter">Issue Type</label>
                <select id="releaseIssueTypeFilter" name="issue_type">
                    <option value="">All issue types</option>
                    <?php $__currentLoopData = $issueTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if($filters['issueType'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="field">
                <label for="releaseStatusFilter">Status</label>
                <select id="releaseStatusFilter" name="status">
                    <option value="">All statuses</option>
                    <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if($filters['status'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="field">
                <label for="releaseEnvironmentFilter">Environment</label>
                <select id="releaseEnvironmentFilter" name="environment">
                    <option value="">All environments</option>
                    <?php $__currentLoopData = $environmentOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if($filters['environment'] === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="release-filter-actions">
                <a href="<?php echo e(route('fleet.release-tracker')); ?>" class="btn light">Reset</a>
                <button type="submit" class="btn secondary">Filter</button>
            </div>
        </form>

        <div class="table-wrap release-table-wrap">
            <table class="release-table">
                <thead>
                    <tr>
                        <th>Created At</th>
                        <th>Version</th>
                        <th>Release</th>
                        <th>Issue Type</th>
                        <th>Initiated By</th>
                        <th>Release Date</th>
                        <th>Environment</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $releases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $release): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="created-at-cell">
                                    <span class="created-at-date"><?php echo e(optional($release->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A')); ?></span>
                                    <small class="created-at-creator">Created by: <?php echo e($release->createdBy?->name ?? 'System / Legacy'); ?></small>
                                </div>
                            </td>
                            <td><span class="release-version"><?php echo e($release->version); ?></span></td>
                            <td class="release-title-cell">
                                <b><?php echo e($release->title); ?></b>
                                <small><?php echo e(\Illuminate\Support\Str::limit($release->summary ?: 'No summary added.', 85)); ?></small>
                            </td>
                            <td><span class="badge soft"><?php echo e($release->issue_type ?: 'Not specified'); ?></span></td>
                            <td><?php echo e($release->initiatedBy?->name ?? 'Not specified'); ?></td>
                            <td><?php echo e(optional($release->release_date)->format('d M Y')); ?></td>
                            <td><span class="badge soft"><?php echo e($release->environmentLabel()); ?></span></td>
                            <td><span class="badge <?php echo e($statusBadge($release->status)); ?>"><?php echo e($release->statusLabel()); ?></span></td>
                            <td>
                                <button type="button" class="mini-btn" data-release-view="<?php echo e($release->id); ?>">View</button>
                                <?php if($isSuperAdmin): ?>
                                    <a href="<?php echo e(route('fleet.release-tracker.edit', $release)); ?>" class="mini-btn">Edit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="9" class="empty">No release entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="release-mobile-list">
            <?php $__empty_1 = true; $__currentLoopData = $releases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $release): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="release-mobile-card">
                    <div class="release-mobile-head">
                        <div>
                            <span class="release-version"><?php echo e($release->version); ?></span>
                            <h3><?php echo e($release->title); ?></h3>
                        </div>
                        <span class="badge <?php echo e($statusBadge($release->status)); ?>"><?php echo e($release->statusLabel()); ?></span>
                    </div>
                    <p><?php echo e(\Illuminate\Support\Str::limit($release->summary ?: 'No summary added.', 130)); ?></p>
                    <div class="release-mobile-meta">
                        <span><small>Issue Type</small><b><?php echo e($release->issue_type ?: 'Not specified'); ?></b></span>
                        <span><small>Initiated By</small><b><?php echo e($release->initiatedBy?->name ?? 'Not specified'); ?></b></span>
                        <span><small>Release Date</small><b><?php echo e(optional($release->release_date)->format('d M Y')); ?></b></span>
                        <span><small>Environment</small><b><?php echo e($release->environmentLabel()); ?></b></span>
                    </div>
                    <div class="release-mobile-actions">
                        <button type="button" class="mini-btn" data-release-view="<?php echo e($release->id); ?>">View</button>
                        <?php if($isSuperAdmin): ?>
                            <a href="<?php echo e(route('fleet.release-tracker.edit', $release)); ?>" class="mini-btn">Edit</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty">No release entries found.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div id="releaseViewModal" class="release-modal hidden" aria-hidden="true">
    <section class="release-modal-panel release-view-panel" role="dialog" aria-modal="true" aria-labelledby="releaseViewTitle">
        <div class="release-modal-head">
            <div>
                <span class="release-modal-kicker">Release Details</span>
                <h2 id="releaseViewTitle">Release</h2>
                <p id="releaseViewSubtitle"></p>
            </div>
            <button type="button" class="release-modal-close" data-release-modal-close aria-label="Close release details">×</button>
        </div>
        <div class="release-modal-body">
            <div class="release-detail-meta" id="releaseViewMeta"></div>
            <div class="release-detail-block">
                <h3>Summary</h3>
                <p id="releaseViewSummary"></p>
            </div>
            <div class="release-detail-block">
                <h3>Changes / Release Notes</h3>
                <pre id="releaseViewChanges"></pre>
            </div>
            <div class="release-detail-block">
                <h3>Known Issues</h3>
                <pre id="releaseViewIssues"></pre>
            </div>
        </div>
    </section>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const releases = <?php echo json_encode($releasePayload, 15, 512) ?>;
    const modal = document.getElementById('releaseViewModal');

    const openModal = () => {
        modal?.classList.remove('hidden');
        modal?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('release-modal-open');
    };

    const closeModal = () => {
        modal?.classList.add('hidden');
        modal?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('release-modal-open');
    };

    const valueOrFallback = (value, fallback = 'Not provided.') => {
        const text = String(value ?? '').trim();
        return text || fallback;
    };

    const openView = (release) => {
        if (!release) return;
        document.getElementById('releaseViewTitle').textContent = `${release.version} — ${release.title}`;
        document.getElementById('releaseViewSubtitle').textContent = `${release.issue_type} • ${release.environment_label} • ${release.status_label}`;
        document.getElementById('releaseViewSummary').textContent = valueOrFallback(release.summary);
        document.getElementById('releaseViewChanges').textContent = valueOrFallback(release.changes);
        document.getElementById('releaseViewIssues').textContent = valueOrFallback(release.known_issues, 'No known issues recorded.');

        const meta = document.getElementById('releaseViewMeta');
        meta.replaceChildren();
        [
            ['Issue Type', release.issue_type],
            ['Initiated By', release.initiated_by],
            ['Release Date', release.release_date_label],
            ['Environment', release.environment_label],
            ['Status', release.status_label],
            ['Created By', release.created_by],
            ['Updated By', release.updated_by],
            ['Created At', release.created_at],
            ['Updated At', release.updated_at],
        ].forEach(([label, value]) => {
            const item = document.createElement('div');
            const small = document.createElement('small');
            const strong = document.createElement('strong');
            small.textContent = label;
            strong.textContent = valueOrFallback(value, '—');
            item.append(small, strong);
            meta.append(item);
        });

        openModal();
    };

    document.querySelectorAll('[data-release-view]').forEach((button) => {
        button.addEventListener('click', () => openView(releases[String(button.dataset.releaseView)]));
    });

    document.querySelectorAll('[data-release-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModal();
    });
})();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.fleetman', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/laravel/FleetManagement/resources/views/fleetman/system/release-tracker-list.blade.php ENDPATH**/ ?>