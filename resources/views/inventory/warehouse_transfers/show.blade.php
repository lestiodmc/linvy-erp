<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$record->number ?: 'Draft Warehouse Transfer'"
            subtitle="Warehouse transfer document"
        >
            <x-slot:action>
                <div class="flex flex-wrap justify-end gap-2">
                    <x-ui.status-badge :status="$record->status" />
                    <a href="{{ route('warehouse-transfers.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>
                    @if($record->status === \App\Models\WarehouseTransfer::STATUS_DRAFT)
                        <a href="{{ route('warehouse-transfers.edit', $record) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                        <form method="POST" action="{{ route('warehouse-transfers.post', $record) }}">
                            @csrf
                            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Post Transfer</button>
                        </form>
                        <form method="POST" action="{{ route('warehouse-transfers.cancel', $record) }}">
                            @csrf
                            <button class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                        </form>
                    @endif
                </div>
            </x-slot:action>
        </x-ui.page-header>
    </x-slot>

    @php
        $formatQty = fn ($value) => number_format((float) $value, 2);
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : '-';
        $formatDateTime = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y H:i') : '-';
        $headCell = 'px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500';
        $cell = 'px-3 py-2';
        $totalQty = $record->lines->sum(fn ($line) => (float) $line->quantity);
        $transferOutQty = $movements->where('transaction_type', \App\Models\Inventory\StockMovement::TRANSACTION_TRF_OUT)->sum(fn ($movement) => (float) $movement->qty);
        $transferInQty = $movements->where('transaction_type', \App\Models\Inventory\StockMovement::TRANSACTION_TRF_IN)->sum(fn ($movement) => (float) $movement->qty);
    @endphp

    <div class="mx-auto max-w-screen-2xl space-y-4">
        @include('purchase.shared.flash')

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Transfer Number</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $record->number ?: 'Draft' }}</h2>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm font-bold text-slate-700">
                        <span>{{ $record->fromWarehouse?->name ?: '-' }}</span>
                        <span class="text-xl text-slate-400">→</span>
                        <span>{{ $record->toWarehouse?->name ?: '-' }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Transfer Date</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $formatDate($record->transfer_date) }}</p>
                </div>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Company</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $record->company?->name ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Branch</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $record->branch?->name ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Source Warehouse</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $record->fromWarehouse?->name ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Destination Warehouse</p>
                    <p class="mt-1 font-bold text-slate-900">{{ $record->toWarehouse?->name ?: '-' }}</p>
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Notes</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $record->notes ?: '-' }}</p>
                </div>
            </div>
        </section>

        <x-ui.data-table title="Detail Lines" class="rounded-2xl shadow-sm">
            <x-slot:head>
                <tr>
                    <th class="{{ $headCell }} whitespace-nowrap">Line</th>
                    <th class="{{ $headCell }} whitespace-nowrap">SKU</th>
                    <th class="{{ $headCell }} min-w-64">Item</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Batch</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Expiry</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap">UOM</th>
                    <th class="{{ $headCell }} min-w-52">Notes</th>
                </tr>
            </x-slot:head>

            @forelse($record->lines as $line)
                <tr class="text-xs hover:bg-slate-50">
                    <td class="{{ $cell }} whitespace-nowrap font-semibold text-slate-500">{{ $loop->iteration }}</td>
                    <td class="{{ $cell }} whitespace-nowrap font-bold text-slate-900">{{ $line->item?->sku ?: '-' }}</td>
                    <td class="{{ $cell }} min-w-64 text-slate-700">{{ $line->item?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ filled($line->batch_no) ? $line->batch_no : 'No Batch' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($line->expiry_date) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold text-slate-900">{{ $formatQty($line->quantity) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $line->unit?->code ?: '-' }}</td>
                    <td class="{{ $cell }} min-w-52 text-slate-600">{{ $line->notes ?: '-' }}</td>
                </tr>
            @empty
                <x-ui.empty-state colspan="8" message="No transfer lines found." />
            @endforelse

            <x-slot:foot>
                <tr class="bg-slate-50 text-xs font-black text-slate-900">
                    <td class="px-3 py-3 text-right" colspan="5">Total</td>
                    <td class="px-3 py-3 text-right">{{ $formatQty($totalQty) }}</td>
                    <td class="px-3 py-3" colspan="2">{{ $record->lines->count() }} lines</td>
                </tr>
            </x-slot:foot>
        </x-ui.data-table>

        <section class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-black text-slate-950">Warehouse Movement Summary</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-lg bg-amber-50 px-3 py-2">
                        <span class="font-bold text-amber-800">Transfer Out</span>
                        <span class="font-black text-amber-900">{{ $formatQty($transferOutQty) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-blue-50 px-3 py-2">
                        <span class="font-bold text-blue-800">Transfer In</span>
                        <span class="font-black text-blue-900">{{ $formatQty($transferInQty) }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="font-bold text-slate-600">Movement Records</span>
                        <span class="font-black text-slate-900">{{ $movements->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h3 class="text-base font-black text-slate-950">Posting Information</h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @if($record->created_at)
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Created At</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $formatDateTime($record->created_at) }}</p>
                        </div>
                    @endif
                    @if($record->updated_at)
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Updated At</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $formatDateTime($record->updated_at) }}</p>
                        </div>
                    @endif
                    @if($record->postedBy)
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Posted By</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $record->postedBy->name }}</p>
                        </div>
                    @endif
                    @if($record->posted_at)
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Posted At</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $formatDateTime($record->posted_at) }}</p>
                        </div>
                    @endif
                    @if($record->cancelledBy)
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Cancelled By</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $record->cancelledBy->name }}</p>
                        </div>
                    @endif
                    @if($record->cancelled_at)
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Cancelled At</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $formatDateTime($record->cancelled_at) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <x-ui.data-table title="Movement Verification" class="rounded-2xl shadow-sm">
            <x-slot:head>
                <tr>
                    <th class="{{ $headCell }} whitespace-nowrap">Movement</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Warehouse</th>
                    <th class="{{ $headCell }} whitespace-nowrap">SKU</th>
                    <th class="{{ $headCell }} min-w-56">Item</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Batch</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Expiry</th>
                    <th class="{{ $headCell }} whitespace-nowrap text-right">Qty</th>
                    <th class="{{ $headCell }} whitespace-nowrap">UOM</th>
                    <th class="{{ $headCell }} min-w-52">Remarks</th>
                    <th class="{{ $headCell }} whitespace-nowrap">Created By</th>
                </tr>
            </x-slot:head>

            @forelse($movements as $movement)
                <tr class="text-xs hover:bg-slate-50">
                    <td class="{{ $cell }} whitespace-nowrap">
                        <x-ui.movement-type-badge :type="$movement->transaction_type" />
                    </td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $movement->warehouse?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap font-bold text-slate-900">{{ $movement->item?->sku ?: '-' }}</td>
                    <td class="{{ $cell }} min-w-56 text-slate-700">{{ $movement->item?->name ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ filled($movement->batch_no) ? $movement->batch_no : 'No Batch' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $formatDate($movement->expiry_date) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-right font-semibold {{ $movement->movement_type === \App\Models\Inventory\StockMovement::MOVEMENT_OUT ? 'text-red-700' : 'text-emerald-700' }}">{{ $formatQty($movement->qty) }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $movement->uom?->code ?: '-' }}</td>
                    <td class="{{ $cell }} min-w-52 text-slate-600">{{ $movement->remarks ?: '-' }}</td>
                    <td class="{{ $cell }} whitespace-nowrap text-slate-600">{{ $movement->createdBy?->name ?: '-' }}</td>
                </tr>
            @empty
                <x-ui.empty-state colspan="10" message="No transfer movements have been created yet." />
            @endforelse
        </x-ui.data-table>
    </div>
</x-app-layout>
