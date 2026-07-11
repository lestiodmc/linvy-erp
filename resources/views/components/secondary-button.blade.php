<button {{ $attributes->merge(['type' => 'button', 'class' => 'theme-surface theme-focus inline-flex items-center rounded-md border px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition duration-150 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
