<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">Dashboard</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Operational overview for Linvy ERP</p>
        </div>
    </x-slot>

    @php
        $statusClass = function (?string $status): string {
            return match ($status) {
                'draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
                'approved' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'posted', 'received', 'delivered', 'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'cancelled' => 'bg-red-50 text-red-700 ring-red-100',
                default => 'bg-amber-50 text-amber-700 ring-amber-100',
            };
        };

        $statStyles = [
            'Total Items' => ['icon' => 'M4 7h16M4 12h16M4 17h10', 'tone' => 'bg-blue-50 text-blue-700 ring-blue-100'],
            'Total Warehouses' => ['icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
            'Stock Low' => ['icon' => 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
            'Purchase This Month' => ['icon' => 'M9 12h6m-6 4h6M7 4h10l2 4v12H5V8l2-4Z', 'tone' => 'bg-cyan-50 text-cyan-700 ring-cyan-100'],
            'Sales This Month' => ['icon' => 'M4 19V5m0 14h16M8 16l3-3 2 2 5-7', 'tone' => 'bg-indigo-50 text-indigo-700 ring-indigo-100'],
            'Pending Receivings' => ['icon' => 'M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'tone' => 'bg-rose-50 text-rose-700 ring-rose-100'],
        ];
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-6 p-6 lg:grid-cols-[1.6fr_1fr] lg:p-8">
                <div>
                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-100">{{ \App\Support\ModuleManager::packageName() }} Package</span>
                    <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950">Welcome back, {{ Auth::user()->name }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Monitor purchasing, inventory, production, and sales from one clean workspace. Stock remains driven by stock movements and warehouse balances.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($quickActions as $action)
                        @if(Auth::user()?->canAccessModule($action['module']) && \App\Support\ModuleManager::enabled($action['module']))
                            <a href="{{ route($action['route']) }}" class="group flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800">
                                <span>{{ $action['label'] }}</span>
                                <span class="grid h-8 w-8 place-items-center rounded-lg bg-white text-slate-500 shadow-sm group-hover:text-emerald-700">+</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($stats as $label => $value)
                @php $style = $statStyles[$label]; @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-slate-500">{{ $label }}</p>
                            <p class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ number_format($value) }}</p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-xl ring-1 {{ $style['tone'] }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $style['icon'] }}" />
                            </svg>
                        </span>
                    </div>
                    <span class="mt-4 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Live summary</span>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 2xl:grid-cols-3">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm 2xl:col-span-2">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-black text-slate-950">Recent Documents</h3>
                    <p class="mt-1 text-sm text-slate-500">Latest operational documents across enabled workflows.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Number</th>
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Partner</th>
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Total</th>
                                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentDocuments as $document)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4"><span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">{{ $document['type'] }}</span></td>
                                    <td class="px-5 py-4 font-bold text-slate-800">{{ $document['number'] }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $document['partner'] ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $document['date'] ? \Illuminate\Support\Carbon::parse($document['date'])->format('d M Y') : '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black capitalize ring-1 {{ $statusClass($document['status']) }}">{{ str($document['status'])->replace('_', ' ') }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold text-slate-700">{{ $document['total'] !== null ? number_format((float) $document['total'], 2) : '-' }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ $document['route'] }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-slate-500">No recent documents yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-black text-slate-950">Stock Low Alert</h3>
                    <p class="mt-1 text-sm text-slate-500">Items with available stock at or below minimum.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Item</th>
                                <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">Warehouse</th>
                                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Available</th>
                                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Min Stock</th>
                                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($lowStocks as $balance)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 font-bold text-slate-800">{{ $balance->item?->name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $balance->warehouse?->name }}</td>
                                    <td class="px-5 py-4 text-right font-black text-amber-700">{{ number_format($balance->quantity_on_hand, 4) }}</td>
                                    <td class="px-5 py-4 text-right text-slate-500">{{ number_format(0, 4) }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('stock-balances.show', $balance) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Open</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-500">No low stock alerts.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
