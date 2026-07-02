<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $record ? 'Edit '.$title : 'New '.$title }}</h2>
    </x-slot>

    @php
        $accountingEnabled = \App\Support\ModuleManager::enabled('accounting');
        $groups = [
            'general' => ['label' => 'General', 'fields' => ['sku', 'name', 'type', 'item_category_id', 'unit_of_measure_id', 'is_stock_item', 'is_active', 'notes']],
            'costing' => ['label' => 'Costing', 'fields' => ['standard_cost', 'cost_method']],
        ];

        if ($accountingEnabled) {
            $groups['accounting'] = ['label' => 'Accounting', 'fields' => ['use_category_default_accounts', 'inventory_account_id', 'cogs_account_id', 'sales_account_id', 'purchase_account_id', 'wip_account_id', 'adjustment_account_id', 'waste_account_id']];
        }

        $accountFields = ['inventory_account_id', 'cogs_account_id', 'sales_account_id', 'purchase_account_id', 'wip_account_id', 'adjustment_account_id', 'waste_account_id'];
        $useDefault = old('use_category_default_accounts', $record ? $record->use_category_default_accounts : true);
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $action }}" x-data="{ tab: 'general', useDefaultAccounts: @js((bool) $useDefault) }" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                <div class="border-b border-gray-200 px-6 pt-5">
                    <nav class="-mb-px flex gap-6" aria-label="Tabs">
                        @foreach($groups as $key => $group)
                            <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'border-emerald-700 text-emerald-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'" class="whitespace-nowrap border-b-2 px-1 pb-4 text-sm font-semibold">
                                {{ $group['label'] }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                <div class="p-6">
                    @foreach($groups as $key => $group)
                        <section x-show="tab === '{{ $key }}'" x-cloak>
                            <div class="grid gap-5 md:grid-cols-2">
                                @foreach($group['fields'] as $name)
                                    @php
                                        $field = $fields[$name];
                                        $type = $field['type'] ?? 'text';
                                        $value = old($name, $record ? data_get($record, $name) : ($field['default'] ?? null));
                                        $isAccountField = in_array($name, $accountFields, true);
                                    @endphp

                                    <div class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }}" @if($isAccountField) x-show="!useDefaultAccounts" @endif>
                                        @if($type === 'checkbox')
                                            <label class="mt-7 flex items-center gap-3 text-sm font-medium text-gray-800">
                                                <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value) @if($name === 'use_category_default_accounts') x-model="useDefaultAccounts" @endif class="rounded border-gray-300 text-emerald-700 shadow-sm focus:ring-emerald-600">
                                                {{ $field['label'] }}
                                            </label>
                                        @else
                                            <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] }}</label>

                                            @if($type === 'select')
                                                <select id="{{ $name }}" name="{{ $name }}" @if($isAccountField) :disabled="useDefaultAccounts" @endif class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                                    @if($field['nullable'] ?? false)
                                                        <option value="">-</option>
                                                    @endif
                                                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($type === 'textarea')
                                                <textarea id="{{ $name }}" name="{{ $name }}" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">{{ $value }}</textarea>
                                            @else
                                                <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" step="{{ $field['step'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                            @endif
                                        @endif

                                        @error($name)
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <a href="{{ route($route.'.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
