@props([
    'name',
    'label' => null,
    'value' => null,
    'options' => [],
    'allLabel' => 'All',
])

@php
    $fieldLabel = $label ?: str($name)->replace('_', ' ')->title();
@endphp

<div>
    <label class="sr-only" for="{{ $name }}">{{ $fieldLabel }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        aria-label="{{ $fieldLabel }}"
        {{ $attributes->class('h-10 w-full rounded-lg border-slate-200 px-3 text-sm focus:border-emerald-500 focus:ring-emerald-500') }}
    >
        <option value="">{{ $allLabel }}</option>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
