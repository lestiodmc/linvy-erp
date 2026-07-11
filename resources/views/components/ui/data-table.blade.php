@props([
    'title' => null,
    'subtitle' => null,
    'count' => null,
    'sticky' => false,
    'density' => 'compact',
])

<div {{ $attributes->class('enterprise-table theme-card overflow-hidden rounded-lg shadow-sm') }} data-density="{{ $density }}">
    @if($title || isset($toolbar))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3" style="border-color: var(--theme-border)">
            <div class="min-w-0">
                @if($title)
                    <div class="flex items-center gap-2">
                        <h3 class="truncate text-sm font-black theme-text">{{ $title }}</h3>
                        @if($count !== null)<span class="theme-primary-soft rounded-full px-2 py-0.5 text-[10px] font-black tabular-nums">{{ number_format($count) }}</span>@endif
                    </div>
                    @if($subtitle)<p class="mt-0.5 text-xs theme-muted">{{ $subtitle }}</p>@endif
                @endif
            </div>
            @isset($toolbar)<div class="flex items-center gap-2">{{ $toolbar }}</div>@endisset
        </div>
    @endif

    @isset($loading)
        {{ $loading }}
    @endisset

    <div class="max-w-full overflow-x-auto overscroll-x-contain">
        <table class="min-w-full text-sm" x-init="$el.querySelectorAll('thead th').forEach((header) => header.setAttribute('scope', 'col'))">
            @isset($head)
                <thead @class(['theme-table-head', 'sticky top-0 z-10' => $sticky])>
                    {{ $head }}
                </thead>
            @endisset

            <tbody>
                {{ $slot }}
            </tbody>

            @isset($foot)
                <tfoot>
                    {{ $foot }}
                </tfoot>
            @endisset
        </table>
    </div>

    @isset($empty)
        {{ $empty }}
    @endisset
</div>
