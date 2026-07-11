@props(['value'])

<label {{ $attributes->merge(['class' => 'enterprise-form-label']) }}>
    {{ $value ?? $slot }}
</label>
