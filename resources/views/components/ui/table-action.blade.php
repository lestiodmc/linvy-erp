@props(['href', 'label' => 'Open'])

<a href="{{ $href }}" {{ $attributes->class('theme-link theme-focus inline-flex h-7 items-center rounded-md px-2 text-xs font-bold hover:underline') }}>
    {{ $label }}
</a>
