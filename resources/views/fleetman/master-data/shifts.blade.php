@extends('layouts.fleetman')

@section('title', 'Shifts | FleetMan')
@section('mobile-title', 'Shifts')

@section('content')
@php
    $shifts = $fleetman['shiftRows'] ?? collect();
    $editingShift = $fleetman['editingShift'] ?? null;
    $isEditing = $editingShift !== null;
    $formName = old('name', $editingShift?->name ?? '');
    $formCode = old('code', $editingShift?->code ?? '');
    $formStartTime = old('start_time', $editingShift?->start_time ? substr((string) $editingShift->start_time, 0, 5) : '');
    $formEndTime = old('end_time', $editingShift?->end_time ? substr((string) $editingShift->end_time, 0, 5) : '');
    $formSortOrder = old('sort_order', $editingShift?->sort_order ?? 0);
    $formStatus = old('status', ($editingShift?->is_active ?? true) ? 'Active' : 'Inactive');
    $formDescription = old('description', $editingShift?->description ?? '');
@endphp

<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Shifts']]" />

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Shift Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage the shifts used when a double-shift vehicle is assigned to a contract.' }}"
    />

    @if (session('success'))
        <div class="login-success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="login-error" role="alert">Please correct the highlighted shift fields and submit again.</div>
    @endif

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">🕒</div>
            <div><strong>{{ $shifts->where('is_active', true)->count() }}</strong><span>Active shifts available for contract assignments</span></div>
        </div>
    </div>

    <section class="card master-card">
        <div class="section-head">
            <div><h2>{{ $isEditing ? 'Edit Shift' : 'Add Shift' }}</h2></div>
            <a href="{{ route('fleet.master-data.shifts') }}" class="btn light">{{ $isEditing ? 'Cancel Edit' : 'Reset' }}</a>
        </div>

        <form
            method="POST"
            action="{{ $isEditing ? route('fleet.master-data.shifts.update', $editingShift) : route('fleet.master-data.shifts.store') }}"
            class="master-form"
            autocomplete="off"
        >
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="field @error('name') field-invalid @enderror">
                <label for="shiftName">Shift Name <span class="req">*</span></label>
                <input id="shiftName" name="name" type="text" value="{{ $formName }}" placeholder="Example: Day Shift" required maxlength="120">
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('code') field-invalid @enderror">
                <label for="shiftCode">Code</label>
                <input id="shiftCode" name="code" type="text" value="{{ $formCode }}" placeholder="Auto-generated when left empty" maxlength="120" pattern="[A-Za-z0-9_]+">
                @error('code')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field fleet-form-temporal-field @error('start_time') field-invalid @enderror">
                <label for="shiftStartTime">Start Time</label>
                <input id="shiftStartTime" name="start_time" type="time" value="{{ $formStartTime }}">
                @error('start_time')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field fleet-form-temporal-field @error('end_time') field-invalid @enderror">
                <label for="shiftEndTime">End Time</label>
                <input id="shiftEndTime" name="end_time" type="time" value="{{ $formEndTime }}">
                @error('end_time')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('sort_order') field-invalid @enderror">
                <label for="shiftSortOrder">Sort Order</label>
                <input id="shiftSortOrder" name="sort_order" type="number" value="{{ $formSortOrder }}" min="0" max="999999">
                @error('sort_order')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('status') field-invalid @enderror">
                <label for="shiftStatus">Status <span class="req">*</span></label>
                <select id="shiftStatus" name="status" required>
                    <option value="Active" @selected($formStatus === 'Active')>Active</option>
                    <option value="Inactive" @selected($formStatus === 'Inactive')>Inactive</option>
                </select>
                @error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="master-form-full field @error('description') field-invalid @enderror">
                <label for="shiftDescription">Description / Note</label>
                <textarea id="shiftDescription" name="description" maxlength="2000" placeholder="Optional note about this shift.">{{ $formDescription }}</textarea>
                @error('description')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="master-form-actions">
                <button type="submit" class="btn primary">{{ $isEditing ? 'Update Shift' : 'Save Shift' }}</button>
                @if ($isEditing)
                    <a href="{{ route('fleet.master-data.shifts') }}" class="btn light">Cancel Edit</a>
                @endif
            </div>
        </form>

        <div class="master-table-title"><div><b>Added Shifts</b></div></div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr><th>Created At</th><th>Shift</th><th>Code</th><th>Time</th><th>Sort</th><th>Status</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $shift)
                        <tr>
                            <td>
                                <div class="created-at-cell">
                                    <span class="created-at-date">{{ optional($shift->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                                    <small class="created-at-creator">Created by: {{ $shift->creatorName ?? 'System / Legacy' }}</small>
                                </div>
                            </td>
                            <td><b>{{ $shift->name }}</b></td>
                            <td><span class="master-code">{{ $shift->code }}</span></td>
                            <td>{{ $shift->start_time ? substr((string) $shift->start_time, 0, 5) : '—' }} – {{ $shift->end_time ? substr((string) $shift->end_time, 0, 5) : '—' }}</td>
                            <td>{{ $shift->sort_order }}</td>
                            <td><span class="badge {{ $shift->is_active ? 'ok' : 'warn' }}">{{ $shift->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="master-description">{{ $shift->description ?: '—' }}</td>
                            <td>
                                <div class="master-actions">
                                    <a href="{{ route('fleet.master-data.shifts', ['edit' => $shift->id]) }}" class="mini-btn">Edit</a>
                                    <form method="POST" action="{{ route('fleet.master-data.shifts.destroy', $shift) }}" onsubmit="return confirm('Delete this shift? Existing contracts will keep their saved shift information.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mini-btn danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">No shift added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
