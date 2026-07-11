<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Warehouse Transfers"
            subtitle="Move inventory between warehouses in the same branch."
        >
            <x-slot:action>
                <a href="{{ route('warehouse-transfers.create') }}" class="enterprise-create theme-focus rounded-lg px-4 py-2 text-sm font-bold">New Transfer</a>
            </x-slot:action>
        </x-ui.page-header>
    </x-slot>

    @php
        $formatQty = fn ($value) => number_format((float) $value, 2);
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : '-';
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <x-ui.filter-toolbar
            :action="route('warehouse-transfers.index')"
            columns="lg:grid-cols-[minmax(13rem,1.4fr)_9rem_9rem_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_10rem_7rem_6rem]"
        >
            <x-ui.search-input :value="$filters['keyword'] ?? ''" />
            <x-ui.date-range :from="$filters['date_from'] ?? ''" :to="$filters['date_to'] ?? ''" />
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies" all-label="All companies" />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches" all-label="All branches" />
            <x-ui.warehouse-filter :warehouses="$warehouses" name="from_warehouse_id" label="From Warehouse" :value="$filters['from_warehouse_id'] ?? ''" all-label="All from warehouses" />
            <x-ui.warehouse-filter :warehouses="$warehouses" name="to_warehouse_id" label="To Warehouse" :value="$filters['to_warehouse_id'] ?? ''" all-label="All to warehouses" />
            <x-ui.select-filter name="status" label="Status" :value="$filters['status'] ?? ''" :options="$statuses" all-label="All status" />
            <button class="h-10 rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white hover:bg-emerald-700">Apply</button>
            <a href="{{ route('warehouse-transfers.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
        </x-ui.filter-toolbar>

        <x-ui.data-table class="rounded-lg shadow-none">
            <x-slot:head>
                <tr>
                    <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Transfer No</th>
                    <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Company</th>
                    <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Branch</th>
                    <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Warehouse Flow</th>
                    <th class="px-3 py-2 text-right text-[11px] font-black uppercase tracking-wide text-slate-500">Total Lines</th>
                    <th class="px-3 py-2 text-right text-[11px] font-black uppercase tracking-wide text-slate-500">Total Qty</th>
                    <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Created By</th>
                    <th class="px-3 py-2 text-right text-[11px] font-black uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </x-slot:head>

            @forelse($records as $record)
                <tr class="text-xs">
                    <td class="whitespace-nowrap px-3 py-2 font-bold text-slate-900">{{ $record->number ?: 'DRAFT' }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $formatDate($record->transfer_date) }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $record->company?->name ?: '-' }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $record->branch?->name ?: '-' }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ $record->fromWarehouse?->name ?: '-' }}</span>
                            <span class="text-slate-400">→</span>
                            <span class="font-semibold">{{ $record->toWarehouse?->name ?: '-' }}</span>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-2 text-right font-semibold text-slate-700">{{ $record->lines_count }}</td>
                    <td class="whitespace-nowrap px-3 py-2 text-right font-semibold text-slate-700">{{ $formatQty($record->lines_sum_quantity) }}</td>
                    <td class="whitespace-nowrap px-3 py-2"><x-ui.status-badge :status="$record->status" /></td>
                    <td class="whitespace-nowrap px-3 py-2 text-slate-500">-</td>
                    <td class="whitespace-nowrap px-3 py-2 text-right">
                        <x-ui.table-action :href="route('warehouse-transfers.show', $record)" />
                    </td>
                </tr>
            @empty
                <x-ui.empty-state colspan="10" message="No warehouse transfers found." description="No transfer documents match the selected filters." />
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
