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
        $movementBadge = function (string $category): string {
            return match ($category) {
                'receive' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'issue' => 'bg-red-50 text-red-700 ring-red-100',
                'transfer' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'adjustment' => 'bg-orange-50 text-orange-700 ring-orange-100',
                default => 'bg-slate-100 text-slate-700 ring-slate-200',
            };
        };
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

        <x-ui.filter-toolbar
            :action="route('item-ledger.index')"
            columns="lg:grid-cols-[minmax(9rem,1fr)_minmax(9rem,1fr)_minmax(10rem,1fr)_minmax(15rem,1.5fr)_8rem_minmax(9rem,1fr)_minmax(10rem,1fr)_9rem_9rem_6rem_6rem_8rem_7rem]"
        >
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies" all-label="All companies" />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches" all-label="All branches" />
            <x-ui.warehouse-filter :warehouses="$warehouses" :value="$filters['warehouse_id'] ?? ''" />
            <x-ui.select-filter name="item_id" label="Item" :value="$filters['item_id'] ?? ''" :options="$items" all-label="Select item" />
            <div>
                <label class="sr-only" for="sku">SKU</label>
                <input id="sku" name="sku" value="{{ $filters['sku'] ?? '' }}" placeholder="SKU" class="h-10 w-full rounded-lg border-slate-200 px-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <x-ui.select-filter name="batch_no" label="Batch" :value="$filters['batch_no'] ?? '__all'" :options="$batches" all-label="All Batch" />
            <x-ui.select-filter name="movement_type" label="Movement Type" :value="$filters['movement_type'] ?? ''" :options="$movementTypes" all-label="All movements" />
            <x-ui.date-range :from="$filters['date_from'] ?? ''" :to="$filters['date_to'] ?? ''" />
            <button class="h-10 rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white hover:bg-emerald-700">Search</button>
            <a href="{{ route('item-ledger.index') }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
            <a href="{{ route('item-ledger.export-excel', request()->query()) }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Excel</a>
            <a href="{{ route('item-ledger.export-pdf', request()->query()) }}" class="flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">PDF</a>
        </x-ui.filter-toolbar>

        @if(($filters['batch_no'] ?? '__all') === '__all')
            <p class="mb-2 text-sm font-medium text-slate-500">All Batch includes batch and No Batch stock.</p>
        @elseif(($filters['batch_no'] ?? '__all') === '__no_batch')
            <p class="mb-2 text-sm font-medium text-slate-500">No Batch shows only legacy or unbatched stock movements.</p>
        @else
            <p class="mb-2 text-sm font-medium text-slate-500">Batch summary is limited to {{ $filters['batch_no'] }}.</p>
        @endif

        <div class="mb-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($summaryCards as $card)
                <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-1 text-xl font-black {{ $card['class'] }}">{{ $formatQty($card['value']) }}</p>
                </div>
            @endforeach
        </div>

        <x-ui.data-table class="rounded-lg shadow-none">
            <x-slot:head>
                <tr>
                    <th class="{{ $headCell }} whitespace-nowrap">Date</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Reference No</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Document Type</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Movement Type</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Warehouse</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Batch</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Expiry Date</th>
                    <th class="{{ $headCell }} min-w-60">Description</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">In Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Out Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Running Balance</th>
                    <th class="{{ $headCell }} whitespace-nowrap">UoM</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Created By</th>
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
                        <td class="{{ $cell }} whitespace-nowrap font-semibold text-slate-700">{{ $openingRow['batch_no'] }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">{{ $formatDate($openingRow['expiry_date']) }}</td>
                        <td class="{{ $cell }} min-w-60 text-slate-600">Opening - {{ $openingRow['batch_no'] }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right font-semibold text-slate-900">{{ $formatQty($runningOpening) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">System</td>
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
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">{{ ($filters['batch_no'] ?? '__all') === '__no_batch' ? 'No Batch' : (($filters['batch_no'] ?? '__all') === '__all' ? 'All Batch' : $filters['batch_no']) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} min-w-60 text-slate-600">Opening balance before selected period</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right text-slate-400">{{ $formatQty(0) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-right font-semibold text-slate-900">{{ $formatQty($openingBalance) }}</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">-</td>
                        <td class="{{ $cell }} whitespace-nowrap text-slate-500">System</td>
                    </tr>
                @endforelse
            @endif

            @forelse($ledger['rows'] as $row)
                @php($movement = $row['movement'])
                <tr class="text-xs hover:bg-slate-50">
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($row['date']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap">
                        <a href="{{ $row['reference_url'] }}" class="font-semibold text-emerald-700 hover:text-emerald-800 hover:underline">{{ $row['reference_no'] }}</a>
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $row['document_type'] }}</td>
                    <td class="{{ $cell }} whitespace-nowrap">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $movementBadge($row['movement_category']) }}">{{ $row['movement_label'] }}</span>
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $movement->warehouse?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $row['batch_no'] }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($row['expiry_date']) }}</td>
                    <td class="{{ $cell }} min-w-60 max-w-96 truncate text-slate-700">{{ $row['description'] }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $row['in_qty'] > 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $formatQty($row['in_qty']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $row['out_qty'] > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ $formatQty($row['out_qty']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $row['running_balance'] < 0 ? 'text-red-700' : 'text-slate-900' }}">{{ $formatQty($row['running_balance']) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $row['uom'] }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $movement->createdBy?->name ?: '-' }}</td>
                </tr>
            @empty
                <x-ui.empty-state colspan="13" message="No inventory movement found." />
            @endforelse
        </x-ui.data-table>

        <x-ui.pagination :records="$movements" />
    </div>
</x-app-layout>
