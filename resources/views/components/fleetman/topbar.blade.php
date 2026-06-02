<div class="topbar">
    <div class="breadcrumb">
        <a href="{{ route('fleet.vehicles') }}">HOME</a>
        @foreach ($items ?? [] as $item)
            <span>/</span>
            @if (! empty($item['route']) && Route::has($item['route']))
                <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
            @else
                <a>{{ $item['label'] }}</a>
            @endif
        @endforeach
    </div>
    <div class="top-actions">
        {{ $actions ?? '' }}
    </div>
</div>
