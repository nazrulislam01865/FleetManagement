@props(['title'])

<div {{ $attributes->merge(['class' => 'side-note']) }}>
    <h3>{{ $title }}</h3>
    {{ $slot }}
</div>
