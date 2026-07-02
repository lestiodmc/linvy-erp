<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-900">Account Mapping</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Item Category Default Accounts</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Category</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Inventory</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">COGS</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Sales</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Purchase</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">WIP</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Adjustment</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Waste</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($categories as $category)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $category->code }} - {{ $category->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $category->inventoryAccount?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $category->cogsAccount?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $category->salesAccount?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $category->purchaseAccount?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $category->wipAccount?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $category->adjustmentAccount?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $category->wasteAccount?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
