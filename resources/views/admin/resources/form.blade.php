<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $record ? 'Edit '.$title : 'New '.$title }}</h2>
    </x-slot>

    @php
        $fieldSections = collect($fields)->chunk(max(1, (int) ceil(max(count($fields), 1) / 2)));
        $sectionTitles = ['General Information', 'Detail Information'];
    @endphp

    <div>
        <div class="mx-auto max-w-4xl">
            <form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-semibold text-slate-900">{{ $record ? 'Edit Details' : 'Create Details' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Fill in the required information for this record.</p>
                </div>

                <div class="space-y-8 p-6">
                    @foreach($fieldSections as $sectionIndex => $sectionFields)
                        <section>
                            <div class="mb-4">
                                <h4 class="text-sm font-black uppercase tracking-wide text-slate-700">{{ $sectionTitles[$sectionIndex] ?? 'Status / Notes' }}</h4>
                                <div class="mt-2 h-px bg-slate-100"></div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                @foreach($sectionFields as $name => $field)
                                    @php
                                        $type = $field['type'] ?? 'text';
                                        $value = ($field['always_empty'] ?? false) ? old($name) : old($name, $record ? data_get($record, $name) : ($field['default'] ?? null));
                                    @endphp

                                    <div class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }}">
                            @if($type === 'checkbox')
                                <label class="mt-7 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800">
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value) class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-600">
                                    {{ $field['label'] }}
                                </label>
                            @elseif($type === 'multicheckbox')
                                <fieldset>
                                    <legend class="block text-sm font-bold text-slate-700">{{ $field['label'] }}</legend>
                                    <div class="mt-2 grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2">
                                        @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="{{ $name }}[]" value="{{ $optionValue }}" @checked(in_array($optionValue, (array) $value, true)) class="rounded border-gray-300 text-emerald-700 shadow-sm focus:ring-emerald-600">
                                                {{ $optionLabel }}
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
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
                                @elseif($type === 'textarea')
                                    <textarea id="{{ $name }}" name="{{ $name }}" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">{{ $value }}</textarea>
                                @else
                                    <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" step="{{ $field['step'] ?? '' }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
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

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                    <a href="{{ route($route.'.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="button-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
