<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Production Formulas" subtitle="Versioned formulas for production and repacking.">
            <x-slot:action><a class="button-primary" href="{{ route('production-formulas.create') }}">New Formula</a></x-slot:action>
        </x-ui.page-header>
    </x-slot>
    <div class="mx-auto max-w-screen-2xl">
        <x-filter.panel :action="route('production-formulas.index')">
            <x-ui.search-input :value="$filters['keyword'] ?? ''" />
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies" all-label="All companies" />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches->pluck('name', 'id')" all-label="All applicability" />
            <x-ui.select-filter name="production_type" label="Type" :value="$filters['production_type'] ?? ''" :options="array_combine(\App\Models\ProductionBom::TYPES, \App\Models\ProductionBom::TYPES)" all-label="All types" />
            <x-ui.select-filter name="finished_item_id" label="Finished Item" :value="$filters['finished_item_id'] ?? ''" :options="$items->mapWithKeys(fn ($item) => [$item->id => trim($item->sku.' — '.$item->name)])" all-label="All finished items" />
            <x-ui.select-filter name="status" label="Status" :value="$filters['status'] ?? ''" :options="array_combine(\App\Models\ProductionBom::STATUSES, \App\Models\ProductionBom::STATUSES)" all-label="All statuses" />
            <div><label class="enterprise-form-label" for="effective_date">Effective on date</label><input class="enterprise-form-control w-full" type="date" id="effective_date" name="effective_date" value="{{ $filters['effective_date'] ?? '' }}"></div>
            <x-slot:actions><button class="button-primary">Apply Filters</button><x-filter.reset :href="route('production-formulas.index')" /></x-slot:actions>
        </x-filter.panel>
        @if (session('status'))
            <div class="status-success mb-3 rounded-lg px-4 py-3 text-sm font-bold">{{ session('status') }}</div>
        @endif
        <x-ui.data-table>
            <x-slot:head><tr>@foreach (['Number','Name','Type','Finished Item','Base Output','Version','Company / Branch','Effective','Status','Actions'] as $heading)<th class="px-3 py-2 text-left text-[11px] font-black uppercase theme-muted">{{ $heading }}</th>@endforeach</tr></x-slot:head>
            @forelse ($records as $record)
                <tr class="text-xs">
                    <td class="px-3 py-2 font-black"><a class="theme-link" href="{{ route('production-formulas.show', $record) }}">{{ $record->number }}</a></td>
                    <td class="px-3 py-2 font-bold">{{ $record->name }}</td><td class="px-3 py-2">{{ $record->production_type }}</td>
                    <td class="px-3 py-2">{{ $record->finishedItem?->sku }} — {{ $record->finishedItem?->name }}</td>
                    <td class="px-3 py-2 text-right">{{ \App\Support\QuantityFormatter::display($record->base_output_quantity) }} {{ $record->finishedItem?->baseUnit?->code }}</td>
                    <td class="px-3 py-2">v{{ $record->version }}</td><td class="px-3 py-2">{{ $record->company?->name }} / {{ $record->branch?->name ?: 'All branches' }}</td>
                    <td class="px-3 py-2">{{ $record->effective_from?->format('d M Y') ?: 'Open' }} — {{ $record->effective_to?->format('d M Y') ?: 'Open' }}</td>
                    <td class="px-3 py-2"><x-ui.status-badge :status="$record->status" /></td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <x-ui.table-action :href="route('production-formulas.show', $record)" label="View" />
                        @if ($record->status === \App\Models\ProductionBom::STATUS_DRAFT)
                            <x-ui.table-action class="ml-1" :href="route('production-formulas.edit', $record)" label="Edit" />
                        @endif
                    </td>
                </tr>
            @empty
                <x-ui.empty-state colspan="10" message="No Production Formulas found." />
            @endforelse
        </x-ui.data-table>
        <x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
