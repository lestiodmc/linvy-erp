<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ __('Linvy ERP Dashboard') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($stats as $label => $value)
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h3 class="text-base font-semibold text-gray-900">Recent Stock Balances</h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Item</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Warehouse</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700">On Hand</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700">Average Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($balances as $balance)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-800">{{ $balance->item?->name }}</td>
                                        <td class="px-4 py-3 text-gray-800">{{ $balance->warehouse?->name }}</td>
                                        <td class="px-4 py-3 text-right text-gray-800">{{ number_format($balance->quantity_on_hand, 4) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-800">{{ number_format($balance->average_cost, 4) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No stock balances yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900">Inventory Principles</h3>
                    <ul class="mt-4 space-y-3 text-sm text-gray-700">
                        <li>Stock is stored by item and warehouse in stock balances.</li>
                        <li>Items do not store direct on hand quantity.</li>
                        <li>Every inventory transaction is designed to leave a stock movement audit trail.</li>
                        <li>Item categories hold default accounting accounts, with item-level overrides available.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
