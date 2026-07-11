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
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $optionValue }}" @checked(in_array($optionValue, (array) $value, true)) class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-600">
                        {{ $optionLabel }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @else
        <label for="{{ $name }}" class="block text-sm font-bold text-slate-700">{{ $field['label'] }}</label>

        @if($type === 'select')
            <select id="{{ $name }}" name="{{ $name }}" class="enterprise-form-control mt-1 block w-full shadow-sm" @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
                @if($field['nullable'] ?? false)
                    <option value="">-</option>
                @endif
                @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif($type === 'textarea')
            <textarea id="{{ $name }}" name="{{ $name }}" rows="4" class="enterprise-form-control mt-1 block w-full shadow-sm" @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>{{ $value }}</textarea>
        @else
            <input id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" step="{{ $field['step'] ?? '' }}" class="enterprise-form-control mt-1 block w-full shadow-sm" @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
        @endif
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="enterprise-form-error">{{ $message }}</p>
    @enderror
</div>
