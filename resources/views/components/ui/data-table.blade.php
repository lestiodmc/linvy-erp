@props([
    'title' => null,
    'sticky' => false,
])

<div {{ $attributes->class('overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm') }}>
    @if($title)
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-base font-black text-slate-950">{{ $title }}</h3>
        </div>
    @endif

    @isset($loading)
        {{ $loading }}
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            @isset($head)
                <thead @class(['bg-slate-50', 'sticky top-0 z-10' => $sticky])>
                    {{ $head }}
                </thead>
            @endisset

            <tbody class="divide-y divide-slate-100">
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
