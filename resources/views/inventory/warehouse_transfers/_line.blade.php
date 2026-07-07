@php
    $isBatchTracked = (bool) ($tracking['is_batch_tracked'] ?? false);
    $hasExpiryDate = (bool) ($tracking['has_expiry_date'] ?? false);
    $itemId = $line['item_id'] ?? '';
    $quantity = (float) ($line['quantity'] ?? 0);
    $uomId = $line['unit_of_measure_id'] ?? '';
    $uomText = $existingLine?->unit?->code ?? '-';
    $cell = 'h-11 whitespace-nowrap px-2 py-1 align-middle';
@endphp

<tr
    data-line
    data-index="{{ $index }}"
    data-batch-tracked="{{ $isBatchTracked ? '1' : '0' }}"
    data-expiry-tracked="{{ $hasExpiryDate ? '1' : '0' }}"
    data-allow-negative="{{ ($tracking['allow_negative_stock'] ?? false) ? '1' : '0' }}"
    data-current-item="{{ $itemId }}"
    data-current-batch="{{ $line['batch_no'] ?? '' }}"
    data-current-expiry="{{ $line['expiry_date'] ?? '' }}"
    class="h-11 border-b border-slate-100 bg-white text-sm hover:bg-slate-50"
>
    <td class="{{ $cell }} w-12 text-center text-xs font-black text-slate-400" data-line-number>
        {{ is_numeric($index) ? ((int) $index + 1) : '-' }}
    </td>

    <td class="{{ $cell }} min-w-80">
        <span class="sr-only" data-line-sku>-</span>
        <x-searchable-select
            name="lines[{{ $index }}][item_id]"
            :url="$itemSearchUrl"
            placeholder="Search SKU or item..."
            :selected-id="$itemId"
            :selected-text="$selectedText"
            :on-select="'window.linvyWarehouseTransfer.selectItem(option, this.$root.closest(\'[data-line]\'))'"
            :extra-params="['warehouse_id' => '[data-from-warehouse]']"
        />
        @error("lines.$index.item_id")<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </td>

    <td class="{{ $cell }} w-48">
        <select data-batch-select class="hidden h-9 w-full rounded-md border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Select batch</option>
        </select>
        <input type="hidden" name="lines[{{ $index }}][batch_no]" value="{{ $line['batch_no'] ?? '' }}" data-batch-no>
        <span data-batch-na class="flex h-9 items-center rounded-md border border-slate-200 bg-slate-50 px-2 text-sm font-semibold text-slate-500">No Batch</span>
        @error("lines.$index.batch_no")<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </td>

    <td class="{{ $cell }} w-36">
        <input type="text" name="lines[{{ $index }}][expiry_date]" value="{{ $line['expiry_date'] ?? '' }}" data-expiry-date readonly class="h-9 w-full rounded-md border-slate-200 bg-slate-50 text-sm text-slate-600">
        @error("lines.$index.expiry_date")<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </td>

    <td class="{{ $cell }} w-36">
        <input type="text" value="0.00" readonly data-available-qty data-available-value="0" class="h-9 w-full rounded-md border-slate-200 bg-slate-50 text-right text-sm font-semibold text-slate-700">
    </td>

    <td class="{{ $cell }} w-32">
        <input type="number" step="0.01" min="0.01" name="lines[{{ $index }}][quantity]" value="{{ number_format($quantity, 2, '.', '') }}" data-transfer-qty class="h-9 w-full rounded-md border-slate-200 text-right text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500">
        <p data-line-validation class="mt-1 hidden text-[11px] font-bold text-red-600"></p>
        @error("lines.$index.quantity")<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </td>

    <td class="{{ $cell }} w-32 text-right">
        <span data-remaining-qty class="inline-flex min-w-24 justify-end rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700 ring-1 ring-slate-200">0.00</span>
    </td>

    <td class="{{ $cell }} w-24">
        <input type="hidden" name="lines[{{ $index }}][unit_of_measure_id]" value="{{ $uomId }}" data-uom-id>
        <span data-uom-text class="font-bold text-slate-700">{{ $uomText }}</span>
    </td>

    <td class="{{ $cell }} min-w-48">
        <input name="lines[{{ $index }}][notes]" value="{{ $line['notes'] ?? '' }}" placeholder="Remark" class="h-9 w-full rounded-md border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
    </td>

    <td class="{{ $cell }} w-20 text-right">
        <button type="button" data-remove-line class="h-9 rounded-md border border-red-200 bg-white px-2 text-xs font-bold text-red-700 hover:bg-red-50">Delete</button>
    </td>
</tr>
