<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Batch Detail"
            subtitle="Batch-level stock inquiry for the selected item and warehouse."
        />
    </x-slot>

    @php
        $formatQty = fn ($value) => number_format((float) $value, 2);
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : '-';
        $uom = $record->uom?->code ?: $record->baseUom?->code ?: $record->item?->baseUnit?->code ?: '-';
        $tracksBatch = (bool) ($record->item?->is_batch_tracked ?? false) || (bool) ($record->item?->has_expiry_date ?? false);
        $rows = collect($batchBalances);

        if ($tracksBatch && $noBatchQty > 0.000001) {
            $rows = collect([(object) [
                'batch_no' => null,
                'expiry_date' => null,
                'qty_on_hand' => $noBatchQty,
                'qty_reserved' => 0,
                'qty_available' => $noBatchQty,
            ]])->merge($rows);
        }
    @endphp

    <div class="mx-auto max-w-screen-xl space-y-4">
        <div class="rounded-lg border border-slate-200 bg-white px-5 py-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-[11px] font-black uppercase text-slate-500">Item</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $record->item?->sku }} - {{ $record->item?->name }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase text-slate-500">Warehouse</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $record->warehouse?->name ?: '-' }}</p>
                    <p class="text-xs text-slate-500">{{ $record->branch?->name ?: $record->warehouse?->branch?->name ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase text-slate-500">Total On Hand</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $formatQty($onHand) }} {{ $uom }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase text-slate-500">Total Available</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $formatQty($available) }} {{ $uom }}</p>
                </div>
            </div>
        </div>

        @if(! $tracksBatch)
            <div class="rounded-lg border border-slate-200 bg-white px-5 py-10 text-center">
                <p class="text-sm font-bold text-slate-700">No batch tracking for this item.</p>
            </div>
        @else
            <x-ui.data-table class="rounded-lg shadow-none">
                <x-slot:head>
                    <tr>
                        <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Batch No</th>
                        <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Expiry Date</th>
                        <th class="px-3 py-2 text-right text-[11px] font-black uppercase tracking-wide text-slate-500">Qty On Hand</th>
                        <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">UOM</th>
                        <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Status</th>
                    </tr>
                </x-slot:head>

                @forelse($rows as $batch)
                    @php
                        $status = $statusForBatch($batch->batch_no, $batch->expiry_date);
                        $statusLabel = $status[0];
                        $statusClass = $status[1];
                    @endphp
                    <tr class="text-xs hover:bg-slate-50">
                        <td class="whitespace-nowrap px-3 py-2 font-semibold text-slate-800">{{ filled($batch->batch_no) ? $batch->batch_no : 'No Batch' }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $formatDate($batch->expiry_date) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-right font-semibold text-slate-900">{{ $formatQty($batch->qty_on_hand) }}</td>
                        <td class="whitespace-nowrap px-3 py-2 text-slate-600">{{ $uom }}</td>
                        <td class="whitespace-nowrap px-3 py-2">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-state colspan="5" message="No batch balance found." />
                @endforelse

                <x-slot:foot>
                    <tr class="bg-slate-50 text-xs font-bold text-slate-900">
                        <td class="px-3 py-2" colspan="2">Total</td>
                        <td class="px-3 py-2 text-right">{{ $formatQty($rows->sum(fn ($row) => (float) $row->qty_on_hand)) }}</td>
                        <td class="px-3 py-2">{{ $uom }}</td>
                        <td class="px-3 py-2"></td>
                    </tr>
                </x-slot:foot>
            </x-ui.data-table>
        @endif

        <a href="{{ route('stock-balances.index') }}" class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Back to Stock Balance</a>
    </div>
</x-app-layout>
