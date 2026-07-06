@props([
    'action',
    'method' => 'GET',
    'columns' => 'lg:grid-cols-[minmax(14rem,1.4fr)_9rem_9rem_10rem_minmax(12rem,1fr)_7rem_6rem]',
])

<form method="{{ $method }}" action="{{ $action }}" {{ $attributes->class('mb-2 rounded-lg border border-slate-200 bg-white p-2 shadow-sm') }}>
    <div class="{{ 'grid gap-2 sm:grid-cols-2 md:grid-cols-3 '.$columns }}">
        {{ $slot }}
    </div>
</form>
