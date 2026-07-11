@props(['title', 'subtitle' => null])

<section {{ $attributes->class('enterprise-form-section theme-card rounded-lg p-4') }}>
    <div class="mb-3">
        <h2 class="text-sm font-black theme-text">{{ $title }}</h2>
        @if($subtitle)<p class="mt-0.5 text-xs theme-muted">{{ $subtitle }}</p>@endif
    </div>
    {{ $slot }}
</section>
