@props([
    'type',
    'number',
    'status' => null,
    'subtitle' => null,
    'metadata' => [],
    'actionUrl' => null,
    'actionLabel' => 'View Source',
])

<section {{ $attributes->class('source-document-summary theme-card rounded-lg p-4') }}>
    <div class="grid items-center gap-4 md:grid-cols-[minmax(16rem,1.5fr)_repeat(3,minmax(8rem,.7fr))_auto]">
        <div class="min-w-0">
            <p class="text-[10px] font-black uppercase tracking-wide theme-muted">{{ $type }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <span class="font-mono text-base font-black theme-text">{{ $number }}</span>
                @if($status)<x-ui.status-badge :status="$status" />@endif
            </div>
            @if($subtitle)<p class="mt-0.5 truncate text-sm font-semibold theme-muted">{{ $subtitle }}</p>@endif
        </div>

        @foreach(collect($metadata)->filter(fn ($item) => filled($item['value'] ?? null))->take(3) as $item)
            <div>
                <p class="text-[10px] font-black uppercase tracking-wide theme-muted">{{ $item['label'] }}</p>
                <p class="mt-1 text-sm font-bold tabular-nums">{{ $item['value'] }}</p>
            </div>
        @endforeach

        @if($actionUrl)
            <a href="{{ $actionUrl }}" class="enterprise-action theme-focus inline-flex h-9 items-center justify-center rounded-lg px-3 text-xs font-bold" aria-label="{{ $actionLabel }} {{ $number }}">
                {{ $actionLabel }}
                <svg class="ml-1.5 h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17 17 7M8 7h9v9" /></svg>
            </a>
        @endif
    </div>
</section>
