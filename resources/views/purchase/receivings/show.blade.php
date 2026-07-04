<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">{{ $record->number }}</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Receiving detail</p>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <a href="{{ route('receivings.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>
                @if($record->status === 'draft')
                    <a href="{{ route('receivings.edit', $record) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                    <form method="POST" action="{{ route('receivings.post', $record) }}">@csrf<button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Post Receiving</button></form>
                    <form method="POST" action="{{ route('receivings.cancel', $record) }}">@csrf<button class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button></form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-black text-slate-950">Header</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-500">Status</dt><dd><x-status-badge :status="$record->status" /></dd></div>
                    <div><dt class="font-bold text-slate-500">PO</dt><dd class="font-semibold text-slate-900">{{ $record->purchaseOrder?->number }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Supplier</dt><dd class="font-semibold text-slate-900">{{ $record->supplier?->name }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Received Date</dt><dd class="font-semibold text-slate-900">{{ $record->received_date?->format('Y-m-d') }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Supplier Delivery No.</dt><dd class="text-slate-700">{{ $record->supplier_delivery_number ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Notes</dt><dd class="text-slate-700">{{ $record->notes ?: '-' }}</dd></div>
                </dl>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-black text-slate-950">Receiving Lines</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Item</th><th class="px-4 py-3 text-left">Warehouse</th><th class="px-4 py-3 text-right">Ordered</th><th class="px-4 py-3 text-right">Previous</th><th class="px-4 py-3 text-right">Received</th><th class="px-4 py-3 text-right">Remaining</th><th class="px-4 py-3 text-right">Unit Cost</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($record->lines as $line)
                                <tr>
                                    <td class="px-4 py-3"><div class="font-bold text-slate-900">{{ $line->item?->name }}</div><div class="text-xs text-slate-500">{{ $line->description }}</div></td>
                                    <td class="px-4 py-3"><div class="font-semibold text-slate-900">{{ $line->warehouse?->name }}</div><div class="text-xs text-slate-500">{{ $line->warehouse?->branch?->name }}</div></td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->ordered_quantity, 4) }} {{ $line->unit?->code }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->previously_received_quantity, 4) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ number_format($line->received_quantity, 4) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->remaining_quantity, 4) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->unit_cost, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
