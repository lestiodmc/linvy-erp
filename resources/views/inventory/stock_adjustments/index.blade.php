<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Stock Adjustments"
            subtitle="Count physical stock and post differences through inventory movements."
        >
            <x-slot:action>
                <a href="{{ route('stock-adjustments.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">New Adjustment</a>
            </x-slot:action>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <x-ui.filter-toolbar
            :action="route('stock-adjustments.index')"
            columns="lg:grid-cols-[minmax(14rem,1.4fr)_9rem_9rem_12rem_12rem_10rem_7rem_6rem]"
        >
            <x-ui.search-input :value="$filters['keyword'] ?? ''" />
            <x-ui.date-range :from="$filters['date_from'] ?? ''" :to="$filters['date_to'] ?? ''" />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches->pluck('name', 'id')->all()" all-label="All branches" />
            <x-ui.warehouse-filter :warehouses="$warehouses" :value="$filters['warehouse_id'] ?? ''" />
            <x-ui.select-filter
                name="status"
                label="Status"
                :value="$filters['status'] ?? ''"
                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status => str($status)->title()->toString()])->all()"
                all-label="All status"
            />
            <button class="h-10 rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white hover:bg-emerald-700">Apply</button>
            <a href="{{ route('stock-adjustments.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
        </x-ui.filter-toolbar>

        <x-ui.data-table>
            <x-slot:head>
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Number</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Warehouse</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Reason</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Total Lines</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Created By</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Posted At</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Action</th>
                </tr>
            </x-slot:head>

            @forelse($records as $record)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4 font-bold text-slate-900">{{ $record->number }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->adjustment_date?->format('Y-m-d') }}</td>
                    <td class="px-5 py-4 text-slate-600">
                        <div class="font-semibold text-slate-900">{{ $record->warehouse?->name }}</div>
                        <div class="text-xs text-slate-500">{{ $record->branch?->name }}</div>
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ str($record->reason)->limit(60) }}</td>
                    <td class="px-5 py-4"><x-ui.status-badge :status="$record->status" /></td>
                    <td class="px-5 py-4 text-right font-semibold text-slate-700">{{ $record->lines_count }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->createdBy?->name ?: '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->posted_at?->format('Y-m-d H:i') ?: '-' }}</td>
                    <td class="px-5 py-4 text-right"><a href="{{ route('stock-adjustments.show', $record) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a></td>
                </tr>
            @empty
                <x-ui.empty-state colspan="9" message="No stock adjustments yet." />
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
