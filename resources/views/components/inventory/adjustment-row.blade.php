@props([
    'index',
    'line' => [],
    'selectedText' => '',
    'existingLine' => null,
    'tracking' => [],
    'itemSearchUrl',
])

@php
    $isBatchTracked = (bool) ($tracking['is_batch_tracked'] ?? false);
    $isSerialTracked = (bool) ($tracking['is_serial_tracked'] ?? false);
    $hasExpiryDate = (bool) ($tracking['has_expiry_date'] ?? false);
    $itemId = $line['item_id'] ?? '';
    $systemQty = (float) ($line['system_qty'] ?? 0);
    $countedQty = (float) ($line['counted_qty'] ?? 0);
    $adjustmentQty = (float) ($line['adjustment_qty'] ?? ($countedQty - $systemQty));
    $uomId = $line['uom_id'] ?? $line['unit_of_measure_id'] ?? '';
    $uomText = $existingLine?->uom?->code ?? $existingLine?->unit?->code ?? '-';
@endphp

<tr
    data-line
    data-index="{{ $index }}"
    data-batch-tracked="{{ $isBatchTracked ? '1' : '0' }}"
    data-serial-tracked="{{ $isSerialTracked ? '1' : '0' }}"
    data-expiry-tracked="{{ $hasExpiryDate ? '1' : '0' }}"
>
    <td class="min-w-[320px] max-w-[420px] px-3 py-1">
        <div class="flex items-start gap-2">
            <div class="min-w-0 flex-1">
                <x-searchable-select
                    name="lines[{{ $index }}][item_id]"
                    :url="$itemSearchUrl"
                    placeholder="Select Item"
                    :selected-id="$itemId"
                    :selected-text="$selectedText"
                    input-class="w-full"
                    :on-select="'window.linvyStockAdjustment.selectItem(option, this.$root.closest(\'tr\'))'"
                    :extra-params="['warehouse_id' => '[data-adjustment-warehouse]']"
                />
            </div>
            <button
                type="button"
                data-browse-item
                class="mt-0.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"
            >
                Browse
            </button>
        </div>
        <div class="mt-1 truncate text-xs font-semibold text-slate-500" data-item-meta>
            {{ $selectedText ?: 'SKU and item name load after selection.' }}
        </div>
    </td>
    <td class="px-3 py-1 text-right">
        <input type="number" step="0.000001" name="lines[{{ $index }}][system_qty]" value="{{ number_format($systemQty, 6, '.', '') }}" readonly data-system-qty class="w-28 rounded-lg border-slate-200 bg-slate-50 text-right text-sm text-slate-600">
    </td>
    <td class="px-3 py-1 text-right">
        <input type="number" step="0.000001" min="0" name="lines[{{ $index }}][counted_qty]" value="{{ number_format($countedQty, 6, '.', '') }}" data-counted-qty class="w-28 rounded-lg border-slate-200 text-right text-sm">
    </td>
    <td class="px-3 py-1 text-right">
        <input type="hidden" name="lines[{{ $index }}][adjustment_qty]" value="{{ number_format($adjustmentQty, 6, '.', '') }}" data-adjustment-qty>
        <span data-adjustment-badge class="inline-flex min-w-20 justify-end rounded-full px-2.5 py-1 text-xs font-black {{ $adjustmentQty > 0 ? 'bg-emerald-50 text-emerald-700' : ($adjustmentQty < 0 ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600') }}">
            {{ number_format($adjustmentQty, 4) }}
        </span>
    </td>
    <td class="px-3 py-1">
        <input type="hidden" name="lines[{{ $index }}][uom_id]" value="{{ $uomId }}" data-uom-id>
        <span data-uom-text class="font-semibold text-slate-700">{{ $uomText }}</span>
    </td>
    <td class="px-3 py-1" data-col="batch">
        <select data-batch-select class="hidden w-44 rounded-lg border-slate-200 text-sm">
            <option value="">Select batch</option>
        </select>
        <input type="hidden" name="lines[{{ $index }}][batch_no]" value="{{ $line['batch_no'] ?? '' }}" data-batch-no>
        <span data-batch-na class="flex h-9 items-center rounded-md border border-slate-200 bg-slate-50 px-2 text-sm font-semibold text-slate-500">No Batch</span>
    </td>
    <td class="px-3 py-1" data-col="serial">
        <textarea name="lines[{{ $index }}][serial_numbers]" rows="1" data-serial-numbers placeholder="One per line" class="w-40 rounded-lg border-slate-200 text-sm">{{ $line['serial_numbers'] ?? '' }}</textarea>
        <span data-serial-na class="hidden text-sm font-semibold text-slate-400">-</span>
    </td>
    <td class="px-3 py-1" data-col="expiry">
        <input type="date" name="lines[{{ $index }}][expiry_date]" value="{{ $line['expiry_date'] ?? '' }}" data-expiry-date readonly class="w-36 rounded-lg border-slate-200 bg-slate-50 text-sm">
        <span data-expiry-na class="hidden text-sm font-semibold text-slate-400">-</span>
    </td>
    <td class="px-3 py-1">
        <input name="lines[{{ $index }}][remarks]" value="{{ $line['remarks'] ?? $line['notes'] ?? '' }}" class="w-40 rounded-lg border-slate-200 text-sm">
    </td>
    <td class="sticky right-0 bg-white px-3 py-1 text-right shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.45)]">
        <button type="button" data-remove-line class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">Remove</button>
    </td>
</tr>
