<aside class="sidebar">
    <div class="logo-card">
        <div class="logo-mark">
            🚙 {{ $brand['name'] ?? 'FleetMan' }}
            <small>{{ $brand['tagline'] ?? 'Fleet Management System' }}</small>
        </div>
    </div>

    <div class="account-card">
        <div class="avatar">{{ $account['avatar'] ?? '👤' }}</div>
        <div>
            <b>{{ $account['title'] ?? 'My Account' }}</b>
            <span>{{ $account['name'] ?? 'User' }}</span>
        </div>
    </div>

    <nav class="menu-nav">
        @foreach ($menuGroups as $group)
            <div class="menu-title">{{ $group['title'] }}</div>
            @foreach ($group['items'] as $item)
                @php
                    $isActive = $activeMenu === $item['key'];
                    $href = ! empty($item['route']) && Route::has($item['route']) ? route($item['route']) : '#';
                @endphp
                <a href="{{ $href }}" class="menu-item {{ $isActive ? 'active' : '' }}">
                    <span>{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>
</aside>
