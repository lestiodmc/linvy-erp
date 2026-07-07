@props([
    'warehouses',
    'name' => 'warehouse_id',
    'label' => 'Warehouse',
    'value' => '',
    'allLabel' => 'All warehouses',
    'branchSelector' => '[name="branch_id"]',
])

@php
    $warehouseRecords = collect($warehouses);
@endphp

<div>
    <label for="{{ $name }}" class="sr-only">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        data-warehouse-filter
        data-branch-selector="{{ $branchSelector }}"
        class="h-10 w-full rounded-lg border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"
    >
        <option value="">{{ $allLabel }}</option>
        @foreach($warehouseRecords as $warehouse)
            @php
                $optionLabel = trim(($warehouse->branch?->name ? $warehouse->branch->name.' - ' : '').($warehouse->code ? $warehouse->code.' - ' : '').$warehouse->name);
            @endphp
            <option
                value="{{ $warehouse->id }}"
                data-branch-id="{{ $warehouse->branch_id }}"
                @selected((string) $value === (string) $warehouse->id)
            >
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-warehouse-filter]').forEach((warehouseSelect) => {
                const form = warehouseSelect.closest('form') || document;
                const branchSelector = warehouseSelect.dataset.branchSelector || '[name="branch_id"]';
                const branchSelect = form.querySelector(branchSelector) || document.querySelector(branchSelector);

                if (!branchSelect) {
                    return;
                }

                const refreshWarehouses = () => {
                    const selectedBranchId = branchSelect.value;
                    let selectedStillVisible = warehouseSelect.value === '';

                    Array.from(warehouseSelect.options).forEach((option) => {
                        if (!option.value) {
                            option.hidden = false;
                            option.disabled = false;
                            return;
                        }

                        const optionBranchId = option.dataset.branchId || '';
                        const visible = !selectedBranchId || optionBranchId === selectedBranchId;

                        option.hidden = !visible;
                        option.disabled = !visible;

                        if (visible && option.selected) {
                            selectedStillVisible = true;
                        }
                    });

                    if (!selectedStillVisible) {
                        warehouseSelect.value = '';
                    }
                };

                branchSelect.addEventListener('change', refreshWarehouses);
                refreshWarehouses();
            });
        });
    </script>
@endonce
