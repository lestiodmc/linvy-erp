@props(['align' => 'left', 'numeric' => false, 'action' => false])

@php
    $alignment = $action || $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : 'text-left');
@endphp

<td {{ $attributes->class("enterprise-table-cell {$alignment} ".($numeric ? 'tabular-nums' : '').($action ? ' whitespace-nowrap' : '')) }}>
    {{ $slot }}
</td>
