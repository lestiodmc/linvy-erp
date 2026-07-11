@props([
    'message' => 'No records found.',
    'description' => null,
    'colspan' => 1,
    'as' => 'tr',
])

@if($as === 'tr')
    <tr>
        <td colspan="{{ $colspan }}" {{ $attributes->class('theme-muted px-5 py-5 text-center text-sm font-semibold') }}>
            @if($slot->isEmpty())
                <span class="mx-auto mb-1 grid h-7 w-7 place-items-center rounded-full theme-primary-soft" aria-hidden="true"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h10" /></svg></span>
                <span class="block theme-text">{{ $message }}</span>
                @if($description)<span class="mt-0.5 block text-xs font-normal theme-muted">{{ $description }}</span>@endif
            @else{{ $slot }}@endif
        </td>
    </tr>
@else
    <div {{ $attributes->class('theme-muted px-5 py-5 text-center text-sm font-semibold') }}>
        @if($slot->isEmpty())<span class="block theme-text">{{ $message }}</span>@if($description)<span class="mt-0.5 block text-xs font-normal theme-muted">{{ $description }}</span>@endif @else{{ $slot }}@endif
    </div>
@endif
