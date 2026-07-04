<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record->exists ? 'Edit Purchase Request' : 'New Purchase Request' }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Header and item lines are required before submit approval.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <form
            method="POST"
            action="{{ $action }}"
            x-data="{
                rows: {{ old('lines') ? count(old('lines')) : ($record->lines->count() ?: 1) }},
                prepareSubmit() {
                    this.$el.querySelectorAll('[data-pr-line]').forEach((row) => {
                        const item = row.querySelector('[data-pr-item]');
                        const quantity = row.querySelector('[data-pr-quantity]');
                        const unit = row.querySelector('[data-pr-unit]');
                        const isEmpty = !item.value || !quantity.value || parseFloat(quantity.value) <= 0;

                        if (isEmpty) {
                            row.querySelectorAll('input, select, textarea').forEach((input) => input.disabled = true);
                        } else if (!unit.value) {
                            const selectedItem = item.selectedOptions[0];
                            unit.value = selectedItem?.dataset.unitId || '';
                        }
                    });
                }
            }"
            @submit="prepareSubmit()"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 border-b border-slate-100 p-6 md:grid-cols-3">
                <div>
                    <label class="text-sm font-bold text-slate-600">Request Date</label>
                    <input type="date" name="request_date" value="{{ old('request_date', optional($record->request_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Department</label>
                    <input type="text" name="department" value="{{ old('department', $record->department) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
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
                    <button type="button" @click="rows++" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Add Row</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-y border-slate-100 bg-slate-50 text-left text-xs font-black uppercase text-slate-500">
                                <th class="px-3 py-3">Item</th>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Required</th>
                                <th class="px-3 py-3">Notes</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @for($i = 0; $i < 40; $i++)
                                @php $line = $lines[$i] ?? []; @endphp
                                <tr x-show="{{ $i }} < rows" data-pr-line>
                                    <td class="px-3 py-3">
                                        <select name="lines[{{ $i }}][item_id]" :disabled="{{ $i }} >= rows" data-pr-item @change="$el.closest('[data-pr-line]').querySelector('[data-pr-unit]').value = $el.selectedOptions[0]?.dataset.unitId || ''" class="w-56 rounded-lg border-slate-200 text-sm">
                                            <option value="">Select item</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-unit-id="{{ $item->unit_of_measure_id }}" @selected((string)($line['item_id'] ?? '') === (string)$item->id)>{{ $item->sku }} - {{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-3"><input name="lines[{{ $i }}][description]" :disabled="{{ $i }} >= rows" value="{{ $line['description'] ?? '' }}" class="w-64 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3"><input type="number" step="0.0001" name="lines[{{ $i }}][quantity]" :disabled="{{ $i }} >= rows" data-pr-quantity value="{{ $line['quantity'] ?? '' }}" class="w-28 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3">
                                        <select name="lines[{{ $i }}][unit_id]" :disabled="{{ $i }} >= rows" data-pr-unit class="w-36 rounded-lg border-slate-200 text-sm">
                                            <option value="">Select Unit</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" @selected((string)($line['unit_id'] ?? '') === (string)$unit->id)>{{ $unit->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-3"><input type="date" name="lines[{{ $i }}][required_date]" :disabled="{{ $i }} >= rows" value="{{ isset($line['required_date']) ? str($line['required_date'])->substr(0, 10) : '' }}" class="w-40 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3"><input name="lines[{{ $i }}][notes]" :disabled="{{ $i }} >= rows" value="{{ $line['notes'] ?? '' }}" class="w-52 rounded-lg border-slate-200 text-sm"></td>
                                    <td class="px-3 py-3"><button type="button" @click="rows = Math.max(1, rows - 1)" class="rounded-lg px-2 py-1 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <a href="{{ route('purchase-requests.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Save Draft</button>
            </div>
        </form>
    </div>
</x-app-layout>
