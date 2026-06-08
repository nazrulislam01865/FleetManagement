@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="section-head">
        <div>
            <h2>{{ $title }}</h2>
            @if($description)
                <p>{{ $description }}</p>
            @endif
        </div>
        @if(isset($action))
            <div>{{ $action }}</div>
        @endif
    </div>
    {{ $slot }}
</div>
