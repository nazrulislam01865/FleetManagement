@extends('layouts.fleetman')

@section('title', 'Release Tracker | FleetMan')
@section('mobile-title', 'Release Tracker')

@section('content')
@php
    $releasePayload = $releases->mapWithKeys(function ($release) {
        return [(string) $release->id => [
            'id' => $release->id,
            'version' => $release->version,
            'title' => $release->title,
            'release_date' => optional($release->release_date)->format('Y-m-d'),
            'release_date_label' => optional($release->release_date)->format('d M Y'),
            'environment' => $release->environment,
            'environment_label' => $release->environmentLabel(),
            'status' => $release->status,
            'status_label' => $release->statusLabel(),
            'summary' => $release->summary ?? '',
            'changes' => $release->changes ?? '',
            'known_issues' => $release->known_issues ?? '',
            'created_by' => $release->createdBy?->name ?? 'Unknown',
            'updated_by' => $release->updatedBy?->name ?? 'Unknown',
            'created_at' => optional($release->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
            'updated_at' => optional($release->updated_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A'),
        ]];
    });

    $statusBadge = static fn (string $status): string => match ($status) {
        'released' => 'ok',
        'scheduled' => 'soft',
        'rolled_back' => 'danger',
        default => 'warn',
    };

    $oldEditPayload = old('_release_id') ? [
        'id' => old('_release_id'),
        'version' => old('version'),
        'title' => old('title'),
        'release_date' => old('release_date'),
        'environment' => old('environment'),
        'status' => old('status'),
        'summary' => old('summary'),
        'changes' => old('changes'),
        'known_issues' => old('known_issues'),
    ] : null;
@endphp

<div class="page-section release-tracker-page">
    <x-fleetman.topbar :items="[['label' => 'System'], ['label' => 'Release Tracker']]">
        <x-slot:actions>
            <span class="badge danger">Super Admin Only</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="Release Tracker"
        subtitle="Track application versions, release dates, changes, deployment environments, and known issues."
    />

    @if (session('status'))
        <div class="role-alert role-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="role-alert role-alert-danger">
            <b>Could not save the release entry.</b>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="kpi release-kpis">
        <div class="card"><strong>{{ $counts['total'] }}</strong><span>Total Releases</span></div>
        <div class="card"><strong>{{ $counts['released'] }}</strong><span>Released</span></div>
        <div class="card"><strong>{{ $counts['scheduled'] }}</strong><span>Scheduled</span></div>
        <div class="card"><strong>{{ $counts['draft'] }}</strong><span>Draft</span></div>
    </div>

    <section class="card release-entry-card">
        <div class="section-head">
            <div>
                <h2>Add Release Entry</h2>
                <p>Record a new application version. Release notes and known issues support multiple lines.</p>
            </div>
            <span class="badge soft">System history</span>
        </div>

        <form method="POST" action="{{ route('fleet.release-tracker.store') }}">
            @csrf
            <div class="release-form-grid">
                <div class="field">
                    <label for="releaseVersion">Version <span class="req">*</span></label>
                    <input id="releaseVersion" name="version" value="{{ old('_release_id') ? '' : old('version') }}" placeholder="Example: v1.4.0" maxlength="60" required>
                </div>
                <div class="field release-title-field">
                    <label for="releaseTitle">Release Title <span class="req">*</span></label>
                    <input id="releaseTitle" name="title" value="{{ old('_release_id') ? '' : old('title') }}" placeholder="Example: Driver image and permission update" maxlength="255" required>
                </div>
                <div class="field fleet-form-temporal-field">
                    <label for="releaseDate">Release Date <span class="req">*</span></label>
                    <input id="releaseDate" type="date" name="release_date" value="{{ old('_release_id') ? now('Asia/Dhaka')->format('Y-m-d') : old('release_date', now('Asia/Dhaka')->format('Y-m-d')) }}" required>
                </div>
                <div class="field">
                    <label for="releaseEnvironment">Environment <span class="req">*</span></label>
                    <select id="releaseEnvironment" name="environment" required>
                        @foreach($environmentOptions as $value => $label)
                            <option value="{{ $value }}" @selected(!old('_release_id') && old('environment', 'production') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="releaseStatus">Status <span class="req">*</span></label>
                    <select id="releaseStatus" name="status" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(!old('_release_id') && old('status', 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field release-summary-field">
                    <label for="releaseSummary">Summary</label>
                    <textarea id="releaseSummary" name="summary" maxlength="2000" placeholder="Briefly describe this release.">{{ old('_release_id') ? '' : old('summary') }}</textarea>
                </div>
                <div class="field release-notes-field">
                    <label for="releaseChanges">Changes / Release Notes</label>
                    <textarea id="releaseChanges" name="changes" maxlength="20000" placeholder="List completed features, fixes, and technical changes.">{{ old('_release_id') ? '' : old('changes') }}</textarea>
                </div>
                <div class="field release-notes-field">
                    <label for="releaseKnownIssues">Known Issues</label>
                    <textarea id="releaseKnownIssues" name="known_issues" maxlength="20000" placeholder="List unresolved issues or write None.">{{ old('_release_id') ? '' : old('known_issues') }}</textarea>
                </div>
            </div>
            <div class="release-form-actions">
                <button type="reset" class="btn light">Clear</button>
                <button type="submit" class="btn primary">Add Release</button>
            </div>
        </form>
    </section>

    <section class="card release-list-card">
        <div class="section-head">
            <div>
                <h2>Release History</h2>
                <p>Newest release date appears first. Use the filters to locate a version or deployment record.</p>
            </div>
            <span class="badge soft">{{ $releases->count() }} result{{ $releases->count() === 1 ? '' : 's' }}</span>
        </div>

        <form method="GET" action="{{ route('fleet.release-tracker') }}" class="release-filter-grid">
            <div class="field release-search-field">
                <label for="releaseSearch">Search</label>
                <input id="releaseSearch" name="q" value="{{ $filters['search'] }}" placeholder="Version, title, notes, or issue">
            </div>
            <div class="field">
                <label for="releaseStatusFilter">Status</label>
                <select id="releaseStatusFilter" name="status">
                    <option value="">All statuses</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="releaseEnvironmentFilter">Environment</label>
                <select id="releaseEnvironmentFilter" name="environment">
                    <option value="">All environments</option>
                    @foreach($environmentOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['environment'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="release-filter-actions">
                <a href="{{ route('fleet.release-tracker') }}" class="btn light">Reset</a>
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
                        <th>Release Date</th>
                        <th>Environment</th>
                        <th>Status</th>
                        <th>Updated By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($releases as $release)
                        <tr>
                            <td>
                                <div class="created-at-cell">
                                    <span class="created-at-date">{{ optional($release->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                                    <small class="created-at-creator">Created by: {{ $release->createdBy?->name ?? 'System / Legacy' }}</small>
                                </div>
                            </td>
                            <td><span class="release-version">{{ $release->version }}</span></td>
                            <td class="release-title-cell">
                                <b>{{ $release->title }}</b>
                                <small>{{ \Illuminate\Support\Str::limit($release->summary ?: 'No summary added.', 85) }}</small>
                            </td>
                            <td>{{ optional($release->release_date)->format('d M Y') }}</td>
                            <td><span class="badge soft">{{ $release->environmentLabel() }}</span></td>
                            <td><span class="badge {{ $statusBadge($release->status) }}">{{ $release->statusLabel() }}</span></td>
                            <td>{{ $release->updatedBy?->name ?? $release->createdBy?->name ?? 'Unknown' }}</td>
                            <td>
                                <div class="release-row-actions">
                                    <button type="button" class="mini-btn" data-release-view="{{ $release->id }}">View</button>
                                    <button type="button" class="mini-btn" data-release-edit="{{ $release->id }}">Edit</button>
                                    <form method="POST" action="{{ route('fleet.release-tracker.destroy', $release) }}" onsubmit="return confirm('Delete this release entry? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mini-btn danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">No release entries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="release-mobile-list">
            @forelse($releases as $release)
                <article class="release-mobile-card">
                    <div class="release-mobile-head">
                        <div>
                            <span class="release-version">{{ $release->version }}</span>
                            <h3>{{ $release->title }}</h3>
                        </div>
                        <span class="badge {{ $statusBadge($release->status) }}">{{ $release->statusLabel() }}</span>
                    </div>
                    <p>{{ \Illuminate\Support\Str::limit($release->summary ?: 'No summary added.', 130) }}</p>
                    <div class="release-mobile-meta">
                        <span><small>Release Date</small><b>{{ optional($release->release_date)->format('d M Y') }}</b></span>
                        <span><small>Environment</small><b>{{ $release->environmentLabel() }}</b></span>
                        <span><small>Updated By</small><b>{{ $release->updatedBy?->name ?? $release->createdBy?->name ?? 'Unknown' }}</b></span>
                        <span><small>Created</small><b>{{ optional($release->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</b></span>
                    </div>
                    <div class="release-mobile-actions">
                        <button type="button" class="mini-btn" data-release-view="{{ $release->id }}">View</button>
                        <button type="button" class="mini-btn" data-release-edit="{{ $release->id }}">Edit</button>
                        <form method="POST" action="{{ route('fleet.release-tracker.destroy', $release) }}" onsubmit="return confirm('Delete this release entry? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mini-btn danger">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="empty">No release entries found.</div>
            @endforelse
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

<div id="releaseEditModal" class="release-modal hidden" aria-hidden="true">
    <section class="release-modal-panel" role="dialog" aria-modal="true" aria-labelledby="releaseEditTitle">
        <div class="release-modal-head">
            <div>
                <span class="release-modal-kicker">Release Tracker</span>
                <h2 id="releaseEditTitle">Edit Release</h2>
                <p>Update the selected release history entry.</p>
            </div>
            <button type="button" class="release-modal-close" data-release-modal-close aria-label="Close edit release">×</button>
        </div>
        <form id="releaseEditForm" method="POST" action="">
            @csrf
            @method('PUT')
            <input type="hidden" name="_release_id" id="editReleaseId" value="{{ old('_release_id') }}">
            <div class="release-modal-body">
                <div class="release-form-grid">
                    <div class="field">
                        <label for="editReleaseVersion">Version <span class="req">*</span></label>
                        <input id="editReleaseVersion" name="version" maxlength="60" required>
                    </div>
                    <div class="field release-title-field">
                        <label for="editReleaseTitleField">Release Title <span class="req">*</span></label>
                        <input id="editReleaseTitleField" name="title" maxlength="255" required>
                    </div>
                    <div class="field fleet-form-temporal-field">
                        <label for="editReleaseDate">Release Date <span class="req">*</span></label>
                        <input id="editReleaseDate" type="date" name="release_date" required>
                    </div>
                    <div class="field">
                        <label for="editReleaseEnvironment">Environment <span class="req">*</span></label>
                        <select id="editReleaseEnvironment" name="environment" required>
                            @foreach($environmentOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="editReleaseStatus">Status <span class="req">*</span></label>
                        <select id="editReleaseStatus" name="status" required>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field release-summary-field">
                        <label for="editReleaseSummary">Summary</label>
                        <textarea id="editReleaseSummary" name="summary" maxlength="2000"></textarea>
                    </div>
                    <div class="field release-notes-field">
                        <label for="editReleaseChanges">Changes / Release Notes</label>
                        <textarea id="editReleaseChanges" name="changes" maxlength="20000"></textarea>
                    </div>
                    <div class="field release-notes-field">
                        <label for="editReleaseIssues">Known Issues</label>
                        <textarea id="editReleaseIssues" name="known_issues" maxlength="20000"></textarea>
                    </div>
                </div>
            </div>
            <div class="release-modal-actions">
                <button type="button" class="btn light" data-release-modal-close>Cancel</button>
                <button type="submit" class="btn primary">Save Changes</button>
            </div>
        </form>
    </section>
</div>

@push('scripts')
<script>
(() => {
    const releases = @json($releasePayload);
    const updateUrlTemplate = @json(route('fleet.release-tracker.update', ['release' => '__ID__']));
    const oldEdit = @json($oldEditPayload);

    const viewModal = document.getElementById('releaseViewModal');
    const editModal = document.getElementById('releaseEditModal');
    const editForm = document.getElementById('releaseEditForm');

    const openModal = (modal) => {
        modal?.classList.remove('hidden');
        modal?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('release-modal-open');
    };

    const closeModal = (modal) => {
        modal?.classList.add('hidden');
        modal?.setAttribute('aria-hidden', 'true');
        if (viewModal?.classList.contains('hidden') && editModal?.classList.contains('hidden')) {
            document.body.classList.remove('release-modal-open');
        }
    };

    const valueOrFallback = (value, fallback = 'Not provided.') => {
        const text = String(value ?? '').trim();
        return text || fallback;
    };

    const openView = (release) => {
        if (!release) return;
        document.getElementById('releaseViewTitle').textContent = `${release.version} — ${release.title}`;
        document.getElementById('releaseViewSubtitle').textContent = `${release.environment_label} • ${release.status_label}`;
        document.getElementById('releaseViewSummary').textContent = valueOrFallback(release.summary);
        document.getElementById('releaseViewChanges').textContent = valueOrFallback(release.changes);
        document.getElementById('releaseViewIssues').textContent = valueOrFallback(release.known_issues, 'No known issues recorded.');

        const meta = document.getElementById('releaseViewMeta');
        meta.replaceChildren();
        [
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
        openModal(viewModal);
    };

    const openEdit = (release) => {
        if (!release || !editForm) return;
        editForm.action = updateUrlTemplate.replace('__ID__', encodeURIComponent(release.id));
        document.getElementById('editReleaseId').value = release.id ?? '';
        document.getElementById('editReleaseVersion').value = release.version ?? '';
        document.getElementById('editReleaseTitleField').value = release.title ?? '';
        document.getElementById('editReleaseDate').value = release.release_date ?? '';
        document.getElementById('editReleaseEnvironment').value = release.environment ?? 'production';
        document.getElementById('editReleaseStatus').value = release.status ?? 'draft';
        document.getElementById('editReleaseSummary').value = release.summary ?? '';
        document.getElementById('editReleaseChanges').value = release.changes ?? '';
        document.getElementById('editReleaseIssues').value = release.known_issues ?? '';
        openModal(editModal);
    };

    document.querySelectorAll('[data-release-view]').forEach((button) => {
        button.addEventListener('click', () => openView(releases[String(button.dataset.releaseView)]));
    });

    document.querySelectorAll('[data-release-edit]').forEach((button) => {
        button.addEventListener('click', () => openEdit(releases[String(button.dataset.releaseEdit)]));
    });

    document.querySelectorAll('[data-release-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('.release-modal')));
    });

    [viewModal, editModal].forEach((modal) => {
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal(viewModal);
            closeModal(editModal);
        }
    });

    if (oldEdit?.id) {
        openEdit(oldEdit);
    }
})();
</script>
@endpush
@endsection
