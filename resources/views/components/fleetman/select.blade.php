@props([
    'id',
    'name' => null,
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'value' => '',
    'hint' => null,
])

<div class="field">
    @if($label !== null && $label !== '')
        <label for="{{ $id }}">{{ $label }} @if($required)<span class="req">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name ?? $id }}" @if($required) required aria-required="true" @endif {{ $attributes }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            @php
                $realValue = is_int($optionValue) ? $optionLabel : $optionValue;
                $realLabel = is_array($optionLabel) ? ($optionLabel['label'] ?? $realValue) : $optionLabel;
            @endphp
            <option value="{{ $realValue }}" @selected($value === $realValue)>{{ $realLabel }}</option>
        @endforeach
    </select>
    @if($hint)<div class="hint">{{ $hint }}</div>@endif
</div>
