@props(['action', 'method' => 'GET', 'title' => null, 'subtitle' => null, 'resultCount' => null])

<form method="{{ $method }}" action="{{ $action }}" {{ $attributes->class('enterprise-filter-panel theme-card mb-3 min-w-0 rounded-lg') }}>
    @if ($title || $subtitle)
        <header class="enterprise-filter-header">
            @if ($title)
                <h2 class="text-sm font-black">{{ $title }}</h2>
            @endif

            @if ($subtitle)
                <p class="theme-muted text-xs">{{ $subtitle }}</p>
            @endif
        </header>
    @endif

    <div class="enterprise-filter-grid">
        {{ $slot }}
    </div>

    @if (! is_null($resultCount) || (isset($summary) && filled($summary)) || isset($actions))
        <footer class="enterprise-filter-actions">
            <div class="theme-muted text-xs font-semibold">
                @if (! is_null($resultCount))
                    {{ number_format($resultCount) }} results
                @endif

                @if (isset($summary) && filled($summary))
                    {{ $summary }}
                @endif
            </div>

            @isset($actions)
                <div class="enterprise-filter-buttons">
                    {{ $actions }}
                </div>
            @endisset
        </footer>
    @endif
</form>
