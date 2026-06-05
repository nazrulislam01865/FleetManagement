<aside class="sidebar" id="fleetSidebar">
    <div class="logo-card">
        <div class="logo-mark">
            @if(!empty($brand['logo_url']))
                <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] ?? 'FleetMan Logo' }}" style="max-height: 32px;">
            @else
                🚙 {{ $brand['name'] ?? 'FleetMan' }}
                <small>{{ $brand['tagline'] ?? 'Fleet Management System' }}</small>
            @endif
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

                <div class="menu-block {{ $isOpen ? 'open' : '' }}" data-menu-block data-menu-key="{{ $item['key'] }}">
                    <a href="{{ $href }}"
                       class="menu-item {{ $isOpen ? 'active' : '' }} {{ $hasChildren ? 'has-children' : '' }}"
                       @if($hasChildren)
                           data-submenu-toggle="{{ $item['key'] }}"
                           aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                           aria-controls="submenu-{{ $item['key'] }}"
                       @endif
                    >
                        <span>{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if($hasChildren)
                            <span class="submenu-arrow" aria-hidden="true">▾</span>
                        @endif
                    </a>

                    @if($hasChildren)
                        <div class="submenu" id="submenu-{{ $item['key'] }}">
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
