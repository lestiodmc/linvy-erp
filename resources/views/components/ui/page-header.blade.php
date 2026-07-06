@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('flex items-center justify-between gap-4') }}>
    <div class="min-w-0">
        @isset($breadcrumb)
            <div class="mb-1">{{ $breadcrumb }}</div>
        @endisset

        <h1 class="truncate text-xl font-black text-slate-950">{{ $title }}</h1>

        @if($subtitle)
            <p class="mt-0.5 text-sm font-medium text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
