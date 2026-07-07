<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Warehouse Transfer"
            subtitle="Transfer inventory between warehouses."
        >
            <x-slot:action>
                <x-ui.status-badge :status="$record->status ?: \App\Models\WarehouseTransfer::STATUS_DRAFT" />
            </x-slot:action>
        </x-ui.page-header>
    </x-slot>

    @php
        $lines = old('lines', $record->lines->toArray() ?: []);
        $lines = count($lines) ? $lines : [[
            'item_id' => '',
            'batch_no' => '',
            'expiry_date' => '',
            'quantity' => 0,
            'unit_of_measure_id' => '',
            'notes' => '',
        ]];
        $itemSearchUrl = route('warehouse-transfers.items');
        $formatDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y H:i') : null;
    @endphp

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')

        <form method="POST" action="{{ $action }}" class="space-y-3" data-transfer-form>
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Warehouse Transfer</h2>
                        <p class="text-xs font-semibold text-slate-500">Internal inventory movement inside one branch.</p>
                    </div>
                    <x-ui.status-badge :status="$record->status ?: \App\Models\WarehouseTransfer::STATUS_DRAFT" />
                </div>

                <div class="grid gap-3 lg:grid-cols-2">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold text-slate-600">Transfer No</label>
                            <div class="mt-1 flex h-9 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-black text-slate-700">{{ $record->number ?: 'DRAFT' }}</div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Transfer Date</label>
                            <input type="date" name="transfer_date" value="{{ old('transfer_date', optional($record->transfer_date)->format('Y-m-d') ?: now()->toDateString()) }}" class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('transfer_date')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold text-slate-600">Company</label>
                            <select name="company_id" data-company class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Select company</option>
                                @foreach($companies as $companyId => $companyName)
                                    <option value="{{ $companyId }}" @selected((string) old('company_id', $record->company_id) === (string) $companyId)>{{ $companyName }}</option>
                                @endforeach
                            </select>
                            @error('company_id')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600">Branch</label>
                            <select name="branch_id" data-branch class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Select branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" data-company-id="{{ $branch->company_id }}" @selected((string) old('branch_id', $record->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_2rem_minmax(16rem,1fr)] lg:items-end">
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-emerald-700">Source Warehouse</label>
                        <select name="from_warehouse_id" data-from-warehouse class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Select source warehouse</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" data-branch-id="{{ $warehouse->branch_id }}" @selected((string) old('from_warehouse_id', $record->from_warehouse_id) === (string) $warehouse->id)>
                                    {{ $warehouse->branch?->name }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('from_warehouse_id')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs font-semibold text-slate-500">Source Stock: <span data-source-qty>0.00</span> | Items: <span data-source-items>0</span></p>
                    </div>
                    <div class="hidden pb-2 text-center text-lg font-black text-slate-400 lg:block">&darr;</div>
                    <div>
                        <label class="text-xs font-black uppercase tracking-wide text-blue-700">Destination Warehouse</label>
                        <select name="to_warehouse_id" data-to-warehouse class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Select destination warehouse</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" data-branch-id="{{ $warehouse->branch_id }}" @selected((string) old('to_warehouse_id', $record->to_warehouse_id) === (string) $warehouse->id)>
                                    {{ $warehouse->branch?->name }} - {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('to_warehouse_id')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                        <p data-warehouse-error class="mt-1 hidden rounded-lg bg-red-50 px-2 py-1 text-xs font-bold text-red-700">Destination warehouse must be different from source warehouse.</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Destination Stock: <span data-destination-qty>0.00</span> | Items: <span data-destination-items>0</span></p>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Transfer Items</p>
                        <h3 class="text-lg font-black text-slate-950">Editable Transfer Grid</h3>
                    </div>
                    <button type="button" data-add-line class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Add Row</button>
                </div>

                @error('lines')<p class="mx-5 mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                @error('inventory')<p class="mx-5 mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                <p data-warehouse-message class="mx-5 mt-4 hidden rounded-lg bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">Select source warehouse before loading item stock.</p>

                <div class="overflow-x-auto">
                    <table class="min-w-[1180px] table-fixed divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="h-10 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">
                                <th class="w-12 px-2 py-2 text-center">No</th>
                                <th class="min-w-80 px-2 py-2">SKU / Item</th>
                                <th class="w-48 px-2 py-2">Batch</th>
                                <th class="w-36 px-2 py-2">Expiry</th>
                                <th class="w-36 px-2 py-2 text-right">Available</th>
                                <th class="w-32 px-2 py-2 text-right">Transfer Qty</th>
                                <th class="w-32 px-2 py-2 text-right">Remaining</th>
                                <th class="w-24 px-2 py-2">UOM</th>
                                <th class="min-w-48 px-2 py-2">Remark</th>
                                <th class="w-20 px-2 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody data-lines class="divide-y divide-slate-100">
                            @foreach($lines as $i => $line)
                                @php($tracking = $lineItemTracking[$line['item_id'] ?? null] ?? [])
                                @php($existingLine = $record->lines->get($i))
                                @include('inventory.warehouse_transfers._line', [
                                    'index' => $i,
                                    'line' => $line,
                                    'tracking' => $tracking,
                                    'selectedText' => $selectedItems[$line['item_id'] ?? null] ?? '',
                                    'existingLine' => $existingLine,
                                    'itemSearchUrl' => $itemSearchUrl,
                                ])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-6 border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm">
                    <div>
                        <span class="font-bold text-slate-500">Total Lines</span>
                        <span data-total-lines class="ml-2 font-black text-slate-950">0</span>
                    </div>
                    <div>
                        <span class="font-bold text-slate-500">Total Quantity</span>
                        <span data-total-quantity class="ml-2 font-black text-slate-950">0.00</span>
                    </div>
                </div>
                <div class="border-t border-slate-100 px-5 py-3">
                    <label class="text-xs font-bold text-slate-600">Document Remark</label>
                    <input name="notes" value="{{ old('notes', $record->notes) }}" class="mt-1 h-9 w-full rounded-lg border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
            </section>

            <div class="sticky bottom-0 z-20 flex flex-wrap justify-end gap-3 rounded-2xl border border-slate-200 bg-white/95 px-5 py-3 shadow-sm backdrop-blur">
                <a href="{{ route('warehouse-transfers.index') }}" class="rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200">Cancel</a>
                <button name="action" value="draft" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Save Draft</button>
                <button name="action" value="post" data-post-transfer class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">Post Transfer</button>
            </div>
        </form>
    </div>

    <template data-line-template>
        @include('inventory.warehouse_transfers._line', [
            'index' => '__INDEX__',
            'line' => [],
            'tracking' => [],
            'selectedText' => '',
            'existingLine' => null,
            'itemSearchUrl' => $itemSearchUrl,
        ])
    </template>

    <script>
        window.linvyWarehouseTransfer = {
            formatQty(value) {
                return Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            form() {
                return document.querySelector('[data-transfer-form]');
            },
            setAvailableQty(row, value, withUom = true) {
                const input = row.querySelector('[data-available-qty]');
                const unit = row.querySelector('[data-uom-text]')?.textContent?.trim() || '';
                const numericValue = Number(value || 0);
                input.dataset.availableValue = String(numericValue);
                input.value = `${this.formatQty(numericValue)}${withUom && unit && unit !== '-' ? ` ${unit}` : ''}`;
            },
            clearLineItem(row) {
                row.dataset.currentItem = '';
                row.dataset.currentBatch = '';
                row.dataset.currentExpiry = '';
                row.dataset.batchTracked = '0';
                row.dataset.expiryTracked = '0';
                row.dataset.allowNegative = '0';
                row.querySelector('input[type=hidden][name$="[item_id]"]').value = '';
                row.querySelector('[data-line-sku]').textContent = '-';
                row.querySelector('[data-batch-no]').value = '';
                row.querySelector('[data-expiry-date]').value = '';
                row.querySelector('[data-uom-id]').value = '';
                row.querySelector('[data-uom-text]').textContent = '-';
                row.querySelector('[data-transfer-qty]').value = '0.00';
                this.setBatchOptions(row, []);
                this.setAvailableQty(row, 0, false);
                this.applyTracking(row);
                this.recalculateRemaining(row);
            },
            selectItem(option, row) {
                if (String(row.dataset.currentItem || '') !== String(option.id || '')) {
                    row.dataset.currentBatch = '';
                    row.dataset.currentExpiry = '';
                    row.querySelector('[data-batch-no]').value = '';
                    row.querySelector('[data-expiry-date]').value = '';
                }
                row.dataset.currentItem = option.id || '';
                row.querySelector('[data-line-sku]').textContent = option.sku || '-';
                this.applyItemInfo(row, option);
                this.fetchItemInfo(row);
            },
            applyItemInfo(row, data) {
                row.dataset.batchTracked = data.tracking?.is_batch_tracked ? '1' : '0';
                row.dataset.expiryTracked = data.tracking?.has_expiry_date ? '1' : '0';
                row.dataset.allowNegative = data.tracking?.allow_negative_stock ? '1' : '0';
                row.querySelector('[data-line-sku]').textContent = data.sku || '-';
                row.querySelector('[data-uom-id]').value = data.unit_id || row.querySelector('[data-uom-id]').value || '';
                row.querySelector('[data-uom-text]').textContent = data.unit_text || '-';
                this.setBatchOptions(row, data.batches || []);
                const selectedBatchOption = row.querySelector('[data-batch-select]')?.selectedOptions[0];
                const selectedBatchAvailable = selectedBatchOption?.dataset.availableQty;
                this.setAvailableQty(row, selectedBatchAvailable ?? data.available_qty ?? 0);
                this.applyTracking(row);
                this.recalculateRemaining(row);
            },
            setBatchOptions(row, batches) {
                const select = row.querySelector('[data-batch-select]');
                const currentBatch = row.querySelector('[data-batch-no]')?.value || row.dataset.currentBatch || '';
                select.innerHTML = '<option value="">Select batch</option>';
                batches.forEach((batch) => {
                    if (Number(batch.available_qty || 0) <= 0) return;
                    const option = document.createElement('option');
                    option.value = batch.batch_no || '';
                    option.textContent = [
                        `Batch: ${batch.label || batch.batch_no}`,
                        `Available: ${this.formatQty(batch.available_qty)} ${row.querySelector('[data-uom-text]')?.textContent?.trim() || ''}`.trim(),
                        `Expiry: ${batch.expiry_text || '-'}`,
                    ].filter(Boolean).join(' | ');
                    option.dataset.expiryDate = batch.expiry_date || '';
                    option.dataset.availableQty = batch.available_qty || 0;
                    select.appendChild(option);
                });
                if (currentBatch && Array.from(select.options).some((option) => option.value === currentBatch)) {
                    select.value = currentBatch;
                    const selectedOption = select.selectedOptions[0];
                    row.querySelector('[data-expiry-date]').value = selectedOption?.dataset.expiryDate || row.dataset.currentExpiry || '';
                    this.setAvailableQty(row, selectedOption?.dataset.availableQty || 0);
                } else if (currentBatch) {
                    row.dataset.currentBatch = '';
                    row.dataset.currentExpiry = '';
                    row.querySelector('[data-batch-no]').value = '';
                    row.querySelector('[data-expiry-date]').value = '';
                }
            },
            applyTracking(row) {
                const needsBatch = row.dataset.batchTracked === '1';
                const batchSelect = row.querySelector('[data-batch-select]');
                batchSelect.classList.toggle('hidden', !needsBatch);
                batchSelect.disabled = !needsBatch;
                row.querySelector('[data-batch-na]').classList.toggle('hidden', needsBatch);
                row.querySelector('[data-expiry-date]').readOnly = true;
                if (!needsBatch) {
                    row.querySelector('[data-batch-no]').value = '';
                    row.querySelector('[data-expiry-date]').value = '';
                }
            },
            recalculateRemaining(row) {
                const hasItem = Boolean(row.querySelector('input[type=hidden][name$="[item_id]"]')?.value);
                const available = Number(row.querySelector('[data-available-qty]')?.dataset.availableValue || 0);
                const transfer = Number(row.querySelector('[data-transfer-qty]')?.value || 0);
                const remaining = available - transfer;
                const badge = row.querySelector('[data-remaining-qty]');
                const transferInput = row.querySelector('[data-transfer-qty]');
                const exceedsAvailable = transfer > available;
                const invalidQty = hasItem && (transfer <= 0 || (row.dataset.allowNegative !== '1' && exceedsAvailable));
                const message = !hasItem || transfer > 0
                    ? (row.dataset.allowNegative !== '1' && exceedsAvailable ? 'Qty exceeds available stock.' : '')
                    : 'Qty must be greater than zero.';
                const validation = row.querySelector('[data-line-validation]');

                transferInput.classList.toggle('border-red-300', invalidQty);
                transferInput.classList.toggle('bg-red-50', invalidQty);
                transferInput.setCustomValidity(invalidQty ? message : '');
                transferInput.title = invalidQty ? message : '';
                if (validation) {
                    validation.textContent = invalidQty ? message : '';
                    validation.classList.toggle('hidden', !invalidQty);
                }
                if (row.dataset.allowNegative === '1') transferInput.removeAttribute('max');
                else transferInput.max = available.toFixed(2);

                badge.textContent = this.formatQty(remaining);
                badge.className = 'inline-flex min-w-24 justify-end rounded-full px-2.5 py-1 text-xs font-black ring-1 ' + (
                    remaining < 0 ? 'bg-red-50 text-red-700 ring-red-100' : (remaining === 0 ? 'bg-orange-50 text-orange-700 ring-orange-100' : 'bg-emerald-50 text-emerald-700 ring-emerald-100')
                );
                this.refreshFormState();
            },
            prepareForSubmit() {
                this.form().querySelectorAll('[data-line]').forEach((row) => {
                    const hasItem = Boolean(row.querySelector('input[type=hidden][name$="[item_id]"]')?.value);
                    if (hasItem) return;

                    row.querySelectorAll('[name]').forEach((field) => {
                        field.dataset.originalName = field.name;
                        field.removeAttribute('name');
                    });
                });

                setTimeout(() => {
                    this.form().querySelectorAll('[data-original-name]').forEach((field) => {
                        field.name = field.dataset.originalName;
                        delete field.dataset.originalName;
                    });
                }, 0);
            },
            fetchItemInfo(row) {
                const warehouseId = this.form().querySelector('[data-from-warehouse]')?.value || '';
                const itemId = row.querySelector('input[type=hidden][name$="[item_id]"]')?.value || '';
                const batchNo = row.querySelector('[data-batch-no]')?.value || '';
                const expiryDate = row.querySelector('[data-expiry-date]')?.value || '';
                const requestId = String(Date.now() + Math.random());
                row.dataset.itemInfoRequest = requestId;
                document.querySelector('[data-warehouse-message]')?.classList.toggle('hidden', Boolean(warehouseId));
                if (!warehouseId || !itemId) return;

                const url = new URL(@js(route('warehouse-transfers.item-info')), window.location.origin);
                url.searchParams.set('warehouse_id', warehouseId);
                url.searchParams.set('item_id', itemId);
                if (batchNo) url.searchParams.set('batch_no', batchNo);
                if (expiryDate) url.searchParams.set('expiry_date', expiryDate);

                fetch(url, { headers: { Accept: 'application/json' } })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        const currentWarehouseId = this.form().querySelector('[data-from-warehouse]')?.value || '';
                        const currentItemId = row.querySelector('input[type=hidden][name$="[item_id]"]')?.value || '';
                        if (data && row.isConnected && row.dataset.itemInfoRequest === requestId && currentWarehouseId === warehouseId && currentItemId === itemId) {
                            this.applyItemInfo(row, data);
                        }
                    });
            },
            warehouseStats(target, warehouseId) {
                const prefix = target === 'source' ? 'source' : 'destination';
                if (!warehouseId) {
                    document.querySelector(`[data-${prefix}-items]`).textContent = '0';
                    document.querySelector(`[data-${prefix}-qty]`).textContent = '0.00';
                    return;
                }
                const url = new URL(@js(route('warehouse-transfers.warehouse-stats')), window.location.origin);
                url.searchParams.set('warehouse_id', warehouseId);
                fetch(url, { headers: { Accept: 'application/json' } })
                    .then((response) => response.ok ? response.json() : null)
                    .then((data) => {
                        if (!data) return;
                        document.querySelector(`[data-${prefix}-items]`).textContent = data.items_count || 0;
                        document.querySelector(`[data-${prefix}-qty]`).textContent = data.total_qty_text || '0.00';
                    });
            },
            refreshWarehouses(resetInvalid = false) {
                const form = this.form();
                const branch = form.querySelector('[data-branch]');
                const company = form.querySelector('[data-company]');
                const branchId = branch.value || '';
                const selectedBranch = branch.selectedOptions[0];
                if (selectedBranch?.dataset.companyId) company.value = selectedBranch.dataset.companyId;

                form.querySelectorAll('[data-from-warehouse], [data-to-warehouse]').forEach((select) => {
                    let selectedVisible = select.value === '';
                    Array.from(select.options).forEach((option) => {
                        if (!option.value) return;
                        const visible = !branchId || option.dataset.branchId === branchId;
                        option.hidden = !visible;
                        option.disabled = !visible;
                        if (visible && option.selected) selectedVisible = true;
                    });
                    if (resetInvalid && !selectedVisible) select.value = '';
                });
            },
            clearAllLines() {
                const lines = this.form().querySelector('[data-lines]');
                const template = document.querySelector('[data-line-template]');
                lines.innerHTML = template.innerHTML.replaceAll('__INDEX__', '0');
                window.Alpine?.initTree(lines.firstElementChild);
                this.applyTracking(lines.firstElementChild);
                this.recalculateRemaining(lines.firstElementChild);
            },
            renumberLines() {
                this.form().querySelectorAll('[data-line]').forEach((row, index) => {
                    row.querySelector('[data-line-number]').textContent = index + 1;
                });
            },
            refreshFormState() {
                const form = this.form();
                if (!form) return;
                const source = form.querySelector('[data-from-warehouse]')?.value || '';
                const destination = form.querySelector('[data-to-warehouse]')?.value || '';
                const sameWarehouse = source && destination && source === destination;
                form.querySelector('[data-warehouse-error]').classList.toggle('hidden', !sameWarehouse);

                const rows = Array.from(form.querySelectorAll('[data-line]'));
                const filledRows = rows.filter((row) => row.querySelector('input[type=hidden][name$="[item_id]"]')?.value);
                const totalQty = filledRows.reduce((sum, row) => sum + Number(row.querySelector('[data-transfer-qty]')?.value || 0), 0);
                const uoms = [...new Set(filledRows.map((row) => row.querySelector('[data-uom-text]')?.textContent?.trim()).filter((uom) => uom && uom !== '-'))];
                const invalidLine = filledRows.some((row) => {
                    const available = Number(row.querySelector('[data-available-qty]')?.dataset.availableValue || 0);
                    const transfer = Number(row.querySelector('[data-transfer-qty]')?.value || 0);
                    const batchRequired = row.dataset.batchTracked === '1' && !row.querySelector('[data-batch-no]')?.value;
                    return batchRequired || transfer <= 0 || (row.dataset.allowNegative !== '1' && transfer > available);
                });

                form.querySelector('[data-total-lines]').textContent = String(filledRows.length);
                form.querySelector('[data-total-quantity]').textContent = `${this.formatQty(totalQty)}${uoms.length === 1 ? ` ${uoms[0]}` : (uoms.length > 1 ? ' Mixed UOM' : '')}`;
                form.querySelector('[data-post-transfer]').disabled = !source || !destination || sameWarehouse || filledRows.length === 0 || invalidLine;
            },
        };

        document.addEventListener('DOMContentLoaded', () => {
            const manager = window.linvyWarehouseTransfer;
            const form = manager.form();
            const lines = form.querySelector('[data-lines]');
            const template = document.querySelector('[data-line-template]');
            let nextIndex = lines.querySelectorAll('[data-line]').length;

            form.querySelector('[data-branch]').addEventListener('change', () => {
                const previousSource = form.querySelector('[data-from-warehouse]').value;
                manager.refreshWarehouses(true);
                const currentSource = form.querySelector('[data-from-warehouse]').value;
                if (previousSource !== currentSource) {
                    manager.clearAllLines();
                    nextIndex = 1;
                }
                manager.warehouseStats('source', form.querySelector('[data-from-warehouse]').value);
                manager.warehouseStats('destination', form.querySelector('[data-to-warehouse]').value);
                manager.refreshFormState();
            });
            form.querySelector('[data-from-warehouse]').addEventListener('change', (event) => {
                manager.clearAllLines();
                nextIndex = 1;
                manager.warehouseStats('source', event.target.value);
                manager.refreshFormState();
            });
            form.querySelector('[data-to-warehouse]').addEventListener('change', (event) => {
                manager.warehouseStats('destination', event.target.value);
                manager.refreshFormState();
            });

            form.addEventListener('click', (event) => {
                if (event.target.matches('[data-add-line]')) {
                    lines.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex));
                    nextIndex += 1;
                    window.Alpine?.initTree(lines.lastElementChild);
                    manager.applyTracking(lines.lastElementChild);
                    manager.renumberLines();
                    lines.lastElementChild.querySelector('[data-searchable-input]')?.focus();
                    manager.refreshFormState();
                }
                if (event.target.matches('[data-remove-line]') && lines.querySelectorAll('[data-line]').length > 1) {
                    event.target.closest('[data-line]').remove();
                    manager.renumberLines();
                    manager.refreshFormState();
                }
            });

            form.addEventListener('input', (event) => {
                const row = event.target.closest('[data-line]');
                if (row && event.target.matches('[data-transfer-qty]')) manager.recalculateRemaining(row);
            });
            form.addEventListener('change', (event) => {
                const row = event.target.closest('[data-line]');
                if (row && event.target.matches('[data-batch-select]')) {
                    const option = event.target.selectedOptions[0];
                    row.querySelector('[data-batch-no]').value = event.target.value || '';
                    row.querySelector('[data-expiry-date]').value = option?.dataset.expiryDate || '';
                    manager.setAvailableQty(row, option?.dataset.availableQty || 0);
                    manager.recalculateRemaining(row);
                }
            });
            form.addEventListener('linvy-searchable-cleared', (event) => {
                const row = event.target.closest('[data-line]');
                if (row && event.detail?.name?.endsWith('[item_id]')) manager.clearLineItem(row);
            });
            form.addEventListener('submit', () => manager.prepareForSubmit());

            manager.refreshWarehouses(false);
            manager.warehouseStats('source', form.querySelector('[data-from-warehouse]').value);
            manager.warehouseStats('destination', form.querySelector('[data-to-warehouse]').value);
            lines.querySelectorAll('[data-line]').forEach((row) => {
                manager.applyTracking(row);
                manager.fetchItemInfo(row);
                manager.recalculateRemaining(row);
            });
            manager.renumberLines();
            manager.refreshFormState();
        });
    </script>
</x-app-layout>
