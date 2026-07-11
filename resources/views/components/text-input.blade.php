@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'theme-surface rounded-md border shadow-sm']) }}>
