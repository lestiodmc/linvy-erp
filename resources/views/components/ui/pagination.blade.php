@props(['records'])

@if($records->hasPages())
    <div {{ $attributes->class('mt-4') }}>
        {{ $records->links() }}
    </div>
@endif
