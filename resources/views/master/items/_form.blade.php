<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record ? 'Edit '.$title : 'New '.$title }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Manage ERP item setup, inventory behavior, purchasing, and sales defaults.</p>
        </div>
    </x-slot>

    @php
        $value = fn (string $name, mixed $default = null) => old($name, $record ? data_get($record, $name) : ($fields[$name]['default'] ?? $default));
        $sections = [
            'General Information' => ['sku', 'name', 'item_category_id', 'brand_id', 'item_type'],
            'Unit of Measure' => ['base_unit_id', 'purchase_unit_id', 'sales_unit_id'],
            'Warehouse Default' => ['default_warehouse_type_id'],
            'Inventory' => ['track_inventory', 'allow_negative_stock', 'is_batch_tracked', 'is_serial_tracked', 'has_expiry_date'],
            'Cost and Price' => ['purchase_price', 'sales_price', 'cost_method', 'standard_cost'],
            'Status' => ['is_active'],
        ];
    @endphp

    <div class="mx-auto max-w-6xl">
        <form
            method="POST"
            action="{{ $action }}"
            x-data="{
                trackInventory: @js((bool) $value('track_inventory', true)),
                allowNegativeStock: @js((bool) $value('allow_negative_stock', false)),
                batchTracked: @js((bool) $value('is_batch_tracked', false)),
                serialTracked: @js((bool) $value('is_serial_tracked', false)),
                hasExpiryDate: @js((bool) $value('has_expiry_date', false)),
                itemType: @js((string) $value('item_type', 'INVENTORY')),
                applyCategory(event) {
                    const option = event.target.selectedOptions[0];

                    if (!option) {
                        return;
                    }

                    const itemType = option.dataset.itemType || '';
                    const warehouseTypeId = option.dataset.warehouseTypeId || '';

                    if (itemType) {
                        this.itemType = itemType;
                    }

                    this.$refs.defaultWarehouseType.value = warehouseTypeId;
                    this.syncInventoryTracking('item_type');
                },
                syncInventoryTracking(changed) {
                    if (this.itemType !== 'INVENTORY') {
                        this.trackInventory = false;
                    }

                    if (!this.trackInventory) {
                        this.allowNegativeStock = false;
                        this.batchTracked = false;
                        this.serialTracked = false;
                        this.hasExpiryDate = false;
                        return;
                    }

                    if (changed === 'batch' && this.batchTracked) {
                        this.trackInventory = true;
                        this.serialTracked = false;
                    }

                    if (changed === 'serial' && this.serialTracked) {
                        this.trackInventory = true;
                        this.batchTracked = false;
                        this.hasExpiryDate = false;
                    }

                    if (changed === 'expiry' && this.hasExpiryDate) {
                        this.trackInventory = true;
                        this.batchTracked = true;
                        this.serialTracked = false;
                    }
                }
            }"
            class="enterprise-form overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-black text-slate-950">Item Information</h3>
                <p class="mt-1 text-sm text-slate-500">Fill the item master data used by inventory, purchase, and sales modules.</p>
            </div>

            <div class="space-y-8 p-6">
                @foreach($sections as $sectionTitle => $sectionFields)
                    <section>
                        <div class="mb-4">
                            <h4 class="text-sm font-black uppercase tracking-wide text-slate-700">{{ $sectionTitle }}</h4>
                            <div class="mt-2 h-px bg-slate-100"></div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            @foreach($sectionFields as $name)
                                @php
                                    $field = $fields[$name];
                                    $type = $field['type'] ?? 'text';
                                    $currentValue = $value($name);
                                @endphp

                                <div class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }}">
                                    @if($type === 'checkbox')
                                        <label class="mt-7 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800">
                                            <input
                                                type="checkbox"
                                                name="{{ $name }}"
                                                value="1"
                                                @checked((bool) $currentValue)
                                                @if($name === 'track_inventory') x-model="trackInventory" @change="syncInventoryTracking('track')" @endif
                                                @if($name === 'allow_negative_stock') x-model="allowNegativeStock" :disabled="!trackInventory" @endif
                                                @if($name === 'is_batch_tracked') x-model="batchTracked" :disabled="!trackInventory" @change="syncInventoryTracking('batch')" @endif
                                                @if($name === 'is_serial_tracked') x-model="serialTracked" :disabled="!trackInventory" @change="syncInventoryTracking('serial')" @endif
                                                @if($name === 'has_expiry_date') x-model="hasExpiryDate" :disabled="!trackInventory" @change="syncInventoryTracking('expiry')" @endif
                                                class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-600"
                                            >
                                            {{ $field['label'] }}
                                        </label>
                                    @else
                                        <label for="{{ $name }}" class="block text-sm font-bold text-slate-700">{{ $field['label'] }}</label>

                                        @if($type === 'select')
                                            <select
                                                id="{{ $name }}"
                                                name="{{ $name }}"
                                                @if($name === 'item_category_id') @change="applyCategory($event)" @endif
                                                @if($name === 'item_type') x-model="itemType" @change="syncInventoryTracking('item_type')" @endif
                                                @if($name === 'default_warehouse_type_id') x-ref="defaultWarehouseType" @endif
                                                class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                                            >
                                                @if($field['nullable'] ?? false)
                                                    <option value="">-</option>
                                                @endif

                                                @if($name === 'item_category_id')
                                                    @foreach($categoryRecords as $category)
                                                        <option
                                                            value="{{ $category->id }}"
                                                            data-item-type="{{ $category->item_type }}"
                                                            data-warehouse-type-id="{{ $category->default_warehouse_type_id }}"
                                                            @selected((string) $currentValue === (string) $category->id)
                                                        >{{ $category->name }}</option>
                                                    @endforeach
                                                @else
                                                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected((string) $currentValue === (string) $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        @elseif($type === 'textarea')
                                            <textarea id="{{ $name }}" name="{{ $name }}" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">{{ $currentValue }}</textarea>
                                        @else
                                            <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $currentValue }}" step="{{ $field['step'] ?? '' }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                        @endif
                                    @endif

                                    @error($name)
                                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <a href="{{ route($route.'.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="button-primary">Save</button>
            </div>
        </form>
    </div>
</x-app-layout>
