<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">{{ $record->item?->sku }} - {{ $record->item?->name }}</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Stock balance detail by warehouse and batch.</p>
            </div>
            <a href="{{ route('stock-balances.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>
        </div>
    </x-slot>

    @php
        $formatQty = fn ($value) => number_format((float) $value, 2);
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : '-';
        $cell = 'px-4 py-3';
        $headCell = 'px-4 py-3 text-left text-xs font-black uppercase text-slate-500';
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <h3 class="text-base font-black text-slate-950">Item</h3>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="font-bold text-slate-500">SKU</dt><dd class="font-semibold text-slate-900">{{ $record->item?->sku ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Item</dt><dd class="font-semibold text-slate-900">{{ $record->item?->name ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Warehouse</dt><dd class="font-semibold text-slate-900">{{ $record->warehouse?->name ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Branch</dt><dd class="font-semibold text-slate-900">{{ $record->branch?->name ?: $record->warehouse?->branch?->name ?: '-' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase text-slate-500">Total On Hand</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $formatQty($onHand) }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $record->baseUom?->code ?: $record->uom?->code ?: $record->item?->baseUnit?->code }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase text-slate-500">Total Available</p>
                <p class="mt-2 text-2xl font-black {{ $available < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $formatQty($available) }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-500">Reserved {{ $formatQty($reserved) }}</p>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-black text-slate-950">Batch Detail</h3>
            </div>
            @if($record->item?->is_batch_tracked)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="{{ $headCell }}">Batch No</th>
                                <th class="{{ $headCell }}">Expiry Date</th>
                                <th class="{{ $headCell }} text-right">On Hand</th>
                                <th class="{{ $headCell }} text-right">Reserved</th>
                                <th class="{{ $headCell }} text-right">Available</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @if($noBatchQty > 0.000001)
                                <tr>
                                    <td class="{{ $cell }} font-semibold text-slate-900">No Batch</td>
                                    <td class="{{ $cell }} text-slate-600">-</td>
                                    <td class="{{ $cell }} text-right font-semibold text-slate-900">{{ $formatQty($noBatchQty) }}</td>
                                    <td class="{{ $cell }} text-right text-slate-600">{{ $formatQty(0) }}</td>
                                    <td class="{{ $cell }} text-right font-semibold text-emerald-700">{{ $formatQty($noBatchQty) }}</td>
                                </tr>
                            @endif
                            @forelse($batchBalances as $batch)
                                <tr>
                                    <td class="{{ $cell }} font-semibold text-slate-900">{{ $batch->batch_no }}</td>
                                    <td class="{{ $cell }} text-slate-600">{{ $formatDate($batch->expiry_date) }}</td>
                                    <td class="{{ $cell }} text-right font-semibold text-slate-900">{{ $formatQty($batch->qty_on_hand) }}</td>
                                    <td class="{{ $cell }} text-right text-slate-600">{{ $formatQty($batch->qty_reserved) }}</td>
                                    <td class="{{ $cell }} text-right font-semibold {{ (float) $batch->qty_available < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $formatQty($batch->qty_available) }}</td>
                                </tr>
                            @empty
                                @if($noBatchQty <= 0.000001)
                                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm font-semibold text-slate-500">No batch balance found.</td></tr>
                                @endif
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-sm font-semibold text-slate-600">This item is not batch tracked. Current stock is maintained as total item balance only.</div>
            @endif
        </div>
    </div>
</x-app-layout>
