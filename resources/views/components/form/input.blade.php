@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'readonly' => false, 'disabled' => false])

@php($id = $attributes->get('id', $name))
<div>
    <label for="{{ $id }}" class="enterprise-form-label">{{ $label }} @if($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) @readonly($readonly) @disabled($disabled) aria-required="{{ $required ? 'true' : 'false' }}" @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes->except('id')->class('enterprise-form-control') }}>
    @if($help)<p class="enterprise-form-help">{{ $help }}</p>@endif
    @error($name)<p id="{{ $id }}-error" class="enterprise-form-error">{{ $message }}</p>@enderror
</div>
