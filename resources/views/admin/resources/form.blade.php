<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $record ? 'Edit '.$title : 'New '.$title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $action }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach($fields as $name => $field)
                        @php
                            $type = $field['type'] ?? 'text';
                            $value = old($name, $record ? data_get($record, $name) : ($field['default'] ?? null));
                        @endphp

                        <div class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }}">
                            @if($type === 'checkbox')
                                <label class="mt-7 flex items-center gap-3 text-sm font-medium text-gray-800">
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value) class="rounded border-gray-300 text-emerald-700 shadow-sm focus:ring-emerald-600">
                                    {{ $field['label'] }}
                                </label>
                            @else
                                <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] }}</label>

                                @if($type === 'select')
                                    <select id="{{ $name }}" name="{{ $name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                        @if($field['nullable'] ?? false)
                                            <option value="">-</option>
                                        @endif
                                        @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                @elseif($type === 'textarea')
                                    <textarea id="{{ $name }}" name="{{ $name }}" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">{{ $value }}</textarea>
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

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                    <a href="{{ route($route.'.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
