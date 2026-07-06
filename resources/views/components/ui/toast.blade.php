@props([
    'type' => 'success',
    'message' => null,
])

@php
    $classes = match ($type) {
        'error' => 'border-red-200 bg-red-50 text-red-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'info' => 'border-blue-200 bg-blue-50 text-blue-800',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-800',
    };
@endphp

@if($message || ! $slot->isEmpty())
    <div {{ $attributes->class('mb-4 rounded-lg border px-4 py-3 text-sm font-medium '.$classes) }}>
        {{ $message ?: $slot }}
    </div>
@endif
