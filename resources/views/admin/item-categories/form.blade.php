<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $record ? 'Edit '.$title : 'New '.$title }}</h2>
    </x-slot>

    @php
        $accountingEnabled = \App\Support\ModuleManager::enabled('accounting');
        $groups = [
            'general' => ['label' => 'General', 'fields' => ['code', 'name', 'is_active']],
        ];

        if ($accountingEnabled) {
            $groups['accounting'] = ['label' => 'Accounting', 'fields' => ['default_inventory_account_id', 'default_cogs_account_id', 'default_sales_account_id', 'default_purchase_account_id', 'default_wip_account_id', 'default_adjustment_account_id', 'default_waste_account_id']];
        }
    @endphp

    <div>
        <div class="mx-auto max-w-5xl">
            <form method="POST" action="{{ $action }}" x-data="{ tab: 'general' }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                <div class="border-b border-slate-100 px-6 pt-5">
                    <div class="mb-5">
                        <h3 class="text-base font-black text-slate-950">Category Information</h3>
                        <p class="mt-1 text-sm text-slate-500">Manage item classification and optional accounting defaults.</p>
                    </div>
                    <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                        @foreach($groups as $key => $group)
                            <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'" class="whitespace-nowrap border-b-2 px-1 pb-4 text-sm font-bold">
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
                                    @endphp

                                    <div>
                                        @if($type === 'checkbox')
                                            <label class="mt-7 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800">
                                                <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value) class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-600">
                                                {{ $field['label'] }}
                                            </label>
                                        @else
                                            <label for="{{ $name }}" class="block text-sm font-bold text-slate-700">{{ $field['label'] }}</label>

                                            @if($type === 'select')
                                                <select id="{{ $name }}" name="{{ $name }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                                    @if($field['nullable'] ?? false)
                                                        <option value="">-</option>
                                                    @endif
                                                    @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
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
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-emerald-900/10 hover:bg-emerald-700">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
