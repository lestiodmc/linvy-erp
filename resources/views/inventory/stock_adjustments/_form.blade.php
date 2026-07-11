<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record->exists ? 'Edit Stock Adjustment' : 'New Stock Adjustment' }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Count inventory in one warehouse and post the variance.</p>
        </div>
    </x-slot>

    @php
        $lines = old('lines', $record->lines->toArray() ?: []);
        $lines = count($lines) ? $lines : [[
            'item_id' => '',
            'system_qty' => 0,
            'counted_qty' => 0,
            'adjustment_qty' => 0,
            'uom_id' => '',
            'batch_no' => '',
            'serial_numbers' => '',
            'expiry_date' => '',
            'remarks' => '',
        ]];
        $initialTracking = collect($lines)->map(fn ($line) => $lineItemTracking[$line['item_id'] ?? null] ?? []);
        $showBatchColumn = $initialTracking->contains(fn ($tracking) => (bool) ($tracking['is_batch_tracked'] ?? false) || (bool) ($tracking['has_expiry_date'] ?? false));
        $showSerialColumn = $initialTracking->contains(fn ($tracking) => (bool) ($tracking['is_serial_tracked'] ?? false));
        $showExpiryColumn = $initialTracking->contains(fn ($tracking) => (bool) ($tracking['has_expiry_date'] ?? false));
        $itemSearchUrl = route('stock-adjustments.items');
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-stock-adjustment-form>
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-4 border-b border-slate-100 p-5 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="text-sm font-bold text-slate-600">Company</label>
                    <div class="mt-1 flex h-10 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-700" data-company-label>
                        {{ $record->company?->name ?: '-' }}
                    </div>
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Branch</label>
                    <select name="branch_id" data-adjustment-branch class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" data-company-name="{{ $branch->company?->name }}" @selected((string) old('branch_id', $record->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Warehouse</label>
                    <select name="warehouse_id" data-adjustment-warehouse class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" data-branch-id="{{ $warehouse->branch_id }}" @selected((string) old('warehouse_id', $record->warehouse_id) === (string) $warehouse->id)>
                                {{ $warehouse->branch?->name ? $warehouse->branch->name.' - ' : '' }}{{ $warehouse->code ? $warehouse->code.' - ' : '' }}{{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Adjustment Date</label>
                    <input type="date" name="adjustment_date" value="{{ old('adjustment_date', optional($record->adjustment_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @error('adjustment_date')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-bold text-slate-600">Reason Code</label>
                    <select name="reason_code" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Select reason</option>
                        @foreach($reasonCodes as $code => $label)
                            <option value="{{ $code }}" @selected((string) old('reason_code', $record->reason_code ?: \App\Models\StockAdjustment::REASON_CORRECTION) === (string) $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason_code')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 lg:col-span-5">
                    <label class="text-sm font-bold text-slate-600">Notes</label>
                    <input name="notes" value="{{ old('notes', $record->notes) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <input type="hidden" name="reason" value="{{ old('reason', $record->reason) }}">
                </div>
            </div>

            <div class="p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-base font-black text-slate-950">Adjustment Lines</h3>
                    <button type="button" data-add-line class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Add Line</button>
                </div>

                @error('lines')<p class="mb-3 rounded-lg bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                @error('inventory')<p class="mb-3 rounded-lg bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                <p data-warehouse-message class="mb-3 hidden rounded-lg bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">Select branch and warehouse before fetching item stock.</p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-black uppercase text-slate-500">
                                <th class="px-3 py-2">Item</th>
                                <th class="px-3 py-2 text-right">System Qty</th>
                                <th class="px-3 py-2 text-right">Physical Qty</th>
                                <th class="px-3 py-2 text-right">Difference</th>
                                <th class="px-3 py-2">UoM</th>
                                <th class="px-3 py-2 {{ $showBatchColumn ? '' : 'hidden' }}" data-header-col="batch">Batch</th>
                                <th class="px-3 py-2 {{ $showSerialColumn ? '' : 'hidden' }}" data-header-col="serial">Serial Numbers</th>
                                <th class="px-3 py-2 {{ $showExpiryColumn ? '' : 'hidden' }}" data-header-col="expiry">Expiry</th>
                                <th class="px-3 py-2">Remark</th>
                                <th class="sticky right-0 bg-slate-50 px-3 py-2 text-right shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.45)]">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" data-lines>
                            @foreach($lines as $i => $line)
                                @php($tracking = $lineItemTracking[$line['item_id'] ?? null] ?? [])
                                @php($existingLine = $record->lines->get($i))
                                <x-inventory.adjustment-row
                                    :index="$i"
                                    :line="$line"
                                    :selected-text="$selectedItems[$line['item_id'] ?? null] ?? ''"
                                    :existing-line="$existingLine"
                                    :tracking="$tracking"
                                    :item-search-url="$itemSearchUrl"
                                />
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <a href="{{ route('stock-adjustments.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button name="action" value="draft" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Save Draft</button>
                <button name="action" value="post" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Post Adjustment</button>
            </div>
        </form>
    </div>

    <div data-item-lookup-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4">
        <div class="w-full max-w-4xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-black text-slate-950">Browse Items</h3>
                <button type="button" data-close-item-lookup class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Close</button>
            </div>
            <div class="p-5">
                <input data-item-lookup-search type="search" placeholder="Search SKU or item name..." class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                    <div class="max-h-96 overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="sticky top-0 bg-slate-50">
                                <tr class="text-left text-xs font-black uppercase text-slate-500">
                                    <th class="px-4 py-3">SKU</th>
                                    <th class="px-4 py-3">Item Name</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3 text-right">Available Qty</th>
                                    <th class="px-4 py-3">UoM</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody data-item-lookup-results class="divide-y divide-slate-100">
                                <tr><td colspan="6" class="px-4 py-8 text-center text-sm font-semibold text-slate-500">Search item by SKU or name.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template data-line-template>
        <x-inventory.adjustment-row
            index="__INDEX__"
            :line="[]"
            selected-text=""
            :tracking="[]"
            :item-search-url="$itemSearchUrl"
        />
    </template>

    <script>
        window.linvyStockAdjustment = {
            activeLookupRow: null,
            lookupTimer: null,
            selectItem(option, row) {
                row.dataset.batchTracked = option.tracking?.is_batch_tracked ? '1' : '0';
                row.dataset.serialTracked = option.tracking?.is_serial_tracked ? '1' : '0';
                row.dataset.expiryTracked = option.tracking?.has_expiry_date ? '1' : '0';
                row.querySelector('[data-uom-id]').value = option.unit_id || '';
                row.querySelector('[data-uom-text]').textContent = option.unit_text || '-';
                this.setBatchOptions(row, option.batches || []);
                this.applyTracking(row);
                this.fetchCurrentStock(row);
            },
            setBatchOptions(row, batches) {
                const select = row.querySelector('[data-batch-select]');
                if (!select) return;

                select.innerHTML = '<option value="">Select batch</option>';
                batches.forEach((batch) => {
                    if (Number(batch.qty_on_hand || 0) <= 0) return;
                    const option = document.createElement('option');
                    option.value = batch.batch_no || '';
                    option.textContent = `${batch.label || batch.batch_no} (${Number(batch.qty_on_hand || 0).toFixed(4)})`;
                    option.dataset.expiryDate = batch.expiry_date || '';
                    option.dataset.qtyOnHand = batch.qty_on_hand || 0;
                    select.appendChild(option);
                });

                const currentBatch = row.querySelector('[data-batch-no]')?.value || '';
                if (currentBatch && Array.from(select.options).some((option) => option.value === currentBatch)) {
                    select.value = currentBatch;
                }
            },
            setSearchableValue(row, option) {
                const root = row.querySelector('[x-data^="linvySearchableSelect"]');
                const hidden = row.querySelector('input[type=hidden][name$="[item_id]"]');
                const input = row.querySelector('[data-searchable-input]');

                if (hidden) hidden.value = option.id || '';
                if (input) input.value = option.text || '';

                if (root && window.Alpine) {
                    const data = window.Alpine.$data(root);
                    data.selectedId = option.id || '';
                    data.query = option.text || '';
                    data.open = false;
                    data.results = [];
                }
            },
            openLookup(row) {
                const form = document.querySelector('[data-stock-adjustment-form]');
                const warehouseId = form.querySelector('[data-adjustment-warehouse]')?.value || '';
                const message = document.querySelector('[data-warehouse-message]');

                message?.classList.toggle('hidden', Boolean(warehouseId));
                if (!warehouseId) return;

                this.activeLookupRow = row;
                const modal = document.querySelector('[data-item-lookup-modal]');
                const search = modal.querySelector('[data-item-lookup-search]');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                search.value = '';
                this.renderLookupRows([]);
                setTimeout(() => search.focus(), 50);
            },
            closeLookup() {
                const modal = document.querySelector('[data-item-lookup-modal]');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                this.activeLookupRow = null;
            },
            searchLookup(query) {
                clearTimeout(this.lookupTimer);
                this.lookupTimer = setTimeout(() => this.fetchLookupRows(query), 250);
            },
            fetchLookupRows(query) {
                const form = document.querySelector('[data-stock-adjustment-form]');
                const warehouseId = form.querySelector('[data-adjustment-warehouse]')?.value || '';

                if (!warehouseId || query.trim().length < 2) {
                    this.renderLookupRows([]);
                    return;
                }

                const url = new URL(@js(route('stock-adjustments.items')), window.location.origin);
                url.searchParams.set('q', query);
                url.searchParams.set('warehouse_id', warehouseId);

                fetch(url, { headers: { Accept: 'application/json' } })
                    .then((response) => response.ok ? response.json() : [])
                    .then((items) => this.renderLookupRows(items));
            },
            renderLookupRows(items) {
                const tbody = document.querySelector('[data-item-lookup-results]');

                if (!items.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-sm font-semibold text-slate-500">Search item by SKU or name.</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map((item) => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-bold text-slate-900">${this.escapeHtml(item.sku || '')}</td>
                        <td class="px-4 py-3 text-slate-700">${this.escapeHtml(item.name || '')}</td>
                        <td class="px-4 py-3 text-slate-600">${this.escapeHtml(item.category || '-')}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700">${Number(item.available_qty || 0).toFixed(4)}</td>
                        <td class="px-4 py-3 text-slate-600">${this.escapeHtml(item.unit_text || '-')}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" data-select-lookup-item data-item='${this.escapeAttribute(JSON.stringify(item))}' class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">Select</button>
                        </td>
                    </tr>
                `).join('');
            },
            selectLookupItem(item) {
                if (!this.activeLookupRow) return;
                this.setSearchableValue(this.activeLookupRow, item);
                this.selectItem(item, this.activeLookupRow);
                this.closeLookup();
            },
            escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                }[char]));
            },
            escapeAttribute(value) {
                return this.escapeHtml(value).replace(/`/g, '&#096;');
            },
            recalculate(row) {
                const systemQty = Number(row.querySelector('[data-system-qty]')?.value || 0);
                const countedQty = Number(row.querySelector('[data-counted-qty]')?.value || 0);
                const adjustmentQty = countedQty - systemQty;
                const badge = row.querySelector('[data-adjustment-badge]');
                const hidden = row.querySelector('[data-adjustment-qty]');
                if (hidden) hidden.value = adjustmentQty.toFixed(6);
                badge.textContent = adjustmentQty.toFixed(4);
                badge.className = 'inline-flex min-w-20 justify-end rounded-full px-2.5 py-1 text-xs font-black ' + (
                    adjustmentQty > 0 ? 'bg-emerald-50 text-emerald-700' : (adjustmentQty < 0 ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600')
                );
            },
            refreshColumns() {
                const rows = Array.from(document.querySelectorAll('[data-line]'));
                const flags = {
                    batch: rows.some((row) => row.dataset.batchTracked === '1' || row.dataset.expiryTracked === '1'),
                    serial: rows.some((row) => row.dataset.serialTracked === '1'),
                    expiry: rows.some((row) => row.dataset.expiryTracked === '1'),
                };

                Object.entries(flags).forEach(([column, visible]) => {
                    document.querySelectorAll(`[data-header-col="${column}"], [data-col="${column}"]`).forEach((element) => {
                        element.classList.toggle('hidden', !visible);
                    });
                });
            },
            applyTracking(row) {
                const batchInput = row.querySelector('[data-batch-no]');
                const batchSelect = row.querySelector('[data-batch-select]');
                const serialInput = row.querySelector('[data-serial-numbers]');
                const expiryInput = row.querySelector('[data-expiry-date]');
                const isBatch = row.dataset.batchTracked === '1';
                const isSerial = row.dataset.serialTracked === '1';
                const hasExpiry = row.dataset.expiryTracked === '1';
                batchInput.disabled = isSerial || !isBatch;
                if (batchSelect) batchSelect.disabled = batchInput.disabled;
                serialInput.disabled = !isSerial;
                expiryInput.disabled = isSerial || !isBatch || !hasExpiry;
                if (batchSelect) batchSelect.classList.toggle('hidden', batchInput.disabled);
                serialInput.classList.toggle('hidden', serialInput.disabled);
                expiryInput.classList.toggle('hidden', expiryInput.disabled);
                row.querySelector('[data-batch-na]')?.classList.toggle('hidden', !batchInput.disabled);
                row.querySelector('[data-serial-na]')?.classList.toggle('hidden', !serialInput.disabled);
                row.querySelector('[data-expiry-na]')?.classList.toggle('hidden', !expiryInput.disabled);
                if (batchInput.disabled) batchInput.value = '';
                if (batchInput.disabled && batchSelect) batchSelect.value = '';
                if (serialInput.disabled) serialInput.value = '';
                if (expiryInput.disabled) expiryInput.value = '';
                this.refreshColumns();
            },
            fetchCurrentStock(row) {
                const form = document.querySelector('[data-stock-adjustment-form]');
                const warehouseId = form.querySelector('[data-adjustment-warehouse]')?.value || '';
                const itemId = row.querySelector('input[type=hidden][name$="[item_id]"]')?.value || '';
                const batchNo = row.querySelector('[data-batch-no]')?.value || '';
                const expiryDate = row.querySelector('[data-expiry-date]')?.value || '';
                const message = document.querySelector('[data-warehouse-message]');

                message?.classList.toggle('hidden', Boolean(warehouseId));
                if (!warehouseId || !itemId) return;

                const url = new URL(@js(route('inventory.stock-adjustments.item-info')), window.location.origin);
                url.searchParams.set('warehouse_id', warehouseId);
                url.searchParams.set('item_id', itemId);
                if (batchNo) url.searchParams.set('batch_no', batchNo);
                if (expiryDate) url.searchParams.set('expiry_date', expiryDate);

                fetch(url, { headers: { Accept: 'application/json' } })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        if (!data) return;
                        row.dataset.batchTracked = data.batch ? '1' : '0';
                        row.dataset.serialTracked = data.serial ? '1' : '0';
                        row.dataset.expiryTracked = data.expiry ? '1' : '0';
                        this.setBatchOptions(row, data.batches || []);
                        row.querySelector('[data-system-qty]').value = Number(data.current_stock || data.system_qty || 0).toFixed(6);
                        row.querySelector('[data-uom-id]').value = data.uom_id || row.querySelector('[data-uom-id]').value;
                        row.querySelector('[data-uom-text]').textContent = data.uom || data.uom_text || row.querySelector('[data-uom-text]').textContent;
                        row.querySelector('[data-item-meta]').textContent = `${data.text || ''} | ${data.warehouse || ''}`.replace(/\s\|\s$/, '');
                        this.applyTracking(row);
                        this.recalculate(row);
                    });
            },
        };

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-stock-adjustment-form]');
            const branchSelect = form.querySelector('[data-adjustment-branch]');
            const warehouseSelect = form.querySelector('[data-adjustment-warehouse]');
            const lines = form.querySelector('[data-lines]');
            const template = document.querySelector('[data-line-template]');
            let nextIndex = lines.querySelectorAll('[data-line]').length;

            const refreshWarehouses = (resetInvalid = false) => {
                const branchId = branchSelect.value || '';
                const selectedBranch = branchSelect.selectedOptions[0];
                const companyLabel = form.querySelector('[data-company-label]');
                if (companyLabel) companyLabel.textContent = selectedBranch?.dataset.companyName || '-';

                Array.from(warehouseSelect.options).forEach((option) => {
                    if (!option.value) return;
                    const visible = option.dataset.branchId === branchId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });
                if (resetInvalid && warehouseSelect.selectedOptions[0]?.dataset.branchId !== branchId) {
                    warehouseSelect.value = '';
                }
            };

            form.addEventListener('input', (event) => {
                const row = event.target.closest('[data-line]');
                if (!row) return;
                if (event.target.matches('[data-counted-qty]')) window.linvyStockAdjustment.recalculate(row);
                if (event.target.matches('[data-batch-no]')) window.linvyStockAdjustment.fetchCurrentStock(row);
                if (event.target.matches('[data-expiry-date]')) window.linvyStockAdjustment.fetchCurrentStock(row);
            });

            form.addEventListener('change', (event) => {
                const row = event.target.closest('[data-line]');
                if (!row) return;
                if (event.target.matches('[data-batch-select]')) {
                    const option = event.target.selectedOptions[0];
                    row.querySelector('[data-batch-no]').value = event.target.value || '';
                    row.querySelector('[data-expiry-date]').value = option?.dataset.expiryDate || '';
                    row.querySelector('[data-system-qty]').value = Number(option?.dataset.qtyOnHand || 0).toFixed(6);
                    window.linvyStockAdjustment.recalculate(row);
                }
            });

            form.addEventListener('click', (event) => {
                if (event.target.matches('[data-add-line]')) {
                    lines.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex));
                    nextIndex += 1;
                    window.Alpine?.initTree(lines.lastElementChild);
                    window.linvyStockAdjustment.applyTracking(lines.lastElementChild);
                    lines.lastElementChild.querySelector('[data-searchable-input]')?.focus();
                }
                if (event.target.matches('[data-remove-line]') && lines.querySelectorAll('[data-line]').length > 1) {
                    event.target.closest('[data-line]').remove();
                    window.linvyStockAdjustment.refreshColumns();
                }
                if (event.target.matches('[data-browse-item]')) {
                    event.preventDefault();
                    window.linvyStockAdjustment.openLookup(event.target.closest('[data-line]'));
                }
            });

            document.querySelector('[data-item-lookup-search]')?.addEventListener('input', (event) => {
                window.linvyStockAdjustment.searchLookup(event.target.value);
            });

            document.querySelector('[data-item-lookup-modal]')?.addEventListener('click', (event) => {
                if (event.target.matches('[data-item-lookup-modal]')) {
                    window.linvyStockAdjustment.closeLookup();
                }
                if (event.target.matches('[data-close-item-lookup]')) {
                    window.linvyStockAdjustment.closeLookup();
                }
                if (event.target.matches('[data-select-lookup-item]')) {
                    window.linvyStockAdjustment.selectLookupItem(JSON.parse(event.target.dataset.item || '{}'));
                }
            });

            branchSelect.addEventListener('change', () => refreshWarehouses(true));
            warehouseSelect.addEventListener('change', () => lines.querySelectorAll('[data-line]').forEach((row) => window.linvyStockAdjustment.fetchCurrentStock(row)));
            refreshWarehouses(false);
            lines.querySelectorAll('[data-line]').forEach((row) => {
                window.linvyStockAdjustment.applyTracking(row);
                window.linvyStockAdjustment.recalculate(row);
            });
            window.linvyStockAdjustment.refreshColumns();
        });
    </script>
</x-app-layout>
