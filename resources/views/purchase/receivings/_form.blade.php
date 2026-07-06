<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record->exists ? 'Edit Receiving' : 'New Receiving' }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Receiving lines must come from approved PO lines.</p>
        </div>
    </x-slot>

    @php
        $selectedPoText = $selectedPo['text'] ?? $record->purchaseOrder?->number;
        $lines = old('lines', $record->lines->toArray() ?: []);
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 border-b border-slate-100 p-6 md:grid-cols-3">
                <div>
                    <label class="text-sm font-bold text-slate-600">Purchase Order</label>
                    <div class="mt-1">
                        <x-searchable-select
                            name="purchase_order_id"
                            :url="route('purchase.lookup.purchase-orders')"
                            placeholder="Search PO by number or supplier..."
                            :selected-id="$selectedPo['id'] ?? $record->purchase_order_id"
                            :selected-text="$selectedPoText ?? ''"
                            :on-select="'window.location.href = \''.route('receivings.create-from-po', ['purchaseOrder' => '__PO_ID__']).'\'.replace(\'__PO_ID__\', option.id)'"
                        />
                    </div>
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Branch</label>
                    <select
                        name="branch_id"
                        data-receiving-branch
                        class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string)$selectedBranchId === (string)$branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @if($branches->count() === 0)
                        <p class="mt-1 text-xs font-semibold text-red-600">No branch access assigned for this user.</p>
                    @endif
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Received Date</label>
                    <input type="date" name="received_date" value="{{ old('received_date', optional($record->received_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Supplier Delivery No.</label>
                    <input name="supplier_delivery_number" value="{{ old('supplier_delivery_number', $record->supplier_delivery_number) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-3">
                    <label class="text-sm font-bold text-slate-600">Notes</label>
                    <input name="notes" value="{{ old('notes', $record->notes) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>

            <div class="p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-base font-black text-slate-950">PO Lines</h3>
                    @if($selectedPoText)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $selectedPoText }}</span>
                    @endif
                </div>
                <p data-warehouse-branch-message class="mb-3 hidden rounded-lg bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
                    Select branch first to load warehouses.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-black uppercase text-slate-500">
                                <th class="px-3 py-3">Item</th><th class="px-3 py-3 text-right">Ordered</th><th class="px-3 py-3 text-right">Previous</th><th class="px-3 py-3 text-right">Receive</th><th class="px-3 py-3 text-right">Remaining After</th><th class="px-3 py-3">Warehouse</th><th class="px-3 py-3 text-right">Unit Cost</th><th class="px-3 py-3">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($lines as $i => $line)
                                @php($warehouseTypeId = $lineItemWarehouseTypes[$line['item_id'] ?? null] ?? null)
                                <tr>
                                    <td class="px-3 py-3">
                                        <input type="hidden" name="lines[{{ $i }}][purchase_order_line_id]" value="{{ $line['purchase_order_line_id'] }}">
                                        <input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $line['item_id'] ?? '' }}">
                                        <div class="font-bold text-slate-900">{{ $selectedItems[$line['item_id'] ?? null] ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $line['description'] ?? '' }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-right">{{ number_format($line['ordered_quantity'] ?? 0, 4) }}</td>
                                    <td class="px-3 py-3 text-right">{{ number_format($line['previously_received_quantity'] ?? 0, 4) }}</td>
                                    <td class="px-3 py-3 text-right"><input type="number" step="0.0001" name="lines[{{ $i }}][received_quantity]" value="{{ $line['received_quantity'] ?? 0 }}" class="w-32 rounded-lg border-slate-200 text-right text-sm"></td>
                                    <td class="px-3 py-3 text-right">{{ number_format($line['remaining_quantity'] ?? 0, 4) }}</td>
                                    <td class="px-3 py-3">
                                        <select
                                            name="lines[{{ $i }}][warehouse_id]"
                                            data-receiving-warehouse
                                            data-default-warehouse-type-id="{{ $warehouseTypeId }}"
                                            class="w-72 rounded-lg border-slate-200 text-sm"
                                        >
                                            <option value="">Select warehouse</option>
                                            @foreach($warehouses as $warehouse)
                                                <option
                                                    value="{{ $warehouse->id }}"
                                                    data-branch-id="{{ $warehouse->branch_id }}"
                                                    data-warehouse-type-id="{{ $warehouse->warehouse_type_id }}"
                                                    @selected((string)($line['warehouse_id'] ?? '') === (string)$warehouse->id)
                                                >
                                                    {{ $warehouse->branch?->name ? $warehouse->branch->name.' - ' : '' }}{{ $warehouse->code ? $warehouse->code.' - ' : '' }}{{ $warehouse->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-3 text-right"><input type="number" step="0.0001" name="lines[{{ $i }}][unit_cost]" value="{{ $line['unit_cost'] ?? 0 }}" class="w-32 rounded-lg border-slate-200 text-right text-sm"></td>
                                    <td class="px-3 py-3"><input name="lines[{{ $i }}][notes]" value="{{ $line['notes'] ?? '' }}" class="w-52 rounded-lg border-slate-200 text-sm"></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-5 py-12 text-center text-sm font-semibold text-slate-500">Open a PO and click Create Receiving to load receivable lines.</td></tr>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const branchSelect = document.querySelector('[data-receiving-branch]');
            const message = document.querySelector('[data-warehouse-branch-message]');
            const warehouseSelects = Array.from(document.querySelectorAll('[data-receiving-warehouse]'));

            const refreshWarehouses = (resetInvalid = false) => {
                const branchId = branchSelect?.value || '';

                message?.classList.toggle('hidden', Boolean(branchId));

                warehouseSelects.forEach((select) => {
                    const currentOption = select.selectedOptions[0];
                    const currentBranchId = currentOption?.dataset.branchId || '';
                    const defaultWarehouseTypeId = select.dataset.defaultWarehouseTypeId || '';

                    select.disabled = ! branchId;

                    Array.from(select.options).forEach((option) => {
                        if (! option.value) {
                            option.hidden = false;
                            option.disabled = false;
                            return;
                        }

                        const isBranchOption = option.dataset.branchId === branchId;
                        option.hidden = ! isBranchOption;
                        option.disabled = ! isBranchOption;
                    });

                    if (! branchId) {
                        select.value = '';
                        return;
                    }

                    if (resetInvalid && currentBranchId !== branchId) {
                        const defaultOption = Array.from(select.options).find((option) => (
                            option.value
                            && option.dataset.branchId === branchId
                            && option.dataset.warehouseTypeId === defaultWarehouseTypeId
                        ));

                        select.value = defaultOption?.value || '';
                    }
                });
            };

            branchSelect?.addEventListener('change', () => refreshWarehouses(true));
            refreshWarehouses(false);
        });
    </script>
</x-app-layout>
