<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $title }} Detail</h2>
            <div class="flex gap-2">
                <a href="{{ route($route.'.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Back</a>
                @if(count($fields) > 0)
                    <a href="{{ route($route.'.edit', $record) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">Edit</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div>
        <div class="mx-auto max-w-5xl">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-semibold text-slate-900">Record Information</h3>
                </div>
                <div class="p-6">
                <dl class="grid gap-x-6 gap-y-5 md:grid-cols-2">
                    @foreach($record->getAttributes() as $key => $value)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ str($key)->replace('_', ' ')->title() }}</dt>
                            <dd class="mt-1 break-words text-sm text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
