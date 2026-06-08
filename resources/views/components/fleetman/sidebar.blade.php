<aside class="sidebar" id="fleetSidebar">
    <div class="logo-card">
        <div class="logo-mark">
            @if(!empty($brand['logo_url']))
                <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] ?? 'FleetMan Logo' }}" style="max-height: 96px; max-width: 100%; object-fit: contain;">
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
                    $itemAllowed = (bool) ($item['allowed'] ?? true);
                    $children = $item['children'] ?? [];
                    $hasChildren = count($children) > 0;
                    $isChildActive = false;

                    foreach ($children as &$child) {
                        $childAllowed = (bool) ($child['allowed'] ?? true);
                        $child['isActive'] = $childAllowed && $activeMenu === ($child['key'] ?? null);
                        if ($childAllowed && isset($child['routeParams']['action']) && request()->query('action') === $child['routeParams']['action'] && $activeMenu === ($item['key'] ?? null)) {
                            $child['isActive'] = true;
                        } elseif ($childAllowed && !request()->query('action') && str_ends_with($child['key'] ?? '', '-list') && $activeMenu === ($item['key'] ?? null)) {
                            $child['isActive'] = true;
                        }
                        if ($child['isActive']) {
                            $isChildActive = true;
                        }
                    }
                    unset($child);

                    $isActive = $itemAllowed && $activeMenu === $item['key'];
                    $isOpen = $itemAllowed && ($isActive || $isChildActive);
                    $href = $itemAllowed && ! empty($item['route']) && Route::has($item['route'])
                        ? route($item['route'], $item['routeParams'] ?? [])
                        : '#';
                @endphp

                <div
                    class="menu-block {{ $isOpen ? 'open' : '' }} {{ ! $itemAllowed ? 'rbac-menu-muted' : '' }}"
                    data-menu-block
                    data-menu-key="{{ $item['key'] }}"
                    data-route-active="{{ $isOpen ? '1' : '0' }}"
                >
                    <a href="{{ $href }}"
                       class="menu-item {{ $isOpen ? 'active' : '' }} {{ $hasChildren ? 'has-children' : '' }} {{ ! $itemAllowed ? 'rbac-muted' : '' }}"
                       @if(! $itemAllowed)
                           aria-disabled="true"
                           tabindex="-1"
                           title="Access not granted for your role"
                           data-rbac-disabled="true"
                       @elseif($hasChildren)
                           data-submenu-toggle="{{ $item['key'] }}"
                           aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                           aria-controls="submenu-{{ $item['key'] }}"
                       @endif
                    >
                        <span>{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                        @if(! $itemAllowed)
                            <span class="rbac-lock" aria-hidden="true">🔒</span>
                        @elseif($hasChildren)
                            <span class="submenu-arrow" aria-hidden="true">▾</span>
                        @endif
                    </a>

                    @if($hasChildren && $itemAllowed)
                        <div class="submenu" id="submenu-{{ $item['key'] }}">
                            @foreach($children as $child)
                                @php
                                    $childAllowed = (bool) ($child['allowed'] ?? true);
                                    $childHref = $childAllowed && ! empty($child['route']) && Route::has($child['route'])
                                        ? route($child['route'], $child['routeParams'] ?? [])
                                        : '#';
                                    $childActive = $childAllowed && ($child['isActive'] ?? false);
                                @endphp
                                <a href="{{ $childHref }}"
                                   class="submenu-item {{ $childActive ? 'active' : '' }} {{ ! $childAllowed ? 'rbac-muted' : '' }}"
                                   @if(! $childAllowed)
                                       aria-disabled="true"
                                       tabindex="-1"
                                       title="Access not granted for your role"
                                       data-rbac-disabled="true"
                                   @endif
                                >
                                    <span>{{ $child['icon'] ?? '↳' }}</span>
                                    <span>{{ $child['label'] }}</span>
                                    @if(! $childAllowed)<span class="rbac-lock" aria-hidden="true">🔒</span>@endif
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
