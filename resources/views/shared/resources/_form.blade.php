<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record ? 'Edit '.$title : 'New '.$title }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">{{ $record ? 'Update existing record' : 'Create a new record' }}</p>
        </div>
    </x-slot>

    @php
        $fieldSections = collect($fields)->chunk(max(1, (int) ceil(max(count($fields), 1) / 2)));
        $sectionTitles = ['General Information', 'Detail Information'];
    @endphp

    <div class="mx-auto max-w-4xl">
        <form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-black text-slate-950">{{ $record ? 'Edit Details' : 'Create Details' }}</h3>
                <p class="mt-1 text-sm text-slate-500">Fill in the required information for this record.</p>
            </div>

            <div class="space-y-8 p-6">
                @foreach($fieldSections as $sectionIndex => $sectionFields)
                    <section>
                        <div class="mb-4">
                            <h4 class="text-sm font-black uppercase tracking-wide text-slate-700">{{ $sectionTitles[$sectionIndex] ?? 'Status / Notes' }}</h4>
                            <div class="mt-2 h-px bg-slate-100"></div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            @foreach($sectionFields as $name => $field)
                                @include('shared.resources.field', ['name' => $name, 'field' => $field])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <a href="{{ route($route.'.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-emerald-900/10 hover:bg-emerald-700">Save</button>
            </div>
        </form>
    </div>
</x-app-layout>
