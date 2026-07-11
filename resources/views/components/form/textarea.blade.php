@props(['name', 'label', 'value' => null, 'required' => false, 'help' => null, 'rows' => 3])

@php($id = $attributes->get('id', $name))
<div>
    <label for="{{ $id }}" class="enterprise-form-label">{{ $label }} @if($required)<span class="text-red-600" aria-hidden="true">*</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif {{ $attributes->except('id')->class('enterprise-form-control resize-y') }}>{{ old($name, $value) }}</textarea>
    @if($help)<p class="enterprise-form-help">{{ $help }}</p>@endif
    @error($name)<p id="{{ $id }}-error" class="enterprise-form-error">{{ $message }}</p>@enderror
</div>
