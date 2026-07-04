<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="truncate text-xl font-black text-slate-950">Purchase Requests</h1>
                <p class="mt-0.5 text-sm font-medium text-slate-500">Request, submit, approve, and convert demand to PO.</p>
            </div>
            <a href="{{ route('purchase-requests.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">New PR</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-screen-2xl">
        @include('purchase.shared.flash')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-black text-slate-950">Purchase Request List</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Number</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Requested By</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Department</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $record)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-bold text-slate-900">{{ $record->number }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->request_date?->format('Y-m-d') }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->requester?->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $record->department ?: '-' }}</td>
                                <td class="px-5 py-4"><x-status-badge :status="$record->status" /></td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('purchase-requests.show', $record) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-sm font-semibold text-slate-500">No purchase requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $records->links() }}</div>
    </div>
</x-app-layout>
