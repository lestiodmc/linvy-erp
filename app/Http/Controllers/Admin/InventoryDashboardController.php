<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchAssignment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\StockBatchBalance;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Support\InventoryExpiryStatus;
use App\Support\InventoryMovementSource;
use App\Support\InventoryReconciliation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InventoryDashboardController extends Controller
{
    public function __invoke(): View
    {
        $branches = $this->accessibleBranches();
        $filters = $this->filters($branches);
        $onHand = InventoryReconciliation::onHandExpression();
        $difference = InventoryReconciliation::differenceExpression($onHand);
        $tolerance = InventoryReconciliation::tolerance();
        $batchTotals = StockBatchBalance::query()->selectRaw('warehouse_id, item_id, SUM(qty_on_hand) batch_total')->groupBy('warehouse_id', 'item_id');
        $balances = $this->balanceQuery($filters, $branches)->select('stock_balances.*')->selectRaw('COALESCE(batch_totals.batch_total,0) batch_total')->leftJoinSub($batchTotals, 'batch_totals', fn ($join) => $join->on('batch_totals.warehouse_id', '=', 'stock_balances.warehouse_id')->on('batch_totals.item_id', '=', 'stock_balances.item_id'))->join('items', 'items.id', '=', 'stock_balances.item_id');
        $kpis = (clone $balances)->select([])->selectRaw("COUNT(*) total_items, SUM(CASE WHEN {$onHand} > 0 THEN 1 ELSE 0 END) in_stock, SUM(CASE WHEN {$onHand} = 0 THEN 1 ELSE 0 END) zero_stock, SUM(CASE WHEN {$onHand} < 0 THEN 1 ELSE 0 END) negative_stock, SUM(CASE WHEN {$onHand} > 0 AND items.minimum_order_qty > 0 AND {$onHand} <= items.minimum_order_qty THEN 1 ELSE 0 END) low_stock, SUM(CASE WHEN items.is_batch_tracked = 1 AND {$difference} > ? THEN 1 ELSE 0 END) batch_mismatch", [$tolerance])->first();
        $expiryQuery = $this->expiryQuery($filters, $branches);
        $today = now()->toDateString();
        $nearExpiryEnd = now()->addDays(InventoryExpiryStatus::NEAR_EXPIRY_DAYS)->toDateString();
        $expiryCounts = (clone $expiryQuery)->selectRaw(
            'SUM(CASE WHEN expiry_date < ? THEN 1 ELSE 0 END) expired, SUM(CASE WHEN expiry_date >= ? AND expiry_date <= ? THEN 1 ELSE 0 END) near_expiry, SUM(CASE WHEN expiry_date > ? THEN 1 ELSE 0 END) safe',
            [$today, $today, $nearExpiryEnd, $nearExpiryEnd]
        )->first();
        $documentCounts = $this->documentCounts($filters, $branches);
        $lowStocks = (clone $balances)->with(['warehouse.branch', 'item.baseUnit', 'baseUom', 'uom'])->whereRaw($onHand.' > 0')->where('items.minimum_order_qty', '>', 0)->whereRaw($onHand.' <= items.minimum_order_qty')->orderByRaw($onHand.' ASC')->limit(8)->get();
        $mismatches = (clone $balances)->with(['warehouse.branch', 'item.baseUnit'])->where('items.is_batch_tracked', true)->whereRaw($difference.' > ?', [$tolerance])->orderByRaw($difference.' DESC')->limit(8)->get();
        $expiryBatches = (clone $expiryQuery)->select('stock_batch_balances.*')->selectSub(StockBalance::query()->select('id')->whereColumn('warehouse_id', 'stock_batch_balances.warehouse_id')->whereColumn('item_id', 'stock_batch_balances.item_id')->limit(1), 'balance_id')->with(['warehouse.branch', 'item.baseUnit'])->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(InventoryExpiryStatus::NEAR_EXPIRY_DAYS)->toDateString())->orderByRaw('CASE WHEN expiry_date < CURRENT_DATE THEN 0 ELSE 1 END')->orderBy('expiry_date')->limit(8)->get();
        $recentMovements = $this->movementQuery($filters, $branches)->with(['warehouse.branch', 'item.baseUnit', 'baseUom', 'uom'])->orderByRaw('COALESCE(transaction_date, movement_date) DESC')->orderByDesc('id')->limit(8)->get();
        $movementChart = $this->movementChart($filters, $branches);

        return view('inventory.dashboard', [
            'filters' => $filters, 'companies' => Company::whereIn('id', $branches->pluck('company_id'))->orderBy('name')->get(), 'branches' => filled($filters['company_id'] ?? null) ? $branches->where('company_id', (int) $filters['company_id'])->values() : $branches, 'warehouses' => $this->warehouses($branches, $filters['company_id'] ?? null, $filters['branch_id'] ?? null),
            'kpis' => ['Total Stock Items' => (int) $kpis->total_items, 'In Stock' => (int) $kpis->in_stock, 'Zero Stock' => (int) $kpis->zero_stock, 'Negative Stock' => (int) $kpis->negative_stock, 'Low Stock' => (int) $kpis->low_stock, 'Batch Mismatch' => (int) $kpis->batch_mismatch, 'Expired Batches' => (int) $expiryCounts->expired, 'Near Expiry Batches' => (int) $expiryCounts->near_expiry, 'Pending Warehouse Transfers' => $documentCounts['transfers'], 'Draft Stock Adjustments' => $documentCounts['adjustments'], 'Draft Batch Assignments' => $documentCounts['assignments']],
            'lowStocks' => $lowStocks, 'expiryBatches' => $expiryBatches, 'mismatches' => $mismatches, 'recentMovements' => $recentMovements, 'movementSourceLinks' => InventoryMovementSource::links($recentMovements), 'onHandExpression' => $onHand,
            'movementChart' => $movementChart,
            'expiryChart' => ['Expired' => (int) $expiryCounts->expired, 'Near Expiry' => (int) $expiryCounts->near_expiry, 'Safe' => (int) $expiryCounts->safe],
            'generatedAt' => now(),
        ]);
    }

    private function movementChart(array $filters, Collection $branches): array
    {
        $start = now()->subDays(29)->startOfDay();
        $rows = $this->movementQuery($filters, $branches)
            ->whereRaw('COALESCE(transaction_date, movement_date) >= ?', [$start])
            ->selectRaw('DATE(COALESCE(transaction_date, movement_date)) movement_day')
            ->selectRaw('SUM(CASE WHEN COALESCE(quantity_in, 0) > 0 THEN 1 ELSE 0 END) incoming_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(quantity_out, 0) > 0 THEN 1 ELSE 0 END) outgoing_count')
            ->groupByRaw('DATE(COALESCE(transaction_date, movement_date))')
            ->orderBy('movement_day')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->movement_day)->toDateString());

        return collect(range(0, 29))->map(function (int $offset) use ($start, $rows): array {
            $day = $start->copy()->addDays($offset);
            $row = $rows->get($day->toDateString());

            return [
                'date' => $day->format('d M'),
                'incoming' => (int) ($row?->incoming_count ?? 0),
                'outgoing' => (int) ($row?->outgoing_count ?? 0),
            ];
        })->all();
    }

    private function balanceQuery(array $filters, Collection $branches): Builder
    {
        return StockBalance::query()->accessibleFromBranches($branches->pluck('id')->all())->when(filled($filters['company_id'] ?? null), fn (Builder $q) => $q->whereHas('warehouse', fn (Builder $w) => $w->where('company_id', $filters['company_id'])->orWhereHas('branch', fn (Builder $b) => $b->where('company_id', $filters['company_id']))))->when(filled($filters['branch_id'] ?? null), fn (Builder $q) => $q->whereHas('warehouse', fn (Builder $w) => $w->where('branch_id', $filters['branch_id'])))->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('stock_balances.warehouse_id', $filters['warehouse_id']));
    }

    private function expiryQuery(array $filters, Collection $branches): Builder
    {
        return StockBatchBalance::query()->where('qty_on_hand', '>', 0)->whereHas('warehouse', fn (Builder $q) => $q->whereIn('branch_id', $branches->pluck('id')))->when(filled($filters['company_id'] ?? null), fn (Builder $q) => $q->whereHas('warehouse', fn (Builder $w) => $w->where('company_id', $filters['company_id'])->orWhereHas('branch', fn (Builder $b) => $b->where('company_id', $filters['company_id']))))->when(filled($filters['branch_id'] ?? null), fn (Builder $q) => $q->whereHas('warehouse', fn (Builder $w) => $w->where('branch_id', $filters['branch_id'])))->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']));
    }

    private function movementQuery(array $filters, Collection $branches): Builder
    {
        return StockMovement::query()->accessibleFromBranches($branches->pluck('id')->all())->when(filled($filters['company_id'] ?? null), fn (Builder $q) => $q->forCompany((int) $filters['company_id']))->when(filled($filters['branch_id'] ?? null), fn (Builder $q) => $q->forBranch((int) $filters['branch_id']))->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']));
    }

    private function documentCounts(array $filters, Collection $branches): array
    {
        $scope = fn (Builder $q) => $q->whereIn('branch_id', $branches->pluck('id'))->when(filled($filters['company_id'] ?? null), fn (Builder $x) => $x->where('company_id', $filters['company_id']))->when(filled($filters['branch_id'] ?? null), fn (Builder $x) => $x->where('branch_id', $filters['branch_id']));
        $transfers = WarehouseTransfer::query()->where($scope)->where('status', WarehouseTransfer::STATUS_DRAFT)->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('from_warehouse_id', $filters['warehouse_id'])->orWhere('to_warehouse_id', $filters['warehouse_id'])))->count();
        $adjustments = StockAdjustment::query()->where($scope)->where('status', StockAdjustment::STATUS_DRAFT)->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']))->count();
        $assignments = BatchAssignment::query()->where($scope)->where('status', BatchAssignment::STATUS_DRAFT)->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']))->count();
        return compact('transfers', 'adjustments', 'assignments');
    }

    private function filters(Collection $branches): array
    {
        $filters = request()->only(['company_id', 'branch_id', 'warehouse_id']);
        $companyIds = $branches->pluck('company_id')->filter()->unique()->values();
        $company = filled($filters['company_id'] ?? null) && $companyIds->contains((int) $filters['company_id'])
            ? (int) $filters['company_id']
            : ($companyIds->count() === 1 ? (int) $companyIds->first() : null);
        $branch = filled($filters['branch_id'] ?? null) && $branches->contains(
            fn (Branch $candidate) => (int) $candidate->id === (int) $filters['branch_id']
                && (! $company || (int) $candidate->company_id === $company)
        ) ? (int) $filters['branch_id'] : null;
        $warehouse = filled($filters['warehouse_id'] ?? null)
            ? $this->warehouses($branches, $company, $branch)->firstWhere('id', (int) $filters['warehouse_id'])
            : null;

        return ['company_id' => $company, 'branch_id' => $branch, 'warehouse_id' => $warehouse?->id];
    }

    private function accessibleBranches(): Collection { return Branch::query()->where('is_active', true)->when(! Auth::user()?->isSuperAdmin(), fn (Builder $q) => $q->whereHas('users', fn (Builder $u) => $u->whereKey(Auth::id())))->orderBy('name')->get(); }
    private function warehouses(Collection $branches, mixed $company = null, mixed $branch = null): Collection { return Warehouse::query()->with('branch')->where('is_active', true)->whereIn('branch_id', $branches->pluck('id'))->when(filled($company), fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('company_id', $company)->orWhere(fn (Builder $l) => $l->whereNull('company_id')->whereHas('branch', fn (Builder $b) => $b->where('company_id', $company)))))->when(filled($branch), fn (Builder $q) => $q->where('branch_id', $branch))->orderBy('name')->get(); }
}
