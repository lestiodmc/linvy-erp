<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Purchase Orders"
            subtitle="Direct PO or converted from approved PR."
        >
            <x-slot:action>
            <a href="{{ route('purchase-orders.create') }}" class="enterprise-create theme-focus rounded-lg px-4 py-2 text-sm font-bold">New Direct PO</a>
            </x-slot:action>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <x-ui.filter-toolbar
            :action="route('purchase-orders.index')"
            columns="lg:grid-cols-[minmax(14rem,1.4fr)_9rem_9rem_10rem_10rem_minmax(12rem,1fr)_7rem_6rem]"
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
            <x-ui.select-filter
                name="branch_id"
                label="Branch"
                :value="$filters['branch_id'] ?? ''"
                :options="$branches->pluck('name', 'id')->all()"
                all-label="All branches"
            />
            <x-ui.select-filter
                name="supplier_id"
                label="Supplier"
                :value="$filters['supplier_id'] ?? ''"
                :options="$suppliers->mapWithKeys(fn ($supplier) => [$supplier->id => trim(($supplier->code ? $supplier->code.' - ' : '').$supplier->name)])->all()"
                all-label="All suppliers"
            />
            <button class="h-10 rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white hover:bg-emerald-700">Apply</button>
            <a href="{{ route('purchase-orders.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
        </x-ui.filter-toolbar>

        <x-ui.data-table>
            <x-slot:head>
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Number</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Supplier</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Branch</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">PR</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Date</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Grand Total</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Action</th>
                </tr>
            </x-slot:head>

            @forelse($records as $record)
                <tr>
                    <td class="px-5 py-4 font-bold text-slate-900">{{ $record->number }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->supplier?->name }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->branch?->name ?: '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->purchaseRequest?->number ?: '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $record->order_date?->format('Y-m-d') }}</td>
                    <td class="px-5 py-4"><x-ui.status-badge :status="$record->status" /></td>
                    <td class="px-5 py-4 text-right font-semibold text-slate-900">{{ number_format($record->grand_total, 2) }}</td>
                    <td class="px-5 py-4 text-right"><x-ui.table-action :href="route('purchase-orders.show', $record)" /></td>
                </tr>
            @empty
                <x-ui.empty-state colspan="8" message="No purchase orders found." description="Try changing the filters or create a new purchase order." />
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
