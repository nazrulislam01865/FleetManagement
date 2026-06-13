@extends('layouts.fleetman')

@section('title', 'Payment Types | FleetMan')
@section('mobile-title', 'Payment Types')

@section('content')
@php
    $paymentTypes = $fleetman['paymentTypeRows'] ?? collect();
    $editingPaymentType = $fleetman['editingPaymentType'] ?? null;
    $isEditing = $editingPaymentType !== null;
    $formName = old('name', $editingPaymentType?->name ?? '');
    $formCode = old('code', $editingPaymentType?->code ?? '');
    $formSortOrder = old('sort_order', $editingPaymentType?->sort_order ?? 0);
    $formStatus = old('status', ($editingPaymentType?->is_active ?? true) ? 'Active' : 'Inactive');
    $formDescription = old('description', $editingPaymentType?->description ?? '');
@endphp

<div class="page-section master-data-page">
    <x-fleetman.topbar :items="[['label' => 'Master Data', 'route' => 'fleet.master-data'], ['label' => 'Payment Types']]">
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="{{ $fleetman['masterTitle'] ?? 'Payment Type Master' }}"
        subtitle="{{ $fleetman['masterSubtitle'] ?? 'Manage the payment methods available on the Add Trip page.' }}"
    />

    @if (session('success'))
        <div class="login-success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="login-error" role="alert">
            Please correct the highlighted payment type fields and submit again.
        </div>
    @endif

    <div class="master-overview-grid">
        <div class="master-overview-card">
            <div class="master-overview-icon">💳</div>
            <div><strong>{{ $paymentTypes->where('is_active', true)->count() }}</strong><span>Active payment types available</span></div>
        </div>
    </div>

    <section class="card master-card" id="paymentTypeMasterCard">
        <div class="section-head">
            <div>
                <h2>{{ $isEditing ? 'Edit Payment Type' : 'Add Payment Type' }}</h2>
            </div>
            <a href="{{ route('fleet.master-data.payment-types') }}" class="btn light">{{ $isEditing ? 'Cancel Edit' : 'Reset' }}</a>
        </div>

        <form
            method="POST"
            action="{{ $isEditing ? route('fleet.master-data.payment-types.update', $editingPaymentType) : route('fleet.master-data.payment-types.store') }}"
            class="master-form"
            autocomplete="off"
        >
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="field @error('name') field-invalid @enderror">
                <label for="paymentTypeName">Payment Type Name <span class="req">*</span></label>
                <input
                    id="paymentTypeName"
                    name="name"
                    type="text"
                    value="{{ $formName }}"
                    placeholder="Example: Cash"
                    required
                    maxlength="120"
                    aria-required="true"
                >
                @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('code') field-invalid @enderror">
                <label for="paymentTypeCode">Code</label>
                <input
                    id="paymentTypeCode"
                    name="code"
                    type="text"
                    value="{{ $formCode }}"
                    placeholder="Auto-generated when left empty"
                    maxlength="120"
                    pattern="[A-Za-z0-9_]+"
                >
                @error('code')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('sort_order') field-invalid @enderror">
                <label for="paymentTypeSortOrder">Sort Order</label>
                <input
                    id="paymentTypeSortOrder"
                    name="sort_order"
                    type="number"
                    value="{{ $formSortOrder }}"
                    min="0"
                    max="999999"
                >
                @error('sort_order')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field @error('status') field-invalid @enderror">
                <label for="paymentTypeStatus">Status <span class="req">*</span></label>
                <select id="paymentTypeStatus" name="status" required aria-required="true">
                    <option value="Active" @selected($formStatus === 'Active')>Active</option>
                    <option value="Inactive" @selected($formStatus === 'Inactive')>Inactive</option>
                </select>
                @error('status')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="master-form-full field @error('description') field-invalid @enderror">
                <label for="paymentTypeDescription">Description / Note</label>
                <textarea
                    id="paymentTypeDescription"
                    name="description"
                    maxlength="2000"
                    placeholder="Optional internal note about this payment method."
                >{{ $formDescription }}</textarea>
                @error('description')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="master-form-actions">
                <button type="submit" class="btn primary">{{ $isEditing ? 'Update Payment Type' : 'Save Payment Type' }}</button>
                @if ($isEditing)
                    <a href="{{ route('fleet.master-data.payment-types') }}" class="btn light">Cancel Edit</a>
                @endif
            </div>
        </form>

        <div class="master-table-title">
            <div><b>Added Payment Types</b></div>
        </div>
        <div class="table-wrap master-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Created At</th><th>Payment Type</th>
                        <th>Code</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paymentTypes as $paymentType)
                        <tr>
                            <td>
                                <div class="created-at-cell">
                                    <span class="created-at-date">{{ optional($paymentType->created_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                                    <small class="created-at-creator">Created by: {{ $paymentType->creatorName ?? 'System / Legacy' }}</small>
                                </div>
                            </td>
                            <td><b>{{ $paymentType->name }}</b></td>
                            <td><span class="master-code">{{ $paymentType->code }}</span></td>
                            <td>{{ $paymentType->sort_order }}</td>
                            <td>
                                <span class="badge {{ $paymentType->is_active ? 'ok' : 'warn' }}">
                                    {{ $paymentType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="master-description">{{ $paymentType->description ?: '—' }}</td>
                            <td>
                                <div class="master-actions">
                                    <a
                                        href="{{ route('fleet.master-data.payment-types', ['edit' => $paymentType->id]) }}"
                                        class="mini-btn"
                                    >Edit</a>
                                    <form
                                        method="POST"
                                        action="{{ route('fleet.master-data.payment-types.destroy', $paymentType) }}"
                                        onsubmit="return confirm('Delete this payment type? Existing trip records will keep their saved payment method.');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mini-btn danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No payment type added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
