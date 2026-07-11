@props(['align' => 'left', 'sortable' => false])

@php
    $alignment = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
@endphp

<th scope="col" {{ $attributes->class("enterprise-table-head whitespace-nowrap px-3 py-2 {$alignment}") }}>
    {{ $slot }}
</th>
