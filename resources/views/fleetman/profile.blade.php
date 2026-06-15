@extends('layouts.fleetman')

@section('title', 'My Profile | FleetMan')
@section('mobile-title', 'My Profile')

@section('content')
@php
    $profilePhotoPath = trim((string) ($profileUser->profile_photo_path ?? ''));
    $nameParts = preg_split('/\s+/u', trim((string) $profileUser->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $profileInitials = collect($nameParts)
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr((string) $part, 0, 1)))
        ->join('');
    $profileInitials = $profileInitials !== '' ? $profileInitials : 'U';
@endphp

<div class="page-section fleet-profile-page">
    <x-fleetman.topbar :items="[['label' => 'My Account'], ['label' => 'Profile']]">
        <x-slot:actions>
            <span class="badge {{ $profileUser->accountStatusValue() === 'active' ? 'ok' : 'warn' }}">
                {{ $profileUser->accountStatusLabel() }}
            </span>
        </x-slot:actions>
    </x-fleetman.topbar>

    <x-fleetman.title-card
        title="My Profile"
        subtitle="View your account information, update your profile picture, and securely change your password."
    />

    @if(session('profile_status'))
        <div class="role-alert role-alert-success">{{ session('profile_status') }}</div>
    @endif

    @if(session('password_status'))
        <div class="role-alert role-alert-success">{{ session('password_status') }}</div>
    @endif

    <div class="fleet-profile-grid">
        <section class="card fleet-profile-summary-card">
            <div class="fleet-profile-identity">
                <x-fleetman.entity-avatar
                    :file="$profilePhotoPath"
                    :fallback="$profileInitials"
                    :alt="$profileUser->name.' profile picture'"
                    size="large"
                    class="fleet-profile-avatar"
                />
                <div>
                    <span class="fleet-profile-kicker">Logged-in account</span>
                    <h2>{{ $profileUser->name }}</h2>
                    <p>{{ $profileUser->email }}</p>
                </div>
            </div>

            <div class="fleet-profile-info-list">
                <div class="fleet-profile-info-row">
                    <span>Name</span>
                    <strong>{{ $profileUser->name }}</strong>
                </div>
                <div class="fleet-profile-info-row">
                    <span>Email</span>
                    <strong>{{ $profileUser->email }}</strong>
                </div>
                <div class="fleet-profile-info-row">
                    <span>Assigned Role</span>
                    <strong>{{ $profileUser->fleetRole?->name ?? 'No Role Assigned' }}</strong>
                </div>
                <div class="fleet-profile-info-row">
                    <span>Account Status</span>
                    <strong>{{ $profileUser->accountStatusLabel() }}</strong>
                </div>
            </div>

            <div class="fleet-profile-readonly-note">
                Basic account information is read-only here. Authorized administrators can manage account details separately from User Management.
            </div>
        </section>

        <div class="fleet-profile-actions-column">
            <section class="card" id="profile-picture">
                <div class="section-head">
                    <div>
                        <h2>{{ $profilePhotoPath !== '' ? 'Change Profile Picture' : 'Upload Profile Picture' }}</h2>
                        <p>Your picture will appear in the top account menu, profile page, and user identity areas.</p>
                    </div>
                </div>

                @if($errors->profilePicture->any())
                    <div class="role-alert role-alert-danger">
                        <b>Could not update profile picture.</b>
                        <span>{{ $errors->profilePicture->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('fleet.profile.picture') }}" enctype="multipart/form-data" class="fleet-profile-form">
                    @csrf
                    @method('PUT')

                    <div class="field @error('profile_picture', 'profilePicture') field-invalid @enderror">
                        <label for="profilePicture">Profile Picture <span class="req">*</span></label>
                        <input
                            id="profilePicture"
                            type="file"
                            name="profile_picture"
                            accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                            required
                        >
                        <div class="hint">JPG, JPEG, PNG, or WebP. Maximum file size: 2 MB.</div>
                        @error('profile_picture', 'profilePicture')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn primary" data-loading-text="Uploading...">
                        {{ $profilePhotoPath !== '' ? 'Update Profile Picture' : 'Upload Profile Picture' }}
                    </button>
                </form>
            </section>

            <section class="card" id="change-password">
                <div class="section-head">
                    <div>
                        <h2>Change Password</h2>
                        <p>Confirm your current password before setting a new password for this account.</p>
                    </div>
                </div>

                @if($errors->passwordUpdate->any())
                    <div class="role-alert role-alert-danger">
                        <b>Could not change password.</b>
                        <span>{{ $errors->passwordUpdate->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('fleet.profile.password') }}" class="fleet-profile-form" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="field @error('current_password', 'passwordUpdate') field-invalid @enderror">
                        <label for="currentPassword">Current Password <span class="req">*</span></label>
                        <input id="currentPassword" type="password" name="current_password" autocomplete="current-password" required>
                        @error('current_password', 'passwordUpdate')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid">
                        <div class="field @error('new_password', 'passwordUpdate') field-invalid @enderror">
                            <label for="newPassword">New Password <span class="req">*</span></label>
                            <input id="newPassword" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
                            @error('new_password', 'passwordUpdate')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="newPasswordConfirmation">Confirm New Password <span class="req">*</span></label>
                            <input id="newPasswordConfirmation" type="password" name="new_password_confirmation" autocomplete="new-password" minlength="8" required>
                        </div>
                    </div>

                    <div class="hint">Use at least 8 characters. The new password and confirmation must match.</div>

                    <button type="submit" class="btn primary" data-loading-text="Updating...">Update Password</button>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection
