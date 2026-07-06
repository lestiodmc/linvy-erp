@props(['status'])

@php
    $normalizedStatus = (string) $status;
    $label = str($normalizedStatus)->replace('_', ' ')->title();

    $classes = match ($normalizedStatus) {
        'submitted' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'approved', 'fully_received', 'posted' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'rejected', 'cancelled' => 'bg-red-50 text-red-700 ring-red-100',
        'partially_received' => 'bg-orange-50 text-orange-700 ring-orange-100',
        'closed' => 'bg-gray-100 text-gray-700 ring-gray-200',
        'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->class('inline-flex rounded-full px-2.5 py-1 text-xs font-black capitalize ring-1 '.$classes) }}>
    {{ $label }}
</span>
