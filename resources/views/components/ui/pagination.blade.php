@props(['records'])

@if($records->hasPages())
    <div {{ $attributes->class('theme-surface mt-3 flex flex-col gap-2 rounded-lg border px-3 py-2 sm:flex-row sm:items-center sm:justify-between') }}>
        <p class="text-xs theme-muted">Showing <b class="theme-text">{{ number_format($records->firstItem()) }}–{{ number_format($records->lastItem()) }}</b> of <b class="theme-text">{{ number_format($records->total()) }}</b> records</p>
        <div class="enterprise-pagination">{{ $records->onEachSide(1)->links() }}</div>
    </div>
@elseif($records->total() > 0)
    <p {{ $attributes->class('mt-2 text-right text-xs theme-muted') }}>Showing {{ number_format($records->count()) }} of {{ number_format($records->total()) }} records</p>
@endif
