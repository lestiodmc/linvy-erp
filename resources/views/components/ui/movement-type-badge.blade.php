@props(['type'])

@php
    $normalized = strtoupper(str_replace('_', '-', (string) $type));

    $label = \App\Models\Inventory\StockMovement::typeLabel((string) $type);
    $classes = match ($normalized) {
        'IN', 'RCV', 'RECEIVE', 'PURCHASE-RECEIVE', 'TRANSFER-IN', 'TRF-IN', 'ADJUSTMENT-IN', 'ADJ-IN', 'BATCH-ASSIGNMENT-IN', 'RETURN-IN', 'PRODUCTION-OUTPUT' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'OUT', 'DO', 'SALE-DELIVERY', 'TRANSFER-OUT', 'TRF-OUT', 'ADJUSTMENT-OUT', 'ADJ-OUT', 'BATCH-ASSIGNMENT-OUT', 'RETURN-OUT', 'PRODUCTION-INPUT', 'SERVICE' => 'bg-red-50 text-red-700 ring-red-100',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->class('inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 '.$classes) }}>
    {{ $label }}
</span>
