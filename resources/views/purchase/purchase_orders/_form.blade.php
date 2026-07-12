<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record->exists ? 'Edit Purchase Order' : 'New Purchase Order' }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">{{ $purchaseRequest ? 'Create a purchase order from the approved purchase request.' : 'Direct purchase order' }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        @if($purchaseRequest)
            <x-source-document-summary
                class="mb-3"
                type="Purchase Request"
                :number="$purchaseRequest->number"
                :status="$purchaseRequest->status"
                :subtitle="$purchaseRequest->requester?->name ?: $purchaseRequest->department"
                :metadata="[
                    ['label' => 'Branch', 'value' => $purchaseRequest->branch?->name],
                    ['label' => 'Request Date', 'value' => $purchaseRequest->request_date?->format('d M Y')],
                    ['label' => 'Department', 'value' => $purchaseRequest->department],
                ]"
                :action-url="Auth::user()?->canAccessModule('purchase') && \App\Support\ModuleManager::enabled('purchase') ? route('purchase-requests.show', $purchaseRequest) : null"
                action-label="View PR"
            />
        @endif

        <form
            method="POST"
            action="{{ $action }}"
            x-data="{
                rows: {{ old('lines') ? count(old('lines')) : ($record->lines->count() ?: 1) }},
                prepareSubmit() {
                    this.$el.querySelectorAll('[data-po-line]').forEach((row) => {
                        const item = row.querySelector('[data-po-item]');
                        const quantity = row.querySelector('[data-po-qty]');
                        const unit = row.querySelector('[data-po-unit]');
                        const isEmpty = !item.value || !quantity.value || parseFloat(quantity.value) <= 0;

                        if (isEmpty) {
                            row.querySelectorAll('input, select, textarea').forEach((input) => input.disabled = true);
                        }
                    });
                }
            }"
            @submit="prepareSubmit()"
            class="enterprise-form overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            id="po-form"
        >
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif
            <input type="hidden" name="purchase_request_id" value="{{ old('purchase_request_id', $record->purchase_request_id) }}">

            <div class="grid gap-5 border-b border-slate-100 p-6 md:grid-cols-4">
                <div>
                    <label class="text-sm font-bold text-slate-600">Supplier</label>
                    <div class="mt-1">
                        <x-searchable-select
                            name="supplier_id"
                            :url="route('purchase.lookup.suppliers')"
                            placeholder="Search supplier by code or name..."
                            :selected-id="$selectedSupplier['id'] ?? null"
                            :selected-text="$selectedSupplier['text'] ?? ''"
                        />
                    </div>
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Order Date</label>
                    <input type="date" name="order_date" value="{{ old('order_date', optional($record->order_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Expected Date</label>
                    <input type="date" name="expected_date" value="{{ old('expected_date', optional($record->expected_date)->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes', $record->notes) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </div>

            @php $lines = old('lines', $record->lines->toArray() ?: [[]]); @endphp
            <div class="p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-base font-black text-slate-950">Item Lines</h3>
                    <button type="button" @click="rows++" class="button-primary">Add Row</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-y border-slate-100 bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                                <th class="px-3 py-3">Item</th><th class="px-3 py-3">Description</th><th class="px-3 py-3">Qty</th><th class="px-3 py-3">Unit</th><th class="px-3 py-3">Price</th><th class="px-3 py-3">Tax %</th><th class="px-3 py-3">Subtotal</th><th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @for($i = 0; $i < 40; $i++)
                                @php $line = $lines[$i] ?? []; @endphp
                                <tr x-show="{{ $i }} < rows" data-po-line>
                                    <td class="px-3 py-3">
                                        <input type="hidden" name="lines[{{ $i }}][purchase_request_line_id]" :disabled="{{ $i }} >= rows" value="{{ $line['purchase_request_line_id'] ?? '' }}">
                                        <x-searchable-select
                                            name="lines[{{ $i }}][item_id]"
                                            :url="route('purchase.lookup.items')"
                                            placeholder="Search item by SKU or name..."
                                            :selected-id="$line['item_id'] ?? null"
                                            :selected-text="$selectedItems[$line['item_id'] ?? null]['text'] ?? ''"
                                            unit-target="[data-po-unit]"
                                            description-target="[data-po-description]"
                                            input-class="w-64"
                                            x-bind:disabled="{{ $i }} >= rows"
                                            data-po-item
                                        />
                                    </td>
                                    <td class="px-3 py-3"><input name="lines[{{ $i }}][description]" :disabled="{{ $i }} >= rows" data-po-description value="{{ $line['description'] ?? '' }}" class="w-64 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3"><input data-po-qty type="number" step="0.0001" name="lines[{{ $i }}][quantity]" :disabled="{{ $i }} >= rows" value="{{ $line['quantity'] ?? '' }}" class="w-28 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3">
                                        <select name="lines[{{ $i }}][unit_id]" :disabled="{{ $i }} >= rows" data-po-unit class="w-32 rounded-lg border-slate-200 text-sm">
                                            <option value="">Select Unit</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" @selected((string)($line['unit_id'] ?? '') === (string)$unit->id)>{{ $unit->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-3"><input data-po-price type="number" step="0.0001" name="lines[{{ $i }}][unit_price]" :disabled="{{ $i }} >= rows" value="{{ $line['unit_price'] ?? 0 }}" class="w-32 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3"><input data-po-tax type="number" step="0.0001" name="lines[{{ $i }}][tax_percent]" :disabled="{{ $i }} >= rows" value="{{ $line['tax_percent'] ?? 0 }}" class="w-24 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3 font-semibold text-slate-900" data-po-line-total>0.00</td>
                                    <td class="px-3 py-3"><button type="button" @click="rows = Math.max(1, rows - 1); setTimeout(window.recalcPoTotals, 50)" class="rounded-lg px-2 py-1 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 grid gap-3 md:ml-auto md:w-96">
                    <div class="flex justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm"><span class="font-bold text-slate-600">Subtotal</span><span class="font-black text-slate-950" id="po-subtotal">0.00</span></div>
                    <div class="flex justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm"><span class="font-bold text-slate-600">Tax Total</span><span class="font-black text-slate-950" id="po-tax">0.00</span></div>
                    <div class="flex justify-between rounded-lg bg-emerald-50 px-4 py-3 text-sm"><span class="font-bold text-emerald-700">Grand Total</span><span class="font-black text-emerald-900" id="po-grand">0.00</span></div>
                </div>
            </div>

            <div class="enterprise-action-bar sticky bottom-0 z-20">
                <a href="{{ route('purchase-orders.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button class="button-primary">Save Draft</button>
            </div>
        </form>
    </div>

    <script>
        window.recalcPoTotals = function () {
            let subtotal = 0;
            let tax = 0;
            document.querySelectorAll('#po-form tbody tr').forEach((row) => {
                if (row.querySelector('[data-po-item]')?.disabled) {
                    return;
                }

                const qty = parseFloat(row.querySelector('[data-po-qty]')?.value || 0);
                const price = parseFloat(row.querySelector('[data-po-price]')?.value || 0);
                const taxPercent = parseFloat(row.querySelector('[data-po-tax]')?.value || 0);
                const lineSubtotal = qty * price;
                const lineTax = lineSubtotal * taxPercent / 100;
                subtotal += lineSubtotal;
                tax += lineTax;
                row.querySelector('[data-po-line-total]').textContent = lineSubtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });
            document.getElementById('po-subtotal').textContent = subtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('po-tax').textContent = tax.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('po-grand').textContent = (subtotal + tax).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        document.addEventListener('input', window.recalcPoTotals);
        document.addEventListener('DOMContentLoaded', window.recalcPoTotals);
    </script>
</x-app-layout>
