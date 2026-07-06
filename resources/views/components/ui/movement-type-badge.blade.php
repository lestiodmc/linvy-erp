@props(['type'])

@php
    $normalized = strtoupper(str_replace('_', '-', (string) $type));

    [$label, $classes] = match ($normalized) {
        'IN', 'RCV', 'PURCHASE-RECEIVE' => ['Purchase Receive', 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
        'TRF-IN', 'TRF-OUT', 'TRANSFER-IN', 'TRANSFER-OUT' => ['Warehouse Transfer', 'bg-blue-50 text-blue-700 ring-blue-100'],
        'ADJ-IN', 'ADJ-OUT', 'ADJUSTMENT-PLUS', 'ADJUSTMENT-MINUS' => ['Stock Adjustment', 'bg-orange-50 text-orange-700 ring-orange-100'],
        'DO', 'SALE-DELIVERY' => ['Sales Delivery', 'bg-red-50 text-red-700 ring-red-100'],
        'PRODUCTION-OUTPUT' => ['Production Output', 'bg-purple-50 text-purple-700 ring-purple-100'],
        'PRODUCTION-INPUT', 'SERVICE' => ['Production Consumption', 'bg-gray-100 text-gray-700 ring-gray-200'],
        default => [str($type ?: 'Unknown')->replace(['_', '-'], ' ')->title()->toString(), 'bg-slate-100 text-slate-700 ring-slate-200'],
    };
@endphp

<span {{ $attributes->class('inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 '.$classes) }}>
    {{ $label }}
</span>
