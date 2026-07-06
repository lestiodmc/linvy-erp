<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Stock Movements"
            subtitle="Inventory audit trail by item, warehouse, and source document."
        />
    </x-slot>

    @php
        $movementDate = fn ($record) => $record->transaction_date ?: $record->movement_date;
        $movementLabel = fn ($record) => $record->transaction_type ?: $record->movement_type;
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : '-';
        $formatQty = fn ($value) => number_format((float) $value, 2);
        $badge = 'inline-flex max-w-36 truncate rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 bg-slate-100 text-slate-700 ring-slate-200';
        $cell = 'px-3 py-2';
        $headCell = 'px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500';
        $qtyIn = function ($record): float {
            $legacyQty = (float) ($record->quantity_in ?? 0);
            if ($legacyQty > 0) {
                return $legacyQty;
            }

            $type = strtoupper(str_replace('_', '-', (string) ($record->transaction_type ?: $record->movement_type)));

            return in_array($type, ['IN', 'RCV', 'PURCHASE-RECEIVE', 'ADJ-IN', 'TRF-IN', 'RETURN-IN', 'PRODUCTION-OUTPUT'], true)
                ? (float) ($record->base_qty ?: $record->qty)
                : 0;
        };
        $qtyOut = function ($record): float {
            $legacyQty = (float) ($record->quantity_out ?? 0);
            if ($legacyQty > 0) {
                return $legacyQty;
            }

            $type = strtoupper(str_replace('_', '-', (string) ($record->transaction_type ?: $record->movement_type)));

            return in_array($type, ['OUT', 'DO', 'SALE-DELIVERY', 'ADJ-OUT', 'TRF-OUT', 'RETURN-OUT', 'SERVICE', 'PRODUCTION-INPUT'], true)
                ? (float) ($record->base_qty ?: $record->qty)
                : 0;
        };
        $referenceUrl = function ($record): ?string {
            $type = strtoupper(str_replace('_', '-', (string) ($record->transaction_type ?: $record->movement_type)));
            $transactionId = $record->transaction_id;

            $routeName = match ($type) {
                'RCV', 'PURCHASE-RECEIVE' => 'receivings.show',
                default => null,
            };

            return $routeName && $transactionId && \Illuminate\Support\Facades\Route::has($routeName)
                ? route($routeName, $transactionId)
                : null;
        };
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        <x-ui.filter-toolbar
            :action="route('stock-movements.index')"
            columns="lg:grid-cols-[minmax(13rem,1.4fr)_9rem_9rem_minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_minmax(10rem,1fr)_7rem_6rem]"
        >
            <x-ui.search-input :value="$filters['keyword'] ?? ''" />
            <x-ui.date-range :from="$filters['date_from'] ?? ''" :to="$filters['date_to'] ?? ''" />
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies" all-label="All companies" />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches" all-label="All branches" />
            <x-ui.select-filter name="warehouse_id" label="Warehouse" :value="$filters['warehouse_id'] ?? ''" :options="$warehouses" all-label="All warehouses" />
            <x-ui.select-filter name="movement_type" label="Movement Type" :value="$filters['movement_type'] ?? ''" :options="$movementTypes" all-label="All movements" />
            <x-ui.select-filter name="item_category_id" label="Item Category" :value="$filters['item_category_id'] ?? ''" :options="$itemCategories" all-label="All categories" />
            <button class="h-10 rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white hover:bg-emerald-700">Apply</button>
            <a href="{{ route('stock-movements.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
        </x-ui.filter-toolbar>

        <x-ui.data-table class="rounded-lg shadow-none">
            <x-slot:head>
                <tr>
                    <th class="{{ $headCell }} whitespace-nowrap">Movement Date</th>
                    <th class="{{ $headCell }} whitespace-nowrap">SKU</th>
                    <th class="{{ $headCell }} min-w-56">Item</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Company</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Branch</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Warehouse</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Movement Type</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Qty In</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Qty Out</th>
                    <th class="{{ $headCell }} whitespace-nowrap">UoM</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Reference</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Action</th>
                </tr>
            </x-slot:head>

            @forelse($records as $record)
                @php
                    $inQty = $qtyIn($record);
                    $outQty = $qtyOut($record);
                    $reference = $record->transaction_number ?: $record->reference_number ?: '-';
                    $sourceUrl = $referenceUrl($record);
                @endphp
                <tr class="text-xs hover:bg-slate-50">
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($movementDate($record)) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap font-bold text-slate-900">{{ $record->item?->sku ?: '-' }}</td>
                    <td class="{{ $cell }} min-w-56 max-w-80 truncate text-slate-700">{{ $record->item?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap"><span class="{{ $badge }}">{{ $record->company?->name ?: $record->warehouse?->company?->name ?: '-' }}</span></td>
                    <td class="{{ $cell }} whitespace-nowrap"><span class="{{ $badge }}">{{ $record->branch?->name ?: $record->warehouse?->branch?->name ?: '-' }}</span></td>
                    <td class="{{ $cell }} whitespace-nowrap"><span class="{{ $badge }}">{{ $record->warehouse?->name ?: '-' }}</span></td>
                    <td class="{{ $cell }} whitespace-nowrap"><x-ui.movement-type-badge :type="$movementLabel($record)" /></td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $inQty > 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $formatQty($inQty) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $outQty > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ $formatQty($outQty) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $record->uom?->code ?: $record->item?->baseUnit?->code ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap">
                        @if($sourceUrl)
                            <a href="{{ $sourceUrl }}" class="font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">{{ $reference }}</a>
                        @else
                            <span class="text-slate-600">{{ $reference }}</span>
                        @endif
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap text-right">
                        <a href="{{ route('stock-movements.show', $record) }}" class="inline-flex h-7 items-center rounded-md border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a>
                    </td>
                </tr>
            @empty
                <x-ui.empty-state colspan="12" message="No stock movements found." />
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
