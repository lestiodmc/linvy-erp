<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">{{ $record->number }}</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Purchase Order detail</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('purchase-orders.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>

                @if($record->status === 'draft')
                    <a href="{{ route('purchase-orders.edit', $record) }}" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100">Edit</a>
                    <form method="POST" action="{{ route('purchase-orders.submit', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Submit</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-orders.cancel', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                    </form>
                @endif

                @if($record->status === 'submitted')
                    <form method="POST" action="{{ route('purchase-orders.approve', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-orders.reject', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-orders.cancel', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                    </form>
                @endif

                @if(in_array($record->status, ['approved', 'partially_received'], true))
                    <a href="{{ route('receivings.create-from-po', $record) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Create Receiving</a>
                @endif

                @if($record->status === 'approved')
                    <form method="POST" action="{{ route('purchase-orders.cancel', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                    </form>
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
                    <div><dt class="font-bold text-slate-500">Supplier</dt><dd class="font-semibold text-slate-900">{{ $record->supplier?->name }}</dd></div>
                    <div><dt class="font-bold text-slate-500">PR</dt><dd class="font-semibold text-slate-900">{{ $record->purchaseRequest?->number ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Order Date</dt><dd class="font-semibold text-slate-900">{{ $record->order_date?->format('Y-m-d') }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Expected Date</dt><dd class="font-semibold text-slate-900">{{ $record->expected_date?->format('Y-m-d') ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Notes</dt><dd class="text-slate-700">{{ $record->notes ?: '-' }}</dd></div>
                </dl>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-black text-slate-950">Item Lines</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Item</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Received</th><th class="px-4 py-3 text-right">Remaining</th><th class="px-4 py-3 text-right">Price</th><th class="px-4 py-3 text-right">Subtotal</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($record->lines as $line)
                                <tr>
                                    <td class="px-4 py-3"><div class="font-bold text-slate-900">{{ $line->item?->name }}</div><div class="text-xs text-slate-500">{{ $line->description }}</div></td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->quantity, 4) }} {{ $line->unit?->code }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->received_quantity, 4) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->remaining_quantity, 4) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($line->unit_price, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($line->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-5">
                    <div class="ml-auto grid max-w-sm gap-2 text-sm">
                        <div class="flex justify-between"><span class="font-bold text-slate-500">Subtotal</span><span class="font-black text-slate-900">{{ number_format($record->subtotal, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-bold text-slate-500">Tax Total</span><span class="font-black text-slate-900">{{ number_format($record->tax_total, 2) }}</span></div>
                        <div class="flex justify-between rounded-lg bg-emerald-50 px-3 py-2"><span class="font-bold text-emerald-700">Grand Total</span><span class="font-black text-emerald-900">{{ number_format($record->grand_total, 2) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
