<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $title }}</h2>
            @if(count($fields) > 0)
                <a href="{{ route($route.'.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">New</a>
            @endif
        </div>
    </x-slot>

    <div>
        <div class="mx-auto max-w-7xl">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">{{ $title }} List</h3>
                        <p class="mt-1 text-sm text-slate-500">Manage and review records in this module.</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                @foreach($columns as $column)
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">{{ str($column)->afterLast('.')->replace('_', ' ')->title() }}</th>
                                @endforeach
                                <th class="w-32 px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($records as $record)
                                <tr class="hover:bg-slate-50/80">
                                    @foreach($columns as $column)
                                        @php $cellValue = data_get($record, $column); @endphp
                                        <td class="whitespace-nowrap px-5 py-3 text-slate-700">
                                            @if(str($column)->contains('status') || str($column)->contains('is_active'))
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700">{{ is_bool($cellValue) ? ($cellValue ? 'active' : 'inactive') : $cellValue }}</span>
                                            @else
                                                {{ is_array($cellValue) ? implode(', ', $cellValue) : $cellValue }}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        <a href="{{ route($route.'.show', $record) }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) + 1 }}" class="px-5 py-10 text-center text-slate-500">No records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">{{ $records->links() }}</div>
        </div>
    </div>
</x-app-layout>
