<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Inventory Dashboard" subtitle="Real-time overview of your inventory health and activity." />
    </x-slot>

    @php
        $qty = fn ($value) => number_format((float) $value, 2);
        $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '-';
        $onHand = fn ($row) => (float) ($row->qty_on_hand ?? 0);
        $uom = fn ($row) => $row->baseUom?->code ?: $row->uom?->code ?: $row->item?->baseUnit?->code ?: '-';
        $head = 'px-3 py-2 text-left text-[10px] font-black uppercase tracking-wide text-slate-500';
        $cell = 'px-3 py-2';
        $scope = array_filter($filters, fn ($value) => filled($value));
        $kpiGroups = [
            'Inventory Health' => [
                ['label' => 'Total Stock Items', 'value' => $kpis['Total Stock Items'], 'tone' => 'slate', 'href' => route('stock-balances.index', $scope), 'help' => 'Balance rows'],
                ['label' => 'In Stock', 'value' => $kpis['In Stock'], 'tone' => 'emerald', 'href' => route('stock-balances.index', ['stock_status' => 'IN_STOCK'] + $scope), 'help' => 'Positive on hand'],
                ['label' => 'Zero Stock', 'value' => $kpis['Zero Stock'], 'tone' => 'slate', 'href' => route('stock-balances.index', ['stock_status' => 'ZERO_STOCK'] + $scope), 'help' => 'No on hand'],
                ['label' => 'Low Stock', 'value' => $kpis['Low Stock'], 'tone' => 'amber', 'href' => route('stock-balances.index', ['stock_status' => 'LOW_STOCK'] + $scope), 'help' => 'At or below minimum'],
                ['label' => 'Negative Stock', 'value' => $kpis['Negative Stock'], 'tone' => 'red', 'href' => route('stock-balances.index', ['stock_status' => 'NEGATIVE_STOCK'] + $scope), 'help' => 'Requires review'],
                ['label' => 'Batch Mismatch', 'value' => $kpis['Batch Mismatch'], 'tone' => 'red', 'href' => route('stock-balances.index', ['reconciliation_status' => 'MISMATCH'] + $scope), 'help' => 'Outside tolerance'],
            ],
            'Expiry Monitoring' => [
                ['label' => 'Expired Batches', 'value' => $kpis['Expired Batches'], 'tone' => 'red', 'href' => route('inventory.dashboard', $scope).'#expiry-monitoring', 'help' => 'Positive stock expired'],
                ['label' => 'Near Expiry Batches', 'value' => $kpis['Near Expiry Batches'], 'tone' => 'amber', 'href' => route('inventory.dashboard', $scope).'#expiry-monitoring', 'help' => 'Within '.\App\Support\InventoryExpiryStatus::NEAR_EXPIRY_DAYS.' days'],
            ],
            'Open Documents' => [
                ['label' => 'Pending Transfers', 'value' => $kpis['Pending Warehouse Transfers'], 'tone' => 'blue', 'href' => route('warehouse-transfers.index', ['status' => 'draft']), 'help' => 'Draft documents'],
                ['label' => 'Draft Adjustments', 'value' => $kpis['Draft Stock Adjustments'], 'tone' => 'blue', 'href' => route('stock-adjustments.index', ['status' => 'draft']), 'help' => 'Awaiting posting'],
                ['label' => 'Draft Batch Assignments', 'value' => $kpis['Draft Batch Assignments'], 'tone' => 'blue', 'href' => route('batch-assignments.index', ['status' => 'draft']), 'help' => 'Awaiting posting'],
            ],
        ];
        $tones = [
            'slate' => 'theme-link',
            'emerald' => 'border-emerald-200 theme-link hover:border-emerald-300',
            'amber' => 'border-amber-200 text-amber-700 hover:border-amber-300',
            'red' => 'border-red-200 text-red-700 hover:border-red-300',
            'blue' => 'border-blue-200 text-blue-700 hover:border-blue-300',
        ];
        $movementMax = max(1, collect($movementChart)->max(fn ($day) => max($day['incoming'], $day['outgoing'])));
        $movementTotal = collect($movementChart)->sum(fn ($day) => $day['incoming'] + $day['outgoing']);
        $expiryTotal = array_sum($expiryChart);
        $expiredPercent = $expiryTotal ? ($expiryChart['Expired'] / $expiryTotal) * 100 : 0;
        $nearPercent = $expiryTotal ? ($expiryChart['Near Expiry'] / $expiryTotal) * 100 : 0;
    @endphp

    <div class="mx-auto max-w-screen-2xl space-y-4">
        <x-ui.filter-toolbar :action="route('inventory.dashboard')" columns="lg:grid-cols-[12rem_12rem_15rem_7rem_6rem]" data-dashboard-filters>
            <x-ui.select-filter name="company_id" label="Company" :value="$filters['company_id'] ?? ''" :options="$companies->pluck('name', 'id')" all-label="All companies" data-company />
            <x-ui.select-filter name="branch_id" label="Branch" :value="$filters['branch_id'] ?? ''" :options="$branches->pluck('name', 'id')" all-label="All branches" data-branch />
            <div>
                <label class="sr-only" for="dashboard_warehouse">Warehouse</label>
                <select id="dashboard_warehouse" name="warehouse_id" data-warehouse class="h-10 w-full rounded-lg border-slate-200 text-sm">
                    <option value="">All warehouses</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) ($filters['warehouse_id'] ?? '') === (string) $warehouse->id)>{{ $warehouse->branch?->name }} - {{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="theme-primary theme-focus h-10 rounded-lg px-3 text-sm font-bold">Apply Filters</button>
            <a href="{{ route('inventory.dashboard') }}" class="theme-surface theme-focus flex h-10 items-center justify-center rounded-lg border px-3 text-sm font-bold">Reset</a>
        </x-ui.filter-toolbar>

        @foreach($kpiGroups as $group => $cards)
            <section aria-labelledby="{{ \Illuminate\Support\Str::slug($group) }}">
                <h2 id="{{ \Illuminate\Support\Str::slug($group) }}" class="mb-1.5 text-xs font-black uppercase tracking-wide text-slate-600">{{ $group }}</h2>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 {{ count($cards) > 3 ? '2xl:grid-cols-6' : 'xl:grid-cols-3' }}">
                    @foreach($cards as $card)
                        <a href="{{ $card['href'] }}" class="theme-card theme-focus group flex min-h-20 items-center justify-between rounded-lg border-l-4 px-3 py-2 transition {{ $tones[$card['tone']] }}">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-current/5" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19V9m5 10V5m5 14v-7m5 7V3" /></svg>
                                </span>
                                <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $card['help'] }}</p>
                                </div>
                            </div>
                            <p class="ml-3 text-2xl font-black tabular-nums">{{ number_format($card['value']) }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section aria-labelledby="inventory-insights">
            <h2 id="inventory-insights" class="mb-2 text-xs font-black uppercase tracking-wide text-slate-600">Inventory Insights</h2>
            <div class="grid gap-3 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,0.8fr)_minmax(18rem,0.8fr)]">
                <div class="theme-card rounded-xl p-3 shadow-sm">
                    <div class="flex items-start justify-between"><div><h3 class="text-sm font-black">Stock Movement — Last 30 Days</h3><p class="theme-muted text-[11px]">Transaction counts by day; quantities are not mixed across UOMs.</p></div><div class="flex gap-3 text-[10px] font-bold"><span><i class="mr-1 inline-block h-2 w-2 rounded-full" style="background:var(--theme-chart-1)"></i>IN</span><span><i class="mr-1 inline-block h-2 w-2 rounded-full" style="background:var(--theme-chart-2)"></i>OUT</span></div></div>
                    @if($movementTotal > 0)
                        <div class="mt-3 overflow-x-auto">
                            <div class="flex h-32 min-w-[38rem] items-end gap-1 border-b theme-chart-grid" role="img" aria-label="Incoming and outgoing movement transaction counts for the last 30 days">
                                @foreach($movementChart as $day)
                                    <div class="group relative flex h-full min-w-3 flex-1 items-end justify-center gap-px" title="{{ $day['date'] }}: {{ $day['incoming'] }} in, {{ $day['outgoing'] }} out">
                                        <span class="w-1/2 rounded-t-sm" style="height:{{ max(2, ($day['incoming'] / $movementMax) * 100) }}%;background:var(--theme-chart-1)"></span>
                                        <span class="w-1/2 rounded-t-sm" style="height:{{ max(2, ($day['outgoing'] / $movementMax) * 100) }}%;background:var(--theme-chart-2)"></span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="theme-muted grid h-32 place-items-center text-xs">No inventory movements in the last 30 days.</div>
                    @endif
                </div>

                <div class="theme-card rounded-xl p-3 shadow-sm">
                    <h3 class="text-sm font-black">Expiry Status Overview</h3><p class="theme-muted text-[11px]">Positive-stock batch counts.</p>
                    @if($expiryTotal > 0)
                        <div class="mt-3 flex items-center gap-4">
                            <div class="grid h-24 w-24 shrink-0 place-items-center rounded-full" style="background:conic-gradient(var(--status-danger) 0 {{ $expiredPercent }}%,var(--status-warning) {{ $expiredPercent }}% {{ $expiredPercent + $nearPercent }}%,var(--status-success) {{ $expiredPercent + $nearPercent }}% 100%)"><div class="theme-surface grid h-14 w-14 place-items-center rounded-full text-sm font-black">{{ $expiryTotal }}</div></div>
                            <div class="space-y-1.5 text-xs">@foreach($expiryChart as $label => $value)<div class="flex min-w-28 justify-between gap-4"><span class="theme-muted">{{ $label }}</span><b>{{ $value }}</b></div>@endforeach</div>
                        </div>
                    @else
                        <div class="theme-muted grid h-24 place-items-center text-xs">No dated batches in stock.</div>
                    @endif
                </div>

                <div class="theme-card rounded-xl p-3 shadow-sm">
                    <h3 class="text-sm font-black">Quick Actions</h3><p class="theme-muted text-[11px]">Common inventory workflows.</p>
                    <div class="mt-2 grid grid-cols-2 gap-1.5">
                        @foreach([
                            ['Stock Adjustment', route('stock-adjustments.create'), 'Correct stock'],
                            ['Warehouse Transfer', route('warehouse-transfers.create'), 'Move inventory'],
                            ['Batch Assignment', route('batch-assignments.create'), 'Assign batches'],
                            ['Item Ledger', route('item-ledger.index'), 'Audit activity'],
                            ['Stock Balance', route('stock-balances.index'), 'Review stock'],
                        ] as [$label, $href, $description])
                            <a href="{{ $href }}" class="theme-option theme-focus rounded-lg border border-transparent px-2 py-1.5"><span class="block text-xs font-bold">{{ $label }}</span><span class="theme-muted block text-[10px]">{{ $description }}</span></a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section aria-labelledby="actionable-exceptions">
            <h2 id="actionable-exceptions" class="mb-2 text-xs font-black uppercase tracking-wide text-slate-600">Actionable Exceptions</h2>
            <div class="grid gap-3 xl:grid-cols-2">
                <div>
                    <div class="mb-1 flex items-center justify-between"><h3 class="text-sm font-black">Low Stock Items</h3><a href="{{ route('stock-balances.index', ['stock_status' => 'LOW_STOCK'] + $scope) }}" class="text-xs font-bold theme-link">View All</a></div>
                    <x-ui.data-table>
                        <x-slot:head><tr>@foreach(['Warehouse', 'SKU', 'Item', 'On Hand', 'Minimum', 'UOM', 'Action'] as $h)<th class="{{ $head }}">{{ $h }}</th>@endforeach</tr></x-slot:head>
                        @forelse($lowStocks as $row)
                            <tr class="text-xs"><td class="{{ $cell }}">{{ $row->warehouse?->name }}</td><td class="{{ $cell }} font-bold">{{ $row->item?->sku }}</td><td class="{{ $cell }}">{{ $row->item?->name }}</td><td class="{{ $cell }} text-right font-bold text-amber-700">{{ $qty($onHand($row)) }}</td><td class="{{ $cell }} text-right">{{ $qty($row->item?->minimum_order_qty) }}</td><td class="{{ $cell }}">{{ $uom($row) }}</td><td class="{{ $cell }}"><a href="{{ route('stock-balances.show', $row) }}" class="font-bold theme-link">Open</a></td></tr>
                        @empty
                            <x-ui.empty-state colspan="7" message="No low-stock items in the selected scope." />
                        @endforelse
                    </x-ui.data-table>
                </div>
                <div id="expiry-monitoring">
                    <div class="mb-1 flex items-center justify-between"><h3 class="text-sm font-black">Near Expiry / Expired Batches</h3><a href="{{ route('stock-balances.index', ['batch_tracking' => 'batch'] + $scope) }}" class="text-xs font-bold theme-link">View All</a></div>
                    <x-ui.data-table>
                        <x-slot:head><tr>@foreach(['Warehouse', 'SKU', 'Item', 'Batch', 'Expiry', 'Qty', 'Status', 'Action'] as $h)<th class="{{ $head }}">{{ $h }}</th>@endforeach</tr></x-slot:head>
                        @forelse($expiryBatches as $batch)
                            @php
                                $status = \App\Support\InventoryExpiryStatus::status($batch->expiry_date);
                            @endphp
                            <tr class="text-xs"><td class="{{ $cell }}">{{ $batch->warehouse?->name }}</td><td class="{{ $cell }} font-bold">{{ $batch->item?->sku }}</td><td class="{{ $cell }}">{{ $batch->item?->name }}</td><td class="{{ $cell }} font-bold">{{ $batch->batch_no }}</td><td class="{{ $cell }}">{{ $date($batch->expiry_date) }}</td><td class="{{ $cell }} text-right">{{ $qty($batch->qty_on_hand) }}</td><td class="{{ $cell }}"><span class="rounded-full px-2 py-0.5 font-bold ring-1 {{ \App\Support\InventoryExpiryStatus::badge($status) }}">{{ $status }}</span></td><td class="{{ $cell }} whitespace-nowrap">@if($batch->balance_id)<a href="{{ route('stock-balances.batches', $batch->balance_id) }}" class="font-bold theme-link">Batches</a>@endif<a href="{{ route('item-ledger.index', ['item_id' => $batch->item_id, 'warehouse_id' => $batch->warehouse_id, 'batch_no' => $batch->batch_no]) }}" class="ml-2 font-bold text-slate-700">Ledger</a></td></tr>
                        @empty
                            <x-ui.empty-state colspan="8" message="No expired or near-expiry batches." />
                        @endforelse
                    </x-ui.data-table>
                </div>
            </div>
            <div class="mt-3">
                <div class="mb-1 flex items-center justify-between"><h3 class="text-sm font-black">Batch Reconciliation Mismatches</h3><a href="{{ route('stock-balances.index', ['reconciliation_status' => 'MISMATCH'] + $scope) }}" class="text-xs font-bold theme-link">View All</a></div>
                <x-ui.data-table>
                    <x-slot:head><tr>@foreach(['Warehouse', 'SKU', 'Item', 'Warehouse Total', 'Batch Total', 'Difference', 'Action'] as $h)<th class="{{ $head }}">{{ $h }}</th>@endforeach</tr></x-slot:head>
                    @forelse($mismatches as $row)
                        @php
                            $difference = $onHand($row) - (float) $row->batch_total;
                        @endphp
                        <tr class="text-xs"><td class="{{ $cell }}">{{ $row->warehouse?->name }}</td><td class="{{ $cell }} font-bold">{{ $row->item?->sku }}</td><td class="{{ $cell }}">{{ $row->item?->name }}</td><td class="{{ $cell }} text-right">{{ $qty($onHand($row)) }}</td><td class="{{ $cell }} text-right">{{ $qty($row->batch_total) }}</td><td class="{{ $cell }} text-right font-bold text-red-700">{{ $qty($difference) }}</td><td class="{{ $cell }}"><a href="{{ route('stock-balances.show', $row) }}" class="font-bold theme-link">Open</a></td></tr>
                    @empty
                        <x-ui.empty-state colspan="7" message="All batch-tracked stock is reconciled." />
                    @endforelse
                </x-ui.data-table>
            </div>
        </section>

        <section aria-labelledby="recent-activity">
            <div class="mb-1 flex items-center justify-between"><h2 id="recent-activity" class="text-xs font-black uppercase tracking-wide text-slate-600">Recent Activity</h2><a href="{{ route('stock-movements.index', $scope) }}" class="text-xs font-bold theme-link">View All</a></div>
            <x-ui.data-table>
                <x-slot:head><tr>@foreach(['Date', 'Document', 'Type', 'Direction', 'Warehouse', 'SKU', 'Batch', 'Qty', 'Action'] as $h)<th class="{{ $head }}">{{ $h }}</th>@endforeach</tr></x-slot:head>
                @forelse($recentMovements as $movement)
                    @php
                        $source = $movementSourceLinks[$movement->id] ?? null;
                        $direction = $movement->direction();
                    @endphp
                    <tr class="text-xs"><td class="{{ $cell }}">{{ $date($movement->transaction_date ?: $movement->movement_date) }}</td><td class="{{ $cell }} font-bold">@if($source)<a href="{{ $source }}" class="theme-link">{{ $movement->transaction_number ?: $movement->reference_number ?: '-' }}</a>@else{{ $movement->transaction_number ?: $movement->reference_number ?: '-' }}@endif</td><td class="{{ $cell }}"><x-ui.movement-type-badge :type="$movement->transaction_type ?: $movement->movement_type" /></td><td class="{{ $cell }} font-bold {{ $direction === 'IN' ? 'theme-link' : ($direction === 'OUT' ? 'text-red-700' : 'text-slate-500') }}">{{ $direction }}</td><td class="{{ $cell }}">{{ $movement->warehouse?->name }}</td><td class="{{ $cell }} font-bold">{{ $movement->item?->sku }}</td><td class="{{ $cell }}">{{ $movement->batch_no ?: 'No Batch' }}</td><td class="{{ $cell }} text-right font-bold">{{ $qty($direction === 'IN' ? $movement->quantityIn() : $movement->quantityOut()) }}</td><td class="{{ $cell }}"><a href="{{ route('stock-movements.show', $movement) }}" class="font-bold theme-link">Open</a></td></tr>
                @empty
                    <x-ui.empty-state colspan="9" message="No recent inventory movements." />
                @endforelse
            </x-ui.data-table>
        </section>

        <footer class="theme-muted flex flex-wrap items-center justify-between gap-2 border-t pt-3 text-xs" style="border-color:var(--theme-border)">
            <span>Showing inventory summary as of {{ $generatedAt->timezone(config('app.timezone'))->format('d M Y, H:i') }}</span>
            <a href="{{ request()->fullUrl() }}" class="theme-link theme-focus rounded px-1 font-bold">Refresh summary</a>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-dashboard-filters]');
            const company = form.querySelector('[data-company]');
            const branch = form.querySelector('[data-branch]');
            const warehouse = form.querySelector('[data-warehouse]');

            company.addEventListener('change', async () => {
                branch.innerHTML = '<option value="">All branches</option>';
                warehouse.innerHTML = '<option value="">All warehouses</option>';
                if (!company.value) return;
                const response = await fetch(`{{ route('stock-movements.branches') }}?company_id=${company.value}`);
                if (response.ok) (await response.json()).forEach(row => branch.add(new Option(row.name, row.id)));
            });

            branch.addEventListener('change', async () => {
                warehouse.innerHTML = '<option value="">All warehouses</option>';
                if (!branch.value) return;
                const companyQuery = company.value ? `&company_id=${company.value}` : '';
                const response = await fetch(`{{ route('stock-movements.warehouses') }}?branch_id=${branch.value}${companyQuery}`);
                if (response.ok) (await response.json()).forEach(row => warehouse.add(new Option(row.label, row.id)));
            });
        });
    </script>
</x-app-layout>

