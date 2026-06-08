@extends('layouts.fleetman')

@section('title', 'Vendor / Contractor Types | FleetMan')
@section('mobile-title', 'Vendor / Contractor Types')

@section('content')
@php
    $vendorContractorTypes = $fleetman['vendorContractorTypeRows'] ?? collect();
    $editingVendorContractorType = $fleetman['editingVendorContractorType'] ?? null;
    $isEditing = $editingVendorContractorType !== null;
    $formName = old('name', $editingVendorContractorType?->name ?? '');
    $formCode = old('code', $editingVendorContractorType?->code ?? '');
    $formSortOrder = old('sort_order', $editingVendorContractorType?->sort_order ?? 0);
    $formStatus = old('status', ($editingVendorContractorType?->is_active ?? true) ? 'Active' : 'Inactive');
    $formDescription = old('description', $editingVendorContractorType?->description ?? '');
    $formIsCarRelated = (bool) old('is_car_related', $editingVendorContractorType?->is_car_related ?? false);
@endphp

<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Vendor / Contractor Types']]">
        <x-slot:actions>
            <a href="{{ route('fleet.vendor-parties', ['action' => 'add']) }}" class="btn secondary">Open Add Vendor / Party</a>
            <span class="badge soft">Saved directly by Laravel</span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Vendor / Contractor Type Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage vendor and contractor types used for vehicle and driver filtering.' }}"
    />

    @if (session('success'))
        <div class="login-success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="login-error" role="alert">
            Please correct the highlighted vendor / contractor type fields and submit again.
        </div>
    @endif

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">🚚</div>
            <div><strong>{{ $vendorContractorTypes->where('is_active', true)->count() }}</strong><span>Active vendor / contractor types available in forms</span></div>
        </div>
    </div>

    <section class="card master-card" id="vendorContractorTypeMasterCard">
        <div class="section-head">
            <div>
                <h2>{{ $isEditing ? 'Edit Vendor / Contractor Type' : 'Add Vendor / Contractor Type' }}</h2>
                <p>These values are written directly to Laravel and used to filter car-related vendors for Vehicle and Driver forms.</p>
            </div>
            <a href="{{ route('fleet.master-data.vendor-contractor-types') }}" class="btn light">{{ $isEditing ? 'Cancel Edit' : 'Reset' }}</a>
        </div>

        <form
            method="POST"
            action="{{ $isEditing ? route('fleet.master-data.vendor-contractor-types.update', $editingVendorContractorType) : route('fleet.master-data.vendor-contractor-types.store') }}"
            class="master-form"
            autocomplete="off"
        >
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="field @error('name') field-invalid @enderror">
                <label for="vendorContractorTypeName">Vendor / Contractor Type Name <span class="req">*</span></label>
                <input
                    id="vendorContractorTypeName"
                    name="name"
                    type="text"
                    value="{{ $formName }}"
                    placeholder="Example: Car Related"
                    required
                    maxlength="120"
                    aria-required="true"
                >
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('code') field-invalid @enderror">
                <label for="vendorContractorTypeCode">Code</label>
                <input
                    id="vendorContractorTypeCode"
                    name="code"
                    type="text"
                    value="{{ $formCode }}"
                    placeholder="Auto-generated when left empty"
                    maxlength="120"
                    pattern="[A-Za-z0-9_]+"
                >
                <div class="hint">Leave empty to generate the code in Laravel from the vendor / contractor type name.</div>
                @error('code')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('sort_order') field-invalid @enderror">
                <label for="vendorContractorTypeSortOrder">Sort Order</label>
                <input
                    id="vendorContractorTypeSortOrder"
                    name="sort_order"
                    type="number"
                    value="{{ $formSortOrder }}"
                    min="0"
                    max="999999"
                >
                @error('sort_order')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('status') field-invalid @enderror">
                <label for="vendorContractorTypeStatus">Status <span class="req">*</span></label>
                <select id="vendorContractorTypeStatus" name="status" required aria-required="true">
                    <option value="Active" @selected($formStatus === 'Active')>Active</option>
                    <option value="Inactive" @selected($formStatus === 'Inactive')>Inactive</option>
                </select>
                @error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('is_car_related') field-invalid @enderror">
                <label class="choice checkbox-choice" for="vendorContractorTypeIsCarRelated">
                    <input
                        id="vendorContractorTypeIsCarRelated"
                        name="is_car_related"
                        type="checkbox"
                        value="1"
                        @checked($formIsCarRelated)
                    >
                    <span><b>Car Related Type</b><small>Vendors assigned to this type can be selected on Vehicle and Driver forms.</small></span>
                </label>
                @error('is_car_related')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="master-form-full field @error('description') field-invalid @enderror">
                <label for="vendorContractorTypeDescription">Description / Note</label>
                <textarea
                    id="vendorContractorTypeDescription"
                    name="description"
                    maxlength="2000"
                    placeholder="Optional internal note about this vendor / contractor type."
                >{{ $formDescription }}</textarea>
                @error('description')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="master-form-actions">
                <button type="submit" class="btn primary">{{ $isEditing ? 'Update Vendor / Contractor Type' : 'Save Vendor / Contractor Type' }}</button>
                @if ($isEditing)
                    <a href="{{ route('fleet.master-data.vendor-contractor-types') }}" class="btn light">Cancel Edit</a>
                @endif
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Vendor / Contractor Types</b><small>Only active rows appear in the Vendor / Contractor Type dropdown.</small></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vendor / Contractor Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Car Related</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendorContractorTypes as $vendorContractorType)
                        <tr>
                            <td><b>{{ $vendorContractorType->name }}</b></td>
                            <td><span class="master-code">{{ $vendorContractorType->code }}</span></td>
                            <td>{{ $vendorContractorType->sort_order }}</td>
                            <td><span class="badge {{ $vendorContractorType->is_car_related ? 'ok' : 'soft' }}">{{ $vendorContractorType->is_car_related ? 'Yes' : 'No' }}</span></td>
                            <td>
                                <span class="badge {{ $vendorContractorType->is_active ? 'ok' : 'warn' }}">
                                    {{ $vendorContractorType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="master-description">{{ $vendorContractorType->description ?: '—' }}</td>
                            <td>
                                <div class="master-actions">
                                    <a
                                        href="{{ route('fleet.master-data.vendor-contractor-types', ['edit' => $vendorContractorType->id]) }}"
                                        class="mini-btn"
                                    >Edit</a>
                                    <form
                                        method="POST"
                                        action="{{ route('fleet.master-data.vendor-contractor-types.destroy', $vendorContractorType) }}"
                                        onsubmit="return confirm('Delete this vendor / contractor type? Existing vendor records will keep their saved type.');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mini-btn danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No vendor / contractor type added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
