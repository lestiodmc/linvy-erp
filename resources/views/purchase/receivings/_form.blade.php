<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$record->exists ? 'Edit Receiving' : 'New Receiving'"
            subtitle="Record items received from the selected purchase order."
        />
    </x-slot>

    @php
        $selectedPoText = $selectedPo['text'] ?? $record->purchaseOrder?->number;
        $lines = old('lines', $record->lines->toArray() ?: []);
        $lineTracking = collect($lines)->mapWithKeys(function ($line, $index) use ($lineItemTracking) {
            return [$index => $lineItemTracking[$line['item_id'] ?? null] ?? [
                'track_inventory' => true,
                'is_batch_tracked' => false,
                'is_serial_tracked' => false,
                'has_expiry_date' => false,
            ]];
        });
        $showWarehouseColumn = $lineTracking->contains(fn ($tracking) => (bool) ($tracking['track_inventory'] ?? true));
        $showBatchColumn = $lineTracking->contains(fn ($tracking) => (bool) ($tracking['is_batch_tracked'] ?? false));
        $showSerialColumn = $lineTracking->contains(fn ($tracking) => (bool) ($tracking['is_serial_tracked'] ?? false));
        $showExpiryColumn = $lineTracking->contains(fn ($tracking) => (bool) ($tracking['has_expiry_date'] ?? false));
        $emptyColspan = 8 + ($showWarehouseColumn ? 1 : 0) + ($showBatchColumn ? 1 : 0) + ($showSerialColumn ? 1 : 0) + ($showExpiryColumn ? 1 : 0);
        $lineCount = count($lines);
    @endphp

    <div class="mx-auto max-w-screen-2xl space-y-3">
        @include('purchase.shared.flash')

        <form method="POST" action="{{ $action }}" class="enterprise-form space-y-3" data-receiving-form>
            @csrf
            @if($method !== 'POST') @method($method) @endif

            @if($selectedPo['id'] ?? null)
                <x-source-document-summary
                    type="Purchase Order"
                    :number="$selectedPo['number'] ?? $selectedPoText"
                    :status="$selectedPo['status'] ?? 'approved'"
                    :subtitle="$selectedPo['supplier'] ?? '-'"
                    :metadata="[
                        ['label' => 'Branch', 'value' => $selectedPo['branch'] ?? null],
                        ['label' => 'Order Date', 'value' => $selectedPo['order_date'] ?? null],
                        ['label' => 'Expected Date', 'value' => $selectedPo['expected_date'] ?? null],
                    ]"
                    :action-url="route('purchase-orders.show', $selectedPo['id'])"
                    action-label="View PO"
                />
                <input type="hidden" name="purchase_order_id" value="{{ old('purchase_order_id', $selectedPo['id']) }}">
            @else
                <x-form.section title="Purchase Order" subtitle="Select an approved purchase order to load its remaining lines.">
                    <div class="max-w-xl">
                        <label class="enterprise-form-label">Purchase Order <span class="text-red-600">*</span></label>
                        <x-searchable-select
                            name="purchase_order_id"
                            :url="route('purchase.lookup.purchase-orders')"
                            placeholder="Search PO by number or supplier..."
                            :selected-id="$record->purchase_order_id"
                            :selected-text="$selectedPoText ?? ''"
                            :on-select="'window.location.href = \''.route('receivings.create-from-po', ['purchaseOrder' => '__PO_ID__']).'\'.replace(\'__PO_ID__\', option.id)'"
                        />
                        @error('purchase_order_id')<p class="enterprise-form-error">{{ $message }}</p>@enderror
                    </div>
                </x-form.section>
            @endif

            @if($errors->any())
                <div class="rounded-lg border px-3 py-2 text-sm status-danger" role="alert">
                    <b>Please review {{ $errors->count() }} field{{ $errors->count() === 1 ? '' : 's' }} before saving.</b>
                    @error('lines')<span class="ml-1">{{ $message }}</span>@enderror
                </div>
            @endif

            <x-form.section title="Receiving Information" subtitle="Document details and authorized receiving branch.">
                <x-form.grid :columns="3">
                    <x-form.read-only label="Supplier" :value="$selectedPo['supplier'] ?? $record->supplier?->name ?? '-'" />
                    <div>
                        <label for="received_date" class="enterprise-form-label">Received Date <span class="text-red-600">*</span></label>
                        <input id="received_date" type="date" name="received_date" value="{{ old('received_date', optional($record->received_date)->format('Y-m-d') ?: now()->toDateString()) }}" required aria-required="true" @if($errors->has('received_date')) aria-invalid="true" aria-describedby="received_date-error" @endif>
                        @error('received_date')<p id="received_date-error" class="enterprise-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="branch_id" class="enterprise-form-label">Branch <span class="text-red-600">*</span></label>
                        <select id="branch_id" name="branch_id" data-receiving-branch required aria-required="true" @if($errors->has('branch_id')) aria-invalid="true" aria-describedby="branch_id-error" @endif>
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>{{ $branch->name }}</option>@endforeach
                        </select>
                        @error('branch_id')<p id="branch_id-error" class="enterprise-form-error">{{ $message }}</p>@enderror
                        @if($branches->isEmpty())<p class="enterprise-form-error">No branch access assigned for this user.</p>@endif
                    </div>
                    <div>
                        <label for="supplier_delivery_number" class="enterprise-form-label">Supplier Delivery No.</label>
                        <input id="supplier_delivery_number" name="supplier_delivery_number" value="{{ old('supplier_delivery_number', $record->supplier_delivery_number) }}" placeholder="Enter supplier delivery number (optional)" @if($errors->has('supplier_delivery_number')) aria-invalid="true" aria-describedby="supplier_delivery_number-error" @endif>
                        @error('supplier_delivery_number')<p id="supplier_delivery_number-error" class="enterprise-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notes" class="enterprise-form-label">Notes</label>
                        <input id="notes" name="notes" value="{{ old('notes', $record->notes) }}" placeholder="Enter notes (optional)" @if($errors->has('notes')) aria-invalid="true" aria-describedby="notes-error" @endif>
                        @error('notes')<p id="notes-error" class="enterprise-form-error">{{ $message }}</p>@enderror
                    </div>
                </x-form.grid>
            </x-form.section>

            <section class="enterprise-line-items theme-card overflow-visible rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3" style="border-color:var(--theme-border)">
                    <div class="flex items-center gap-2"><h2 class="text-sm font-black">Receiving Lines</h2><span class="theme-primary-soft rounded-full px-2 py-0.5 text-[10px] font-black">{{ $lineCount }} {{ $lineCount === 1 ? 'item' : 'items' }}</span></div>
                    <p data-warehouse-branch-message class="hidden text-xs font-bold text-amber-700">Select branch to load warehouses.</p>
                </div>
                <div class="max-w-full overflow-x-auto overscroll-x-contain">
                    <table class="receiving-lines-table min-w-[76rem] text-xs">
                        <thead class="sticky top-0 z-10"><tr>
                            @foreach(['#', 'Item', 'Ordered', 'Received', 'Receive Qty', 'Remaining'] as $heading)<th scope="col" class="{{ in_array($heading, ['Ordered', 'Received', 'Receive Qty', 'Remaining']) ? 'text-right' : 'text-left' }}">{{ $heading }}</th>@endforeach
                            @if($showWarehouseColumn)<th scope="col" class="text-left">Warehouse</th>@endif
                            @if($showBatchColumn)<th scope="col" class="text-left">Batch No.</th>@endif
                            @if($showSerialColumn)<th scope="col" class="text-left">Serial Numbers</th>@endif
                            @if($showExpiryColumn)<th scope="col" class="text-left">Expiry Date</th>@endif
                            <th scope="col" class="text-right">Unit Cost</th><th scope="col" class="text-left">Notes</th>
                        </tr></thead>
                        <tbody>
                            @forelse($lines as $i => $line)
                                @php
                                    $warehouseTypeId = $lineItemWarehouseTypes[$line['item_id'] ?? null] ?? null;
                                    $tracking = $lineTracking[$i] ?? [];
                                    $tracksInventory = (bool) ($tracking['track_inventory'] ?? true);
                                    $isBatchTracked = (bool) ($tracking['is_batch_tracked'] ?? false);
                                    $isSerialTracked = (bool) ($tracking['is_serial_tracked'] ?? false);
                                    $hasExpiryDate = (bool) ($tracking['has_expiry_date'] ?? false);
                                    $itemText = $selectedItems[$line['item_id'] ?? null] ?? '-';
                                    $sku = str($itemText)->before(' - ');
                                    $itemName = str($itemText)->contains(' - ') ? str($itemText)->after(' - ') : ($line['description'] ?? $itemText);
                                    $maximum = max(0, (float) ($line['ordered_quantity'] ?? 0) - (float) ($line['previously_received_quantity'] ?? 0));
                                @endphp
                                <tr data-receiving-line data-ordered="{{ (float) ($line['ordered_quantity'] ?? 0) }}" data-previous="{{ (float) ($line['previously_received_quantity'] ?? 0) }}">
                                    <td class="text-center font-bold theme-muted">{{ $i + 1 }}</td>
                                    <td class="min-w-52">
                                        <input type="hidden" name="lines[{{ $i }}][purchase_order_line_id]" value="{{ $line['purchase_order_line_id'] }}"><input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $line['item_id'] ?? '' }}">
                                        <div class="font-bold">{{ $sku }}</div><div class="max-w-56 truncate text-[11px] theme-muted" title="{{ $itemName }}">{{ $itemName }}</div>
                                        <div class="mt-1 flex gap-1">
                                            @if(! $tracksInventory)<x-ui.status-badge status="neutral" label="Non Inventory" />
                                            @elseif($isBatchTracked)<x-ui.status-badge status="batch_assignment" label="Batch" />
                                            @else<x-ui.status-badge status="neutral" label="No Batch" />@endif
                                            @if($hasExpiryDate)<x-ui.status-badge status="near_expiry" label="Expiry" />@endif
                                            @if($isSerialTracked)<x-ui.status-badge status="submitted" label="Serial" />@endif
                                        </div>
                                    </td>
                                    <td class="text-right font-semibold tabular-nums">{{ number_format($line['ordered_quantity'] ?? 0, 4) }}</td>
                                    <td class="text-right tabular-nums theme-muted">{{ number_format($line['previously_received_quantity'] ?? 0, 4) }}</td>
                                    <td class="text-right"><input type="number" step="0.0001" min="0.0001" max="{{ $maximum }}" name="lines[{{ $i }}][received_quantity]" value="{{ $line['received_quantity'] ?? 0 }}" data-receive-qty class="receiving-qty-input w-28 text-right font-black tabular-nums" required aria-required="true" @if($errors->has("lines.$i.received_quantity")) aria-invalid="true" aria-describedby="line-{{ $i }}-qty-error" @endif>@error("lines.$i.received_quantity")<p id="line-{{ $i }}-qty-error" class="enterprise-form-error">{{ $message }}</p>@enderror</td>
                                    <td class="text-right font-bold tabular-nums" data-remaining>{{ number_format($line['remaining_quantity'] ?? 0, 4) }}</td>
                                    @if($showWarehouseColumn)<td>
                                        @if($tracksInventory)
                                            <select name="lines[{{ $i }}][warehouse_id]" data-receiving-warehouse data-default-warehouse-type-id="{{ $warehouseTypeId }}" class="w-56" required aria-required="true" title="{{ optional($warehouses->firstWhere('id', $line['warehouse_id'] ?? null))->name }}" @if($errors->has("lines.$i.warehouse_id")) aria-invalid="true" aria-describedby="line-{{ $i }}-warehouse-error" @endif>
                                                <option value="">Select warehouse</option>
                                                @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" data-branch-id="{{ $warehouse->branch_id }}" data-warehouse-type-id="{{ $warehouse->warehouse_type_id }}" title="{{ $warehouse->branch?->name }} - {{ $warehouse->code }} - {{ $warehouse->name }}" @selected((string) ($line['warehouse_id'] ?? '') === (string) $warehouse->id)>{{ $warehouse->code ? $warehouse->code.' — ' : '' }}{{ $warehouse->name }}</option>@endforeach
                                            </select>
                                            @error("lines.$i.warehouse_id")<p id="line-{{ $i }}-warehouse-error" class="enterprise-form-error">{{ $message }}</p>@enderror
                                        @else<input type="hidden" name="lines[{{ $i }}][warehouse_id]" value=""><span class="theme-muted">Not Required</span>@endif
                                    </td>@endif
                                    @if($showBatchColumn)<td>
                                        @if($isBatchTracked)<input name="lines[{{ $i }}][batch_no]" value="{{ $line['batch_no'] ?? '' }}" placeholder="Batch number" class="w-36" required aria-required="true" @if($errors->has("lines.$i.batch_no")) aria-invalid="true" aria-describedby="line-{{ $i }}-batch-error" @endif>@error("lines.$i.batch_no")<p id="line-{{ $i }}-batch-error" class="enterprise-form-error">{{ $message }}</p>@enderror
                                        @else<input type="hidden" name="lines[{{ $i }}][batch_no]" value=""><span class="theme-muted">No Batch</span>@endif
                                    </td>@endif
                                    @if($showSerialColumn)<td>@if($isSerialTracked)<textarea name="lines[{{ $i }}][serial_numbers]" rows="1" placeholder="One serial per line" class="w-44" required aria-required="true">{{ $line['serial_numbers'] ?? '' }}</textarea>@else<input type="hidden" name="lines[{{ $i }}][serial_numbers]" value=""><span class="theme-muted">-</span>@endif</td>@endif
                                    @if($showExpiryColumn)<td>
                                        @if($hasExpiryDate)<input type="date" name="lines[{{ $i }}][expiry_date]" value="{{ $line['expiry_date'] ?? '' }}" class="w-36" required aria-required="true" @if($errors->has("lines.$i.expiry_date")) aria-invalid="true" aria-describedby="line-{{ $i }}-expiry-error" @endif>@error("lines.$i.expiry_date")<p id="line-{{ $i }}-expiry-error" class="enterprise-form-error">{{ $message }}</p>@enderror
                                        @else<input type="hidden" name="lines[{{ $i }}][expiry_date]" value=""><span class="theme-muted">Not Required</span>@endif
                                    </td>@endif
                                    <td class="text-right"><input type="number" step="0.0001" min="0" name="lines[{{ $i }}][unit_cost]" value="{{ $line['unit_cost'] ?? 0 }}" class="w-28 text-right tabular-nums" required></td>
                                    <td><input name="lines[{{ $i }}][notes]" value="{{ $line['notes'] ?? '' }}" placeholder="Line notes" class="w-40"></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $emptyColspan }}" class="py-6 text-center theme-muted">Select an approved purchase order to load receivable lines.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap justify-end gap-6 border-t px-4 py-2 text-xs" style="border-color:var(--theme-border)"><span class="theme-muted">Total Lines <b class="ml-1 theme-text">{{ $lineCount }}</b></span><span class="theme-muted">Lines Receiving <b class="ml-1 theme-text" data-lines-receiving>{{ collect($lines)->filter(fn ($line) => (float) ($line['received_quantity'] ?? 0) > 0)->count() }}</b></span></div>
            </section>

            <div class="enterprise-action-bar sticky bottom-0 z-20 rounded-lg">
                <a href="{{ route('receivings.index') }}" class="enterprise-action theme-focus inline-flex h-9 items-center rounded-lg px-4 text-sm font-bold">Cancel</a>
                <button type="submit" class="theme-focus h-9 rounded-lg px-4 text-sm font-bold">Save Draft</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-receiving-form]');
            const branchSelect = form?.querySelector('[data-receiving-branch]');
            const message = form?.querySelector('[data-warehouse-branch-message]');
            const warehouseSelects = Array.from(form?.querySelectorAll('[data-receiving-warehouse]') || []);
            const lineRows = Array.from(form?.querySelectorAll('[data-receiving-line]') || []);

            const refreshSummary = () => {
                let receiving = 0;
                lineRows.forEach((row) => {
                    const input = row.querySelector('[data-receive-qty]');
                    const ordered = Number(row.dataset.ordered || 0);
                    const previous = Number(row.dataset.previous || 0);
                    const current = Math.max(0, Number(input?.value || 0));
                    const remaining = Math.max(0, ordered - previous - current);
                    row.querySelector('[data-remaining]').textContent = remaining.toLocaleString(undefined, { minimumFractionDigits: 4, maximumFractionDigits: 4 });
                    if (current > 0) receiving++;
                });
                const summary = form?.querySelector('[data-lines-receiving]');
                if (summary) summary.textContent = receiving;
            };

            const refreshWarehouses = (resetInvalid = false) => {
                const branchId = branchSelect?.value || '';
                message?.classList.toggle('hidden', Boolean(branchId));
                warehouseSelects.forEach((select) => {
                    const currentBranchId = select.selectedOptions[0]?.dataset.branchId || '';
                    const defaultType = select.dataset.defaultWarehouseTypeId || '';
                    select.disabled = !branchId;
                    Array.from(select.options).forEach((option) => {
                        const allowed = !option.value || option.dataset.branchId === branchId;
                        option.hidden = !allowed; option.disabled = !allowed;
                    });
                    if (!branchId) select.value = '';
                    else if (resetInvalid && currentBranchId !== branchId) select.value = Array.from(select.options).find((option) => option.value && option.dataset.branchId === branchId && option.dataset.warehouseTypeId === defaultType)?.value || '';
                });
            };

            branchSelect?.addEventListener('change', () => refreshWarehouses(true));
            lineRows.forEach((row) => row.querySelector('[data-receive-qty]')?.addEventListener('input', refreshSummary));
            refreshWarehouses(false); refreshSummary();
        });
    </script>
</x-app-layout>
