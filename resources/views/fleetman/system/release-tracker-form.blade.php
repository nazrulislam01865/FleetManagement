@extends('layouts.fleetman')

@section('title', 'Add Release | FleetMan')
@section('mobile-title', 'Add Release')

@section('content')
<div class="page-section release-tracker-page">
    <x-fleetman.topbar :items="[['label' => 'System'], ['label' => 'Release Tracker / Notes'], ['label' => 'Add Release']]">
        <x-slot:actions>
            <span class="badge danger">Super Admin Only</span>
            <a href="{{ route('fleet.release-tracker') }}" class="btn light">Release List</a>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="Add Release"
        subtitle="Record a new application release, issue category, initiator, deployment status, and release notes."
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
                <h2>Release Entry Form</h2>
                <p>All required fields must be completed before the release is added to the read-only release list.</p>
            </div>
            <span class="badge soft">System history</span>
        </div>

        <form method="POST" action="{{ route('fleet.release-tracker.store') }}">
            @csrf
            <div class="release-form-grid">
                <div class="field">
                    <label for="releaseVersion">Version <span class="req">*</span></label>
                    <input id="releaseVersion" name="version" value="{{ old('version') }}" placeholder="Example: v1.4.0" maxlength="60" required>
                </div>

                <div class="field release-title-field">
                    <label for="releaseTitle">Release Title <span class="req">*</span></label>
                    <input id="releaseTitle" name="title" value="{{ old('title') }}" placeholder="Example: Driver image and permission update" maxlength="255" required>
                </div>

                <div class="field">
                    <label for="releaseIssueType">Issue Type <span class="req">*</span></label>
                    <select id="releaseIssueType" name="issue_type" required>
                        <option value="">Select issue type</option>
                        @foreach($issueTypeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('issue_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="releaseInitiatedBy">Initiated By <span class="req">*</span></label>
                    <select id="releaseInitiatedBy" name="initiated_by_user_id" required>
                        <option value="">Select active user</option>
                        @foreach($activeUsers as $activeUser)
                            <option value="{{ $activeUser->id }}" @selected((string) old('initiated_by_user_id') === (string) $activeUser->id)>
                                {{ $activeUser->name }}{{ $activeUser->fleetRole?->name ? ' — '.$activeUser->fleetRole->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field fleet-form-temporal-field">
                    <label for="releaseDate">Release Date <span class="req">*</span></label>
                    <input id="releaseDate" type="date" name="release_date" value="{{ old('release_date', now('Asia/Dhaka')->format('Y-m-d')) }}" required>
                </div>

                <div class="field">
                    <label for="releaseEnvironment">Environment <span class="req">*</span></label>
                    <select id="releaseEnvironment" name="environment" required>
                        @foreach($environmentOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('environment', 'production') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="releaseStatus">Status <span class="req">*</span></label>
                    <select id="releaseStatus" name="status" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field release-summary-field">
                    <label for="releaseSummary">Summary</label>
                    <textarea id="releaseSummary" name="summary" maxlength="2000" placeholder="Briefly describe this release.">{{ old('summary') }}</textarea>
                </div>

                <div class="field release-notes-field">
                    <label for="releaseChanges">Changes / Release Notes</label>
                    <textarea id="releaseChanges" name="changes" maxlength="20000" placeholder="List completed features, fixes, and technical changes.">{{ old('changes') }}</textarea>
                </div>

                <div class="field release-notes-field">
                    <label for="releaseKnownIssues">Known Issues</label>
                    <textarea id="releaseKnownIssues" name="known_issues" maxlength="20000" placeholder="List unresolved issues or write None.">{{ old('known_issues') }}</textarea>
                </div>
            </div>

            <div class="release-form-actions">
                <button type="reset" class="btn light">Clear</button>
                <button type="submit" class="btn primary">Add Release</button>
            </div>
        </form>
    </section>
</div>
@endsection
