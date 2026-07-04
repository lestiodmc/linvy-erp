<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">Receivings</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Receive approved PO items into warehouse stock.</p>
            </div>
            <a href="{{ route('receivings.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">New Receiving</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Number</th><th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">PO</th><th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Supplier</th><th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Line Warehouses</th><th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Date</th><th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th><th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $record)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-bold text-slate-900">{{ $record->number }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->purchaseOrder?->number }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->supplier?->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->lines->pluck('warehouse.name')->filter()->unique()->values()->join(', ') ?: '-' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->received_date?->format('Y-m-d') }}</td>
                                <td class="px-5 py-4"><x-status-badge :status="$record->status" /></td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('receivings.show', $record) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-sm font-semibold text-slate-500">No receivings yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</x-app-layout>
