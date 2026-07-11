@props(['status', 'label' => null])

@php
    $normalizedStatus = str((string) $status)->lower()->replace([' ', '-'], '_')->toString();
    $classes = match ($normalizedStatus) {
        'approved', 'fully_received', 'posted', 'in', 'in_stock', 'matched' => 'status-success',
        'pending', 'partially_received', 'near_expiry', 'low', 'low_stock', 'adjustment' => 'status-warning',
        'rejected', 'expired', 'negative', 'negative_stock', 'out', 'mismatch' => 'status-danger',
        'submitted', 'transfer', 'batch_assignment' => 'status-info',
        'cancelled', 'closed', 'draft', 'zero' => 'status-neutral',
        default => 'status-neutral',
    };
@endphp

<span {{ $attributes->class('inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-black capitalize ring-1 '.$classes) }}>
    {{ $label ?: str($normalizedStatus)->replace('_', ' ')->title() }}
</span>
