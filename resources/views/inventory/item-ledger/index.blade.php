<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Item Ledger"
            subtitle="Stock card inquiry calculated from stock movements."
        />
    </x-slot>

    @php
        $formatQty = fn ($value) => number_format((float) $value, 2);
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : '-';
        $cell = 'px-3 py-2';
        $headCell = 'px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500';
        $summaryCards = [
            ['label' => 'Opening Balance', 'value' => $openingBalance, 'class' => 'text-slate-900'],
            ['label' => 'Total In', 'value' => $ledger['total_in'], 'class' => 'text-emerald-700'],
            ['label' => 'Total Out', 'value' => $ledger['total_out'], 'class' => 'text-red-700'],
            ['label' => 'Closing Balance', 'value' => $ledger['closing_balance'], 'class' => $ledger['closing_balance'] < 0 ? 'text-red-700' : 'text-slate-900'],
        ];
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @if(session('status'))
            <div class="mb-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <x-filter.panel :action="route('item-ledger.index')">
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies" all-label="All companies" />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches" all-label="All branches" />
            <x-ui.warehouse-filter :warehouses="$warehouses" :value="$filters['warehouse_id'] ?? ''" />
            <x-filter.field :span="2"><x-ui.select-filter name="item_id" label="Item" :value="$filters['item_id'] ?? ''" :options="$items" all-label="Select item" /></x-filter.field>
            <div>
                <label class="sr-only" for="sku">SKU</label>
                <input id="sku" name="sku" value="{{ $filters['sku'] ?? '' }}" placeholder="SKU" class="h-10 w-full rounded-lg border-slate-200 px-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <x-ui.select-filter name="batch_no" label="Batch" :value="$filters['batch_no'] ?? '__all'" :options="$batches" all-label="All Batch" />
            <x-ui.select-filter name="movement_type" label="Movement Type" :value="$filters['movement_type'] ?? ''" :options="$movementTypes" all-label="All movements" />
            <div>
                <label class="sr-only" for="reference">Document number</label>
                <input id="reference" name="reference" value="{{ $filters['reference'] ?? '' }}" placeholder="Document no." class="h-10 w-full rounded-lg border-slate-200 px-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <x-ui.date-range :from="$filters['date_from'] ?? ''" :to="$filters['date_to'] ?? ''" />
            <x-slot:actions><button class="button-primary">Search</button><x-filter.reset :href="route('item-ledger.index')" /><a href="{{ route('item-ledger.export-excel', request()->query()) }}" class="filter-button-secondary">Excel</a><a href="{{ route('item-ledger.export-pdf', request()->query()) }}" class="filter-button-secondary">PDF</a></x-slot:actions>
        </x-filter.panel>

        @if(($filters['batch_no'] ?? '__all') === '__all')
            <p class="mb-2 text-sm font-medium text-slate-500">All Batch includes batch and No Batch stock.</p>
        @elseif(($filters['batch_no'] ?? '__all') === '__no_batch')
            <p class="mb-2 text-sm font-medium text-slate-500">No Batch shows only legacy or unbatched stock movements.</p>
        @else
            <p class="mb-2 text-sm font-medium text-slate-500">Batch summary is limited to {{ $filters['batch_no'] }}.</p>
        @endif

        @if($ledgerUom)
        <div class="mb-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($summaryCards as $card)
                <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-1 text-xl font-black {{ $card['class'] }}">{{ $formatQty($card['value']) }} {{ $ledgerUom }}</p>
                </div>
            @endforeach
        </div>
        @elseif(filled($filters['sku'] ?? null))
            <div class="theme-card mb-2 rounded-lg px-4 py-3 text-sm theme-muted">Quantity KPIs are available after selecting one item, preventing totals across incompatible UOMs.</div>
        @endif

        <x-ui.data-table class="rounded-lg shadow-none">
            <x-slot:head>
                <tr>
                    <th class="{{ $headCell }} whitespace-nowrap">Date</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Document No</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Transaction Type</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Reference Type</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Warehouse</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Item SKU</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Item Name</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Batch</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Expiry Date</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">In Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Out Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Running Balance</th>
                    <th class="{{ $headCell }} whitespace-nowrap">UoM</th>
                    <th class="{{ $headCell }} min-w-48">Notes</th>
                </tr>
            </x-slot:head>

            @if($movements->currentPage() === 1)
                @php($openingRows = collect($ledger['opening_rows'] ?? []))
                @php($runningOpening = 0.0)
                @forelse($openingRows as $openingRow)
                    @php($runningOpening += (float) $openingRow['balance'])
                    <tr class="bg-slate-50 text-xs">
                        <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($filters['date_from'] ?? null) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap font-semibold text-slate-700">Opening</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-600">Opening</td>
                        <td class="{{ $cell }} whitespace-nowrap">
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">Opening</span>
                        </td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap font-semibold text-slate-700">{{ $openingRow['batch_no'] }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">{{ $formatDate($openingRow['expiry_date']) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right font-semibold text-slate-900">{{ $formatQty($runningOpening) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} text-slate-500">Opening balance before selected period</td>
                    </tr>
                @empty
                    <tr class="bg-slate-50 text-xs">
                        <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($filters['date_from'] ?? null) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap font-semibold text-slate-700">Opening</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-600">Opening</td>
                        <td class="{{ $cell }} whitespace-nowrap">
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200">Opening</span>
                        </td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">{{ ($filters['batch_no'] ?? '__all') === '__no_batch' ? 'No Batch' : (($filters['batch_no'] ?? '__all') === '__all' ? 'All Batch' : $filters['batch_no']) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right font-semibold text-slate-900">{{ $formatQty($openingBalance) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} text-slate-500">Opening balance before selected period</td>
                    </tr>
                @endforelse
            @endif

            @forelse($ledger['rows'] as $row)
                @php($movement = $row['movement'])
                <tr class="text-xs">
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($row['date']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap">
                        <a href="{{ $row['reference_url'] }}" class="theme-link font-semibold hover:underline">{{ $row['reference_no'] }}</a>
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap">
                        <x-ui.status-badge :status="$row['movement_direction']" :label="$row['movement_label']" />
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $movement->reference_type ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $movement->warehouse?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap font-semibold text-slate-700">{{ $movement->item?->sku ?: '-' }}</td>
                    <td class="{{ $cell }} max-w-56 truncate text-slate-700" title="{{ $movement->item?->name }}">{{ $movement->item?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $row['batch_no'] }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($row['expiry_date']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $row['in_qty'] > 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $formatQty($row['in_qty']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $row['out_qty'] > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ $formatQty($row['out_qty']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $row['running_balance'] < 0 ? 'text-red-700' : 'text-slate-900' }}">{{ $formatQty($row['running_balance']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $row['uom'] }}</td>
                    <td class="{{ $cell }} max-w-64 truncate text-slate-600" title="{{ $movement->remarks ?: $movement->notes }}">{{ $movement->remarks ?: $movement->notes ?: '-' }}</td>
                </tr>
            @empty
                <x-ui.empty-state colspan="14" message="No inventory movements found." description="No ledger activity matches the selected filters." />
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :records="$movements" />
    </div>
</x-app-layout>
