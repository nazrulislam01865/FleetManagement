@props(['title', 'subtitle'])

<section {{ $attributes->merge(['class' => 'title-card']) }}>
    <div>
        <h1>{{ $title }}</h1>
    </div>
    @if (isset($action))
        <div>{{ $action }}</div>
    @endif
</section>
