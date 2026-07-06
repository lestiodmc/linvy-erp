@props([
    'fromName' => 'date_from',
    'toName' => 'date_to',
    'from' => null,
    'to' => null,
])

<div>
    <label class="sr-only" for="{{ $fromName }}">Date From</label>
    <input
        id="{{ $fromName }}"
        type="date"
        name="{{ $fromName }}"
        value="{{ $from }}"
        aria-label="Date From"
        class="h-10 w-full rounded-lg border-slate-200 px-3 text-sm focus:border-emerald-500 focus:ring-emerald-500"
    >
</div>

<div>
    <label class="sr-only" for="{{ $toName }}">Date To</label>
    <input
        id="{{ $toName }}"
        type="date"
        name="{{ $toName }}"
        value="{{ $to }}"
        aria-label="Date To"
        class="h-10 w-full rounded-lg border-slate-200 px-3 text-sm focus:border-emerald-500 focus:ring-emerald-500"
    >
</div>
