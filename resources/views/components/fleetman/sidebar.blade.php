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
                    $children = $item['children'] ?? [];
                    $hasChildren = count($children) > 0;
                    $isChildActive = false;

                    foreach ($children as $child) {
                        if ($activeMenu === ($child['key'] ?? null)) {
                            $isChildActive = true;
                            break;
                        }
                    }

                    $isActive = $activeMenu === $item['key'];
                    $isOpen = $isActive || $isChildActive;
                    $href = ! empty($item['route']) && Route::has($item['route']) ? route($item['route']) : '#';
                @endphp

                <div class="menu-block {{ $isOpen ? 'open' : '' }}">
                    <a href="{{ $href }}" class="menu-item {{ $isOpen ? 'active' : '' }} {{ $hasChildren ? 'has-children' : '' }}">
                        <span>{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if($hasChildren)
                            <span class="submenu-arrow">▾</span>
                        @endif
                    </a>

                    @if($hasChildren)
                        <div class="submenu">
                            @foreach($children as $child)
                                @php
                                    $childHref = ! empty($child['route']) && Route::has($child['route']) ? route($child['route']) : '#';
                                    $childActive = $activeMenu === ($child['key'] ?? null);
                                @endphp
                                <a href="{{ $childHref }}" class="submenu-item {{ $childActive ? 'active' : '' }}">
                                    <span>{{ $child['icon'] ?? '↳' }}</span>
                                    <span>{{ $child['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    </nav>

    @auth
        <form method="POST" action="{{ route('logout') }}" class="logout-form logout-form-bottom">
            @csrf
            <button type="submit">↪ Logout</button>
        </form>
    @endauth
</aside>
