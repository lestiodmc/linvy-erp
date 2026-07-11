@props(['columns' => 3])

@php
    $grid = match ((int) $columns) {
        2 => 'lg:grid-cols-2',
        4 => 'lg:grid-cols-4',
        default => 'lg:grid-cols-3',
    };
@endphp

<div {{ $attributes->class("grid grid-cols-1 gap-3 sm:grid-cols-2 {$grid}") }}>{{ $slot }}</div>
