@props(['account' => []])

@php
    $photoPath = trim((string) ($account['photo_path'] ?? ''));
    $initials = trim((string) ($account['initials'] ?? 'U'));
@endphp

<details class="fleet-user-menu" id="fleetUserMenu">
    <summary class="fleet-user-menu-trigger" aria-label="Open user account menu">
        <x-fleetman.entity-avatar
            :file="$photoPath"
            :fallback="$initials"
            :alt="($account['name'] ?? 'User').' profile picture'"
            size="compact"
            class="fleet-user-menu-avatar"
        />
        <span class="fleet-user-menu-copy">
            <strong>{{ $account['name'] ?? 'User' }}</strong>
            <small>{{ $account['title'] ?? 'My Account' }}</small>
        </span>
        <span class="fleet-user-menu-arrow" aria-hidden="true">▾</span>
    </summary>

    <div class="fleet-user-menu-panel">
        <div class="fleet-user-menu-head">
            <x-fleetman.entity-avatar
                :file="$photoPath"
                :fallback="$initials"
                :alt="($account['name'] ?? 'User').' profile picture'"
                size="compact"
            />
            <div>
                <strong>{{ $account['name'] ?? 'User' }}</strong>
                <small>{{ $account['email'] ?? '' }}</small>
            </div>
        </div>

        <a href="{{ route('fleet.profile') }}" class="fleet-user-menu-link">
            <span aria-hidden="true">👤</span>
            <span>My Profile</span>
        </a>
        <a href="{{ route('fleet.profile') }}#change-password" class="fleet-user-menu-link">
            <span aria-hidden="true">🔐</span>
            <span>Change Password</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="fleet-user-menu-logout">
            @csrf
            <button type="submit" data-loading-text="Signing out...">
                <span aria-hidden="true">↪</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</details>
