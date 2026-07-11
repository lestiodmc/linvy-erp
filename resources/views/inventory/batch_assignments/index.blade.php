<x-app-layout>
    <x-slot name="header"><x-ui.page-header title="Batch Assignments" subtitle="Auditable allocation of legacy No Batch stock." /></x-slot>
    <div class="mx-auto max-w-screen-2xl">
        <x-ui.filter-toolbar :action="route('batch-assignments.index')" columns="lg:grid-cols-[minmax(14rem,1fr)_11rem_11rem_13rem_9rem_7rem_6rem]" data-index-filters>
            <x-ui.search-input :value="$filters['keyword'] ?? ''" />
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies->pluck('name','id')" all-label="All companies" data-index-company />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches->pluck('name','id')" all-label="All branches" data-index-branch />
            <div><label class="sr-only" for="warehouse_id">Warehouse</label><select id="warehouse_id" name="warehouse_id" data-index-warehouse class="h-10 w-full rounded-lg border-slate-200 text-sm"><option value="">All warehouses</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string)($filters['warehouse_id']??'')===(string)$warehouse->id)>{{ $warehouse->branch?->name }} - {{ $warehouse->name }}</option>@endforeach</select></div>
            <x-ui.select-filter name="status" label="Status" :value="$filters['status'] ?? ''" :options="collect($statuses)->mapWithKeys(fn($s)=>[$s=>str($s)->title()])" all-label="All statuses" />
            <button class="h-10 rounded-lg bg-emerald-600 px-3 text-sm font-bold text-white">Apply</button><a href="{{ route('batch-assignments.index') }}" class="flex h-10 items-center justify-center rounded-lg border px-3 text-sm font-bold">Reset</a>
        </x-ui.filter-toolbar>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-index-filters]');
                const company = form.querySelector('[data-index-company]');
                const branch = form.querySelector('[data-index-branch]');
                const warehouse = form.querySelector('[data-index-warehouse]');
                company.addEventListener('change', async () => {
                    branch.innerHTML = '<option value="">All branches</option>';
                    warehouse.innerHTML = '<option value="">All warehouses</option>';
                    if (!company.value) return;
                    const response = await fetch(`{{ route('batch-assignments.branches') }}?company_id=${company.value}`);
                    if (!response.ok) return;
                    (await response.json()).forEach((row) => branch.add(new Option(row.name, row.id)));
                });
                branch.addEventListener('change', async () => {
                    warehouse.innerHTML = '<option value="">All warehouses</option>';
                    if (!branch.value) return;
                    const companyQuery = company.value ? `&company_id=${company.value}` : '';
                    const response = await fetch(`{{ route('batch-assignments.warehouses') }}?branch_id=${branch.value}${companyQuery}`);
                    if (!response.ok) return;
                    (await response.json()).forEach((row) => warehouse.add(new Option(row.label, row.id)));
                });
            });
        </script>
        <div class="mb-2 text-right"><a href="{{ route('batch-assignments.create') }}" class="enterprise-create theme-focus inline-flex h-9 items-center rounded-lg px-3 text-sm font-bold">New Assignment</a></div>
        <x-ui.data-table><x-slot:head><tr>@foreach(['Number','Date','Branch','Warehouse','Items','Status','Created By','Action'] as $h)<th class="px-3 py-2 text-left text-[11px] font-black uppercase text-slate-500">{{ $h }}</th>@endforeach</tr></x-slot:head>
        @forelse($records as $record)<tr class="text-xs"><td class="px-3 py-2 font-bold">{{ $record->number }}</td><td class="px-3 py-2">{{ $record->assignment_date?->format('d M Y') }}</td><td class="px-3 py-2">{{ $record->branch?->name }}</td><td class="px-3 py-2">{{ $record->warehouse?->name }}</td><td class="px-3 py-2 text-right tabular-nums">{{ $record->lines_count }}</td><td class="px-3 py-2"><x-ui.status-badge :status="$record->status" /></td><td class="px-3 py-2">{{ $record->createdBy?->name ?: '-' }}</td><td class="px-3 py-2 text-right"><x-ui.table-action :href="route('batch-assignments.show', $record)" /></td></tr>@empty<x-ui.empty-state colspan="8" message="No batch assignments found." description="No batch assignment documents match the selected filters." />@endforelse
        </x-ui.data-table><x-ui.pagination :records="$records" />
    </div>
</x-app-layout>
