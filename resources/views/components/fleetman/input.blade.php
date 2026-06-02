@props([
    'id',
    'name' => null,
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'required' => false,
    'readonly' => false,
    'value' => null,
    'hint' => null,
])

<div class="field">
    @if($label !== null && $label !== '')
        <label for="{{ $id }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>
    @endif
    <input
        id="{{ $id }}"
        name="{{ $name ?? $id }}"
        type="{{ $type }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($readonly) readonly @endif
        @if($value !== null) value="{{ $value }}" @endif
        {{ $attributes }}
    >
    @if($hint)<div class="hint">{{ $hint }}</div>@endif
</div>
