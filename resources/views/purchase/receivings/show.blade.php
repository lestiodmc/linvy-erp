<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$record->number" subtitle="Receiving document and warehouse allocation.">
            <x-slot:action><x-ui.status-badge :status="$record->status" /></x-slot:action>
        </x-ui.page-header>
    </x-slot>

    @php
        $po = $record->purchaseOrder;
        $formatQty = fn ($value) => number_format((float) $value, 4);
        $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '-';
    @endphp

    <div class="enterprise-detail mx-auto max-w-screen-2xl space-y-3">
        @include('purchase.shared.flash')

        <x-source-document-summary
            type="Purchase Order"
            :number="$po?->number ?: '-'"
            :status="$po?->status"
            :subtitle="$record->supplier?->name ?: '-'"
            :metadata="[
                ['label' => 'Branch', 'value' => $record->branch?->name],
                ['label' => 'Order Date', 'value' => $formatDate($po?->order_date)],
                ['label' => 'Expected Date', 'value' => $formatDate($po?->expected_date)],
            ]"
            :action-url="$po ? route('purchase-orders.show', $po) : null"
            action-label="View PO"
        />

        <section class="theme-card rounded-lg p-4">
            <div class="mb-3 flex items-center justify-between"><h2 class="text-sm font-black">Receiving Information</h2><x-ui.status-badge :status="$record->status" /></div>
            <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt>Document Number</dt><dd class="font-mono">{{ $record->number }}</dd></div>
                <div><dt>Received Date</dt><dd>{{ $formatDate($record->received_date) }}</dd></div>
                <div><dt>Supplier Delivery No.</dt><dd>{{ $record->supplier_delivery_number ?: '-' }}</dd></div>
                <div><dt>Branch</dt><dd>{{ $record->branch?->name ?: '-' }}</dd></div>
                <div class="sm:col-span-2 lg:col-span-4"><dt>Notes</dt><dd>{{ $record->notes ?: '-' }}</dd></div>
            </dl>
        </section>

        <section class="enterprise-line-items theme-card overflow-hidden rounded-lg">
            <div class="flex items-center gap-2 border-b px-4 py-3" style="border-color:var(--theme-border)"><h2 class="text-sm font-black">Receiving Lines</h2><span class="theme-primary-soft rounded-full px-2 py-0.5 text-[10px] font-black">{{ $record->lines->count() }} items</span></div>
            <div class="max-w-full overflow-x-auto"><table class="receiving-lines-table min-w-[70rem] text-xs">
                <thead><tr>@foreach(['#', 'Item', 'Ordered', 'Previously Received', 'Received', 'Remaining', 'Warehouse', 'Batch', 'Expiry', 'UOM', 'Unit Cost'] as $heading)<th scope="col" class="{{ in_array($heading, ['Ordered', 'Previously Received', 'Received', 'Remaining', 'Unit Cost']) ? 'text-right' : 'text-left' }}">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody>@foreach($record->lines as $line)<tr>
                    <td class="text-center theme-muted">{{ $loop->iteration }}</td>
                    <td><div class="font-bold">{{ $line->item?->sku ?: '-' }}</div><div class="max-w-56 truncate text-[11px] theme-muted">{{ $line->item?->name ?: $line->description ?: '-' }}</div><div class="mt-1 flex gap-1">@if($line->item?->is_batch_tracked)<x-ui.status-badge status="batch_assignment" label="Batch" />@else<x-ui.status-badge status="neutral" label="No Batch" />@endif @if($line->item?->has_expiry_date)<x-ui.status-badge status="near_expiry" label="Expiry" />@endif</div></td>
                    <td class="text-right tabular-nums">{{ $formatQty($line->ordered_quantity) }}</td><td class="text-right tabular-nums theme-muted">{{ $formatQty($line->previously_received_quantity) }}</td><td class="text-right font-black tabular-nums text-emerald-700">{{ $formatQty($line->received_quantity) }}</td><td class="text-right font-bold tabular-nums">{{ $formatQty($line->remaining_quantity) }}</td>
                    <td><div class="font-bold">{{ $line->warehouse?->code ?: '-' }}</div><div class="max-w-44 truncate text-[11px] theme-muted" title="{{ $line->warehouse?->name }}">{{ $line->warehouse?->name ?: 'Not Required' }}</div></td>
                    <td>{{ $line->item?->is_batch_tracked ? ($line->batch_no ?: '-') : 'No Batch' }}</td><td>{{ $line->item?->has_expiry_date ? $formatDate($line->expiry_date) : 'Not Required' }}</td><td class="text-center">{{ $line->unit?->code ?: '-' }}</td><td class="text-right tabular-nums">{{ number_format((float) $line->unit_cost, 2) }}</td>
                </tr>@endforeach</tbody>
            </table></div>
            <div class="flex justify-end border-t px-4 py-2 text-xs theme-muted" style="border-color:var(--theme-border)">Total Lines <b class="ml-1 theme-text">{{ $record->lines->count() }}</b></div>
        </section>

        <div class="enterprise-action-bar sticky bottom-0 z-20 rounded-lg">
            <a href="{{ route('receivings.index') }}" class="enterprise-action theme-focus inline-flex h-9 items-center rounded-lg px-4 text-sm font-bold">Back</a>
            @if($record->status === \App\Models\Receiving::STATUS_DRAFT)
                <a href="{{ route('receivings.edit', $record) }}" class="enterprise-action theme-focus inline-flex h-9 items-center rounded-lg px-4 text-sm font-bold">Edit</a>
                <form method="POST" action="{{ route('receivings.post', $record) }}">@csrf<button class="theme-primary theme-focus h-9 rounded-lg px-4 text-sm font-bold">Post Receiving</button></form>
                <form method="POST" action="{{ route('receivings.cancel', $record) }}">@csrf<button class="theme-focus h-9 rounded-lg border border-red-200 px-4 text-sm font-bold text-red-700">Cancel</button></form>
            @endif
        </div>
    </div>
</x-app-layout>
