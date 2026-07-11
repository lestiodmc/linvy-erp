@props(['label', 'value' => '-', 'mono' => false])
<div><span class="enterprise-form-label">{{ $label }}</span><div {{ $attributes->class('enterprise-readonly '.($mono ? 'font-mono' : '')) }}>{{ $slot->isEmpty() ? $value : $slot }}</div></div>
