@props(['sticky' => true])
<div {{ $attributes->class('enterprise-action-bar theme-surface '.($sticky ? 'sticky bottom-0 z-20' : '')) }}>{{ $slot }}</div>
