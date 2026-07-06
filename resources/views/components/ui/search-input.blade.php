@props([
    'name' => 'keyword',
    'value' => null,
    'placeholder' => 'Keyword',
    'label' => 'Keyword',
])

<div>
    <label class="sr-only" for="{{ $name }}">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->class('h-10 w-full rounded-lg border-slate-200 px-3 text-sm focus:border-emerald-500 focus:ring-emerald-500') }}
    >
</div>
