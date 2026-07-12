<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">{{ $record->number }}</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Purchase Request detail</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('purchase-requests.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Back</a>

                @if($record->status === 'draft')
                    <a href="{{ route('purchase-requests.edit', $record) }}" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100">Edit</a>
                    <form method="POST" action="{{ route('purchase-requests.submit', $record) }}">
                        @csrf
                        <button type="submit" class="button-primary">Submit</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-requests.cancel', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                    </form>
                @endif

                @if($record->status === 'submitted')
                    <form method="POST" action="{{ route('purchase-requests.approve', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-requests.reject', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-requests.cancel', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                    </form>
                @endif

                @if($record->status === 'approved')
                    <a href="{{ route('purchase-orders.create-from-pr', $record) }}" class="button-primary">Convert to PO</a>
                    <form method="POST" action="{{ route('purchase-requests.close', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Close</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-requests.cancel', $record) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Cancel</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                <h3 class="text-base font-black text-slate-950">Header</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="font-bold text-slate-500">Status</dt><dd><x-status-badge :status="$record->status" /></dd></div>
                    <div><dt class="font-bold text-slate-500">Request Date</dt><dd class="font-semibold text-slate-900">{{ $record->request_date?->format('Y-m-d') }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Requested By</dt><dd class="font-semibold text-slate-900">{{ $record->requester?->name }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Department</dt><dd class="font-semibold text-slate-900">{{ $record->department ?: '-' }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Notes</dt><dd class="text-slate-700">{{ $record->notes ?: '-' }}</dd></div>
                </dl>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-base font-black text-slate-950">Item Lines</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Item</th><th class="px-4 py-3 text-left">Qty</th><th class="px-4 py-3 text-left">Converted</th><th class="px-4 py-3 text-left">Required</th><th class="px-4 py-3 text-left">Notes</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($record->lines as $line)
                                <tr>
                                    <td class="px-4 py-3"><div class="font-bold text-slate-900">{{ $line->item?->name }}</div><div class="text-xs text-slate-500">{{ $line->description }}</div></td>
                                    <td class="px-4 py-3 text-slate-700">{{ number_format($line->quantity, 4) }} {{ $line->unit?->code }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ number_format($line->converted_quantity, 4) }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $line->required_date?->format('Y-m-d') ?: '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $line->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
