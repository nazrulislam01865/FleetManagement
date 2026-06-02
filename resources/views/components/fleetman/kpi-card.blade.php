@props(['id', 'label', 'value' => '0'])

<div class="card">
    <strong id="{{ $id }}">{{ $value }}</strong>
    <span>{{ $label }}</span>
</div>
