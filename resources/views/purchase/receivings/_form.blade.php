<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record->exists ? 'Edit Receiving' : 'New Receiving' }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Receiving lines must come from approved PO lines.</p>
        </div>
    </x-slot>

    @php
        $selectedPo = $record->purchaseOrder ?: $purchaseOrders->firstWhere('id', $record->purchase_order_id);
        $lines = old('lines', $record->lines->toArray() ?: []);
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 border-b border-slate-100 p-6 md:grid-cols-4">
                <div>
                    <label class="text-sm font-bold text-slate-600">Purchase Order</label>
                    <select name="purchase_order_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select PO</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}" @selected((string)old('purchase_order_id', $record->purchase_order_id) === (string)$po->id)>{{ $po->number }} - {{ $po->supplier?->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Warehouse</label>
                    <select name="warehouse_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string)old('warehouse_id', $record->warehouse_id) === (string)$warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Received Date</label>
                    <input type="date" name="received_date" value="{{ old('received_date', optional($record->received_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Supplier Delivery No.</label>
                    <input name="supplier_delivery_number" value="{{ old('supplier_delivery_number', $record->supplier_delivery_number) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-4">
                    <label class="text-sm font-bold text-slate-600">Notes</label>
                    <input name="notes" value="{{ old('notes', $record->notes) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>

            <div class="p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-base font-black text-slate-950">PO Lines</h3>
                    @if($selectedPo)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $selectedPo->number }}</span>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-black uppercase text-slate-500">
                                <th class="px-3 py-3">Item</th><th class="px-3 py-3 text-right">Ordered</th><th class="px-3 py-3 text-right">Previous</th><th class="px-3 py-3 text-right">Receive</th><th class="px-3 py-3 text-right">Remaining After</th><th class="px-3 py-3 text-right">Unit Cost</th><th class="px-3 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($lines as $i => $line)
                                <tr>
                                    <td class="px-3 py-3">
                                        <input type="hidden" name="lines[{{ $i }}][purchase_order_line_id]" value="{{ $line['purchase_order_line_id'] }}">
                                        <div class="font-bold text-slate-900">{{ \App\Models\Item::find($line['item_id'] ?? null)?->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $line['description'] ?? '' }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-right">{{ number_format($line['ordered_quantity'] ?? 0, 4) }}</td>
                                    <td class="px-3 py-3 text-right">{{ number_format($line['previously_received_quantity'] ?? 0, 4) }}</td>
                                    <td class="px-3 py-3 text-right"><input type="number" step="0.0001" name="lines[{{ $i }}][received_quantity]" value="{{ $line['received_quantity'] ?? 0 }}" class="w-32 rounded-lg border-slate-200 text-right text-sm"></td>
                                    <td class="px-3 py-3 text-right">{{ number_format($line['remaining_quantity'] ?? 0, 4) }}</td>
                                    <td class="px-3 py-3 text-right"><input type="number" step="0.0001" name="lines[{{ $i }}][unit_cost]" value="{{ $line['unit_cost'] ?? 0 }}" class="w-32 rounded-lg border-slate-200 text-right text-sm"></td>
                                    <td class="px-3 py-3"><input name="lines[{{ $i }}][notes]" value="{{ $line['notes'] ?? '' }}" class="w-52 rounded-lg border-slate-200 text-sm"></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-12 text-center text-sm font-semibold text-slate-500">Open a PO and click Create Receiving to load receivable lines.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <a href="{{ route('receivings.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Save Draft</button>
            </div>
        </form>
    </div>
</x-app-layout>
