<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">{{ $title }}</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Manage {{ str($title)->lower() }} records</p>
            </div>
            @if(count($fields) > 0)
                <a href="{{ route($route.'.create') }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white shadow-sm shadow-emerald-900/10 hover:bg-emerald-700">New</a>
            @endif
        </div>
    </x-slot>

    @php
        $badgeClass = function ($value): string {
            $normalized = is_bool($value) ? ($value ? 'active' : 'inactive') : (string) $value;

            return match ($normalized) {
                'active', 'approved', 'posted', 'received', 'delivered', 'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'draft', 'pending' => 'bg-slate-100 text-slate-700 ring-slate-200',
                'cancelled', 'inactive' => 'bg-red-50 text-red-700 ring-red-100',
                'partially_received', 'partially_delivered' => 'bg-amber-50 text-amber-700 ring-amber-100',
                default => 'bg-blue-50 text-blue-700 ring-blue-100',
            };
        };
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-950">{{ $title }} List</h3>
                    <p class="mt-1 text-sm text-slate-500">Manage and review records in this module.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <label class="relative block">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" /></svg>
                        </span>
                        <input type="search" placeholder="Search in this list..." class="w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm font-medium text-slate-700 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 sm:w-72">
                    </label>
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">Filter</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach($columns as $column)
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">{{ str($column)->afterLast('.')->replace('_', ' ')->title() }}</th>
                            @endforeach
                            <th class="w-32 px-5 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($records as $record)
                            <tr class="hover:bg-slate-50/80">
                                @foreach($columns as $column)
                                    @php $cellValue = data_get($record, $column); @endphp
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-700">
                                        @if(str($column)->contains('status') || str($column)->contains('is_active'))
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black capitalize ring-1 {{ $badgeClass($cellValue) }}">{{ is_bool($cellValue) ? ($cellValue ? 'active' : 'inactive') : str($cellValue)->replace('_', ' ') }}</span>
                                        @else
                                            {{ is_array($cellValue) ? implode(', ', $cellValue) : $cellValue }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a href="{{ route($route.'.show', $record) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) + 1 }}" class="px-5 py-14 text-center">
                                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10" /></svg>
                                    </div>
                                    <p class="mt-3 text-sm font-bold text-slate-700">No records yet</p>
                                    <p class="mt-1 text-sm text-slate-500">Create the first record to start using this module.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</x-app-layout>
