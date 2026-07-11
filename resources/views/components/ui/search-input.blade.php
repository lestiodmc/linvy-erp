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
        {{ $attributes->class('theme-surface h-10 w-full rounded-lg border px-3 text-sm') }}
    >
</div>
