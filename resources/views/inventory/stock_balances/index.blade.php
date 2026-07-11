<x-app-layout>
    <x-slot name="header"><x-ui.page-header title="Stock Balances" subtitle="Current stock by item and warehouse." /></x-slot>
    @php
        $qty = fn ($value) => number_format((float) $value, 2);
        $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '-';
        $onHand = fn ($row) => (float) ($row->qty_on_hand ?: $row->quantity_on_hand ?: 0);
        $reserved = fn ($row) => (float) ($row->qty_reserved ?: $row->quantity_reserved ?: 0);
        $uom = fn ($row) => $row->baseUom?->code ?: $row->uom?->code ?: $row->item?->baseUnit?->code ?: '-';
        $cell = 'px-3 py-2'; $head = 'px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500';
        $stockBadge = function ($row) use ($onHand) { $value = $onHand($row); $min = (float) ($row->item?->minimum_order_qty ?? 0); return $value < 0 ? ['NEGATIVE', 'bg-red-50 text-red-700 ring-red-100'] : ($value == 0 ? ['ZERO', 'bg-slate-100 text-slate-700 ring-slate-200'] : ($min > 0 && $value <= $min ? ['LOW', 'bg-amber-50 text-amber-700 ring-amber-100'] : ['IN STOCK', 'bg-emerald-50 text-emerald-700 ring-emerald-100'])); };
    @endphp
    <div class="mx-auto max-w-screen-2xl">
        <x-ui.filter-toolbar :action="route('stock-balances.index')" columns="lg:grid-cols-[minmax(13rem,1.3fr)_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_9rem_9rem_9rem_7rem_6rem]">
            <x-ui.search-input :value="$filters['keyword'] ?? ''" />
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies" all-label="All companies" />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches" all-label="All branches" />
            <x-ui.warehouse-filter :warehouses="$warehouses" :value="$filters['warehouse_id'] ?? ''" />
            <x-ui.select-filter name="item_category_id" label="Category" :value="$filters['item_category_id'] ?? ''" :options="$itemCategories" all-label="All categories" />
            <x-ui.select-filter name="stock_status" label="Stock status" :value="$filters['stock_status'] ?? ''" :options="$stockStatuses" all-label="All stock" />
            <x-ui.select-filter name="batch_tracking" label="Batch tracking" :value="$filters['batch_tracking'] ?? ''" :options="$batchTrackingOptions" all-label="All items" />
            <x-ui.select-filter name="reconciliation_status" label="Reconciliation" :value="$filters['reconciliation_status'] ?? ''" :options="$reconciliationStatuses" all-label="All" />
            <button class="h-10 rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white hover:bg-emerald-700">Apply</button><a href="{{ route('stock-balances.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700">Reset</a>
        </x-ui.filter-toolbar>
        <x-ui.data-table class="rounded-lg shadow-none"><x-slot:head><tr>
            <th class="{{ $head }}">Company</th><th class="{{ $head }}">Branch</th><th class="{{ $head }}">Warehouse</th><th class="{{ $head }}">SKU</th><th class="{{ $head }}">Item Name</th><th class="{{ $head }}">Category</th><th class="{{ $head }}">Batch</th><th class="{{ $head }} text-right">On Hand</th><th class="{{ $head }} text-right">Reserved</th><th class="{{ $head }} text-right">Available</th><th class="{{ $head }}">UOM</th><th class="{{ $head }}">Stock Status</th><th class="{{ $head }}">Last Updated</th><th class="{{ $head }}">Action</th>
        </tr></x-slot:head>
        @forelse($records as $record) @php($on = $onHand($record)) @php($res = $reserved($record)) @php($status = $stockBadge($record)) @php($tracked = (bool) $record->item?->is_batch_tracked) @php($difference = $on - (float) $record->batch_total)
        <tr class="text-xs hover:bg-slate-50"><td class="{{ $cell }}">{{ $record->company?->name ?: $record->warehouse?->company?->name ?: '-' }}</td><td class="{{ $cell }}">{{ $record->branch?->name ?: $record->warehouse?->branch?->name ?: '-' }}</td><td class="{{ $cell }} font-semibold">{{ $record->warehouse?->name ?: '-' }}</td><td class="{{ $cell }} font-bold">{{ $record->item?->sku ?: '-' }}</td><td class="{{ $cell }} max-w-48 truncate">{{ $record->item?->name ?: '-' }}</td><td class="{{ $cell }}">{{ $record->item?->category?->name ?: '-' }}</td><td class="{{ $cell }}">{{ $tracked ? 'Batch' : 'No Batch' }} @if($tracked)<span class="ml-1 inline-flex rounded-full px-1.5 py-0.5 font-bold ring-1 {{ abs($difference) <= 0.000001 ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-red-50 text-red-700 ring-red-100' }}">{{ abs($difference) <= 0.000001 ? 'MATCHED' : 'MISMATCH' }}</span>@endif</td><td class="{{ $cell }} text-right font-bold {{ $on < 0 ? 'text-red-700' : '' }}">{{ $qty($on) }}</td><td class="{{ $cell }} text-right">{{ $qty($res) }}</td><td class="{{ $cell }} text-right font-bold {{ $on - $res < 0 ? 'text-red-700' : '' }}">{{ $qty($on - $res) }}</td><td class="{{ $cell }}">{{ $uom($record) }}</td><td class="{{ $cell }}"><span class="inline-flex rounded-full px-2 py-0.5 font-bold ring-1 {{ $status[1] }}">{{ $status[0] }}</span></td><td class="{{ $cell }}">{{ $date($record->last_movement_at ?: $record->updated_at) }}</td><td class="{{ $cell }} whitespace-nowrap"><a href="{{ route('item-ledger.index', ['item_id'=>$record->item_id,'warehouse_id'=>$record->warehouse_id,'company_id'=>$record->company_id,'branch_id'=>$record->branch_id]) }}" class="font-bold text-emerald-700">Ledger</a>@if($tracked)<a href="{{ route('stock-balances.batches', $record) }}" class="ml-2 font-bold text-slate-700">Batches</a>@endif</td></tr>
        @empty <x-ui.empty-state colspan="14" message="No stock balances found." /> @endforelse
        </x-ui.data-table><x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
