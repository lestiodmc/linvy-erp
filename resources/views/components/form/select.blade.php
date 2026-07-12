@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'placeholder' => 'Select'])

@php($id = $attributes->get('id', $name))
<div>
    <label for="{{ $id }}" class="enterprise-form-label">{{ $label }} @if($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required) aria-required="{{ $required ? 'true' : 'false' }}" @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes->except('id')->class('enterprise-form-control') }}>
        <option value="">{{ $placeholder }}</option>
        @if (trim($slot) !== '')
            {{ $slot }}
        @else
            @foreach($options as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>@endforeach
        @endif
    </select>
    @error($name)<p id="{{ $id }}-error" class="enterprise-form-error">{{ $message }}</p>@enderror
</div>
