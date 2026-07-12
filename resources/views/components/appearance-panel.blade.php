@php
    $groups = [
        'mode' => [['light','Light'], ['dark','Dark'], ['system','System']],
        'accent' => [['emerald','Emerald'], ['blue','Blue'], ['purple','Purple'], ['rose','Rose'], ['amber','Amber'], ['teal','Teal'], ['slate','Slate']],
        'density' => [['comfortable','Comfortable'], ['compact','Compact']],
        'sidebar' => [['expanded','Expanded'], ['compact','Compact']],
    ];
@endphp
<div class="relative" x-data="appearancePanel" @click.outside="close()" @keydown.escape.window="open && close(true)">
    <button x-ref="trigger" type="button" class="theme-surface theme-focus grid h-10 w-10 place-items-center rounded-xl border shadow-sm" @click="open = !open" :aria-expanded="open" aria-controls="appearance-panel" aria-haspopup="dialog" aria-label="Open appearance settings">
        <svg class="h-5 w-5 theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 1 0 0 18h1.2a1.8 1.8 0 0 0 0-3.6h-.6a1.5 1.5 0 0 1 0-3H15a6 6 0 0 0 0-12h-3Zm-4 6h.01M11 6h.01M6 13h.01" /></svg>
    </button>
    <section id="appearance-panel" x-show="open" x-cloak x-transition class="appearance-panel theme-dropdown absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-1rem))] rounded-xl p-3 shadow-2xl" role="dialog" aria-modal="false" aria-labelledby="appearance-title">
        <div class="mb-3 flex items-center justify-between"><h2 id="appearance-title" class="text-sm font-black">Appearance</h2><button type="button" class="theme-focus rounded-lg p-1 theme-muted sm:hidden" @click="close(true)" aria-label="Close appearance settings">✕</button></div>
        @foreach($groups as $preference => $options)
            <fieldset class="appearance-group {{ !$loop->first ? 'mt-3' : '' }}" @if($preference === 'sidebar') data-sidebar-setting @endif>
                <legend class="mb-1.5 text-[10px] font-black uppercase tracking-[.14em] theme-muted">{{ $preference }}</legend>
                <div class="grid gap-1.5 {{ $preference === 'accent' ? 'grid-cols-4' : 'grid-cols-3' }}" role="radiogroup" aria-label="{{ ucfirst($preference) }} preference">
                    @foreach($options as [$value, $label])
                        <button type="button" class="appearance-option theme-focus relative flex min-h-10 items-center justify-center gap-1.5 rounded-lg border px-2 py-2 text-xs font-bold" :class="{{ $preference }} === '{{ $value }}' ? 'is-selected' : ''" @click="choose('{{ $preference }}', '{{ $value }}')" role="radio" :aria-checked="{{ $preference }} === '{{ $value }}'">
                            @if($preference === 'accent')<span class="appearance-swatch" data-swatch="{{ $value }}" aria-hidden="true"></span>@endif
                            <span>{{ $label }}</span><svg x-show="{{ $preference }} === '{{ $value }}'" class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 12 4 4L19 6" /></svg>
                        </button>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </section>
</div>
