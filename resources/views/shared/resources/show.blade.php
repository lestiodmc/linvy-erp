<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">{{ $title }} Detail</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Review record information</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route($route.'.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>
                @if(count($fields) > 0)
                    <a href="{{ route($route.'.edit', $record) }}" class="button-primary">Edit</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="enterprise-detail mx-auto max-w-5xl">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-black text-slate-950">Record Information</h3>
            </div>
            <div class="p-6">
                <dl class="grid gap-x-6 gap-y-5 md:grid-cols-2">
                    @foreach($record->getAttributes() as $key => $value)
                        <div>
                            <dt class="text-sm font-bold text-slate-500">{{ str($key)->replace('_', ' ')->title() }}</dt>
                            <dd class="mt-1 break-words text-sm font-medium text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
