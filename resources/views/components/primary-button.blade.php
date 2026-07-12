<button {{ $attributes->merge(['type' => 'submit', 'class' => 'button-primary text-xs uppercase tracking-widest']) }}>
    {{ $slot }}
</button>
