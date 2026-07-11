@props([
    'action',
    'method' => 'GET',
    'columns' => 'lg:grid-cols-[minmax(14rem,1.4fr)_9rem_9rem_10rem_minmax(12rem,1fr)_7rem_6rem]',
])

<form method="{{ $method }}" action="{{ $action }}" {{ $attributes->class('enterprise-filter-toolbar theme-card mb-3 rounded-lg p-2 shadow-sm') }}>
    <div class="{{ 'grid gap-2 sm:grid-cols-2 md:grid-cols-3 '.$columns }}">
        {{ $slot }}
    </div>
</form>
