<button {{ $attributes->merge(['type' => 'submit', 'class' => 'theme-primary theme-focus inline-flex items-center rounded-md border border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest transition duration-150 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
