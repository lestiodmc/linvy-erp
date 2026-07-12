@props(['span' => 1])
<div {{ $attributes->class(['enterprise-filter-field', 'enterprise-filter-span-2' => (int)$span === 2, 'enterprise-filter-span-full' => $span === 'full']) }}>{{ $slot }}</div>
