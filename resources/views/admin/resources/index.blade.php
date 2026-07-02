<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-900">{{ $title }}</h2>
            @if(count($fields) > 0)
                <a href="{{ route($route.'.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800">New</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach($columns as $column)
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ str($column)->afterLast('.')->replace('_', ' ')->title() }}</th>
                                @endforeach
                                <th class="w-32 px-4 py-3 text-right font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($records as $record)
                                <tr class="hover:bg-gray-50">
                                    @foreach($columns as $column)
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-800">{{ data_get($record, $column) }}</td>
                                    @endforeach
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <a href="{{ route($route.'.show', $record) }}" class="font-medium text-emerald-700 hover:text-emerald-900">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) + 1 }}" class="px-4 py-8 text-center text-gray-500">No records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">{{ $records->links() }}</div>
        </div>
    </div>
</x-app-layout>
