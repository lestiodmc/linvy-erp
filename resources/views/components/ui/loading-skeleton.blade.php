@props([
    'rows' => 5,
])

<div {{ $attributes->class('space-y-2 p-4') }}>
    @for($index = 0; $index < $rows; $index++)
        <div class="h-10 animate-pulse rounded-lg bg-slate-100"></div>
    @endfor
</div>
