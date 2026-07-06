@props([
    'message' => 'No records found.',
    'colspan' => 1,
    'as' => 'tr',
])

@if($as === 'tr')
    <tr>
        <td colspan="{{ $colspan }}" {{ $attributes->class('px-5 py-12 text-center text-sm font-semibold text-slate-500') }}>
            {{ $slot->isEmpty() ? $message : $slot }}
        </td>
    </tr>
@else
    <div {{ $attributes->class('px-5 py-12 text-center text-sm font-semibold text-slate-500') }}>
        {{ $slot->isEmpty() ? $message : $slot }}
    </div>
@endif
