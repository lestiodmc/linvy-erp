<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$record->number"
            subtitle="Stock adjustment detail"
        >
            <x-slot:action>
                <div class="flex flex-wrap justify-end gap-2">
                    <a href="{{ route('stock-adjustments.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>
                    @if($record->status === \App\Models\StockAdjustment::STATUS_DRAFT)
                        <a href="{{ route('stock-adjustments.edit', $record) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                        <form method="POST" action="{{ route('stock-adjustments.post', $record) }}">
                            @csrf
                            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Post Adjustment</button>
                        </form>
                        <form method="POST" action="{{ route('stock-adjustments.cancel', $record) }}">
                            @csrf
                            <button class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                        </form>
                        <form method="POST" action="{{ route('stock-adjustments.destroy', $record) }}" onsubmit="return confirm('Delete this draft stock adjustment?')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-100">Delete</button>
                        </form>
                    @endif
                </div>
            </x-slot:action>
        </x-ui.page-header>
    </x-slot>

    @php
        $formatQty = fn ($value) => number_format((float) $value, 4);
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('Y-m-d') : '-';
        $reasonLabel = $reasonCodes[$record->reason_code] ?? str($record->reason_code ?: $record->reason ?: '-')->replace('_', ' ')->title()->toString();
        $headCell = 'px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500';
        $cell = 'px-3 py-2';
        $lineType = function ($qty): array {
            $qty = (float) $qty;

            return match (true) {
                $qty > 0 => ['Adj In', 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
                $qty < 0 => ['Adj Out', 'bg-red-50 text-red-700 ring-red-100'],
                default => ['No Change', 'bg-slate-100 text-slate-700 ring-slate-200'],
            };
        };
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Status</p>
                <div class="mt-1"><x-ui.status-badge :status="$record->status" /></div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Company</p>
                <p class="mt-1 font-bold text-slate-900">{{ $record->company?->name ?: '-' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Branch</p>
                <p class="mt-1 font-bold text-slate-900">{{ $record->branch?->name ?: '-' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Warehouse</p>
                <p class="mt-1 font-bold text-slate-900">{{ $record->warehouse?->name ?: '-' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Adjustment Date</p>
                <p class="mt-1 font-bold text-slate-900">{{ $formatDate($record->adjustment_date) }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Posted At</p>
                <p class="mt-1 font-bold text-slate-900">{{ $record->posted_at?->format('Y-m-d H:i') ?: '-' }}</p>
            </div>
        </div>

        <div class="mb-4 grid gap-3 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Reason Code</p>
                <span class="mt-1 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">{{ $reasonLabel }}</span>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Notes</p>
                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $record->notes ?: '-' }}</p>
            </div>
        </div>

        <x-ui.data-table title="Adjustment Lines" class="rounded-lg shadow-none">
            <x-slot:head>
                <tr>
                    <th class="{{ $headCell }} min-w-64">Item</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Warehouse</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Batch</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Expiry</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">System Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Physical Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Difference</th>
                    <th class="{{ $headCell }} whitespace-nowrap">UOM</th>
                    <th class="{{ $headCell }} min-w-52">Reason / Notes</th>
                </tr>
            </x-slot:head>

            @forelse($record->lines as $line)
                @php
                    $adjustmentQty = (float) $line->adjustment_qty;
                    [$typeLabel, $typeClass] = $lineType($adjustmentQty);
                @endphp
                <tr class="text-xs hover:bg-slate-50">
                    <td class="{{ $cell }} min-w-64">
                        <div class="font-bold text-slate-900">{{ $line->item?->sku ?: '-' }} - {{ $line->item?->name ?: '-' }}</div>
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $record->warehouse?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ filled($line->batch_no) ? $line->batch_no : 'No Batch' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($line->expiry_date) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold text-slate-700">{{ $formatQty($line->system_qty) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold text-slate-700">{{ $formatQty($line->counted_qty) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 {{ $typeClass }}">{{ $typeLabel }} {{ $formatQty(abs($adjustmentQty)) }}</span>
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $line->uom?->code ?: $line->unit?->code ?: '-' }}</td>
                    <td class="{{ $cell }} min-w-52 text-slate-600">{{ $line->remarks ?: $line->notes ?: '-' }}</td>
                </tr>
            @empty
                <x-ui.empty-state colspan="9" message="No adjustment lines found." />
            @endforelse
        </x-ui.data-table>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm">
                <span class="font-bold text-slate-500">Created By:</span>
                <span class="font-semibold text-slate-800">{{ $record->createdBy?->name ?: '-' }}</span>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm">
                <span class="font-bold text-slate-500">Posted By:</span>
                <span class="font-semibold text-slate-800">{{ $record->postedBy?->name ?: '-' }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
