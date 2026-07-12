<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Purchase Requests"
            subtitle="Request, submit, approve, and convert demand to PO."
        >
            <x-slot:action>
            <a href="{{ route('purchase-requests.create') }}" class="button-primary">New PR</a>
            </x-slot:action>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <x-ui.filter-toolbar
            :action="route('purchase-requests.index')"
            columns="lg:grid-cols-[minmax(16rem,1fr)_9rem_9rem_10rem_7rem_6rem]"
        >
            <x-ui.search-input :value="$filters['keyword'] ?? ''" />
            <x-ui.date-range :from="$filters['date_from'] ?? ''" :to="$filters['date_to'] ?? ''" />
            <x-ui.select-filter
                name="status"
                label="Status"
                :value="$filters['status'] ?? ''"
                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status => str($status)->replace('_', ' ')->title()])->all()"
                all-label="All status"
            />
            <button class="button-primary">Apply</button>
            <a href="{{ route('purchase-requests.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
        </x-ui.filter-toolbar>

        <x-ui.data-table title="Purchase Request List">
            <x-slot:head>
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Number</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Requested By</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Department</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Action</th>
                </tr>
            </x-slot:head>

            @forelse($records as $record)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4 font-bold text-slate-900">{{ $record->number }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->request_date?->format('Y-m-d') }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->branch?->name ?: '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->requester?->name }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->department ?: '-' }}</td>
                    <td class="px-5 py-4"><x-ui.status-badge :status="$record->status" /></td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('purchase-requests.show', $record) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a>
                    </td>
                </tr>
            @empty
                <x-ui.empty-state colspan="7" message="No purchase requests yet." />
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
