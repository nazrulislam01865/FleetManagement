@props([
    'id',
    'name' => null,
    'label' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
])

<div class="field">
    @if($label !== null && $label !== '')
        <label for="{{ $id }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name ?? $id }}" @if($placeholder) placeholder="{{ $placeholder }}" @endif {{ $attributes }}></textarea>
    @if($hint)<div class="hint">{{ $hint }}</div>@endif
</div>
