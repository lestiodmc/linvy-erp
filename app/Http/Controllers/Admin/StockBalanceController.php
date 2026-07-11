<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockBatchBalance;
use App\Models\ItemCategory;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockBalanceController extends Controller
{
    private const NEAR_EXPIRY_DAYS = 30;
    private const QTY_TOLERANCE = 0.000001;

    public function index(): View
    {
        $filters = request()->only(['keyword', 'company_id', 'branch_id', 'warehouse_id', 'item_category_id', 'stock_status', 'batch_tracking', 'reconciliation_status']);
        $branchIds = $this->accessibleBranches()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $onHand = 'COALESCE(NULLIF(stock_balances.qty_on_hand, 0), stock_balances.quantity_on_hand, 0)';
        $reserved = 'COALESCE(NULLIF(stock_balances.qty_reserved, 0), stock_balances.quantity_reserved, 0)';
        $batchTotals = StockBatchBalance::query()
            ->selectRaw('warehouse_id, item_id, SUM(qty_on_hand) as batch_total')
            ->groupBy('warehouse_id', 'item_id');

        $records = StockBalance::query()
            ->select('stock_balances.*')
            ->selectRaw('COALESCE(batch_totals.batch_total, 0) as batch_total')
            ->leftJoinSub($batchTotals, 'batch_totals', fn ($join) => $join
                ->on('batch_totals.warehouse_id', '=', 'stock_balances.warehouse_id')
                ->on('batch_totals.item_id', '=', 'stock_balances.item_id'))
            ->accessibleFromBranches($branchIds)
            ->with(['company', 'branch', 'warehouse.branch', 'warehouse.company', 'item.category', 'item.baseUnit', 'uom', 'baseUom'])
            ->when(filled($filters['keyword'] ?? null), function (Builder $query) use ($filters): void {
                $term = '%'.$filters['keyword'].'%';
                $query->whereHas('item', fn (Builder $item) => $item->where('sku', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when(filled($filters['company_id'] ?? null), fn (Builder $query) => $query->where(function (Builder $balance) use ($filters): void {
                $balance->where('stock_balances.company_id', $filters['company_id'])
                    ->orWhere(function (Builder $legacy) use ($filters): void {
                        $legacy->whereNull('stock_balances.company_id')
                            ->whereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('company_id', $filters['company_id'])->orWhereHas('branch', fn (Builder $branch) => $branch->where('company_id', $filters['company_id'])));
                    });
            }))
            ->when(filled($filters['branch_id'] ?? null), fn (Builder $query) => $query->whereHas('warehouse', fn (Builder $warehouse) => $warehouse->where('branch_id', $filters['branch_id'])))
            ->when(filled($filters['warehouse_id'] ?? null), fn (Builder $query) => $query->where('stock_balances.warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['item_category_id'] ?? null), fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('item_category_id', $filters['item_category_id'])))
            ->when(($filters['batch_tracking'] ?? '') === 'batch', fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('is_batch_tracked', true)))
            ->when(($filters['batch_tracking'] ?? '') === 'non_batch', fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('is_batch_tracked', false)))
            ->when(($filters['stock_status'] ?? '') === 'IN_STOCK', fn (Builder $query) => $query->whereRaw($onHand.' > 0'))
            ->when(($filters['stock_status'] ?? '') === 'ZERO_STOCK', fn (Builder $query) => $query->whereRaw($onHand.' = 0'))
            ->when(($filters['stock_status'] ?? '') === 'NEGATIVE_STOCK', fn (Builder $query) => $query->whereRaw($onHand.' < 0'))
            ->when(($filters['stock_status'] ?? '') === 'LOW_STOCK', fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('minimum_order_qty', '>', 0))->whereRaw($onHand.' > 0')->whereRaw($onHand.' <= (SELECT minimum_order_qty FROM items WHERE items.id = stock_balances.item_id)'))
            ->when(($filters['reconciliation_status'] ?? '') === 'MATCHED', fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('is_batch_tracked', true))->whereRaw('ABS('.$onHand.' - COALESCE(batch_totals.batch_total, 0)) <= ?', [self::QTY_TOLERANCE]))
            ->when(($filters['reconciliation_status'] ?? '') === 'MISMATCH', fn (Builder $query) => $query->whereHas('item', fn (Builder $item) => $item->where('is_batch_tracked', true))->whereRaw('ABS('.$onHand.' - COALESCE(batch_totals.batch_total, 0)) > ?', [self::QTY_TOLERANCE]))
            ->orderBy('stock_balances.warehouse_id')->orderBy('stock_balances.item_id')
            ->paginate(15)->withQueryString();

        return view('inventory.stock_balances.index', [
            'records' => $records, 'filters' => $filters,
            'companies' => Company::whereIn('id', $this->accessibleBranches()->pluck('company_id'))->orderBy('name')->pluck('name', 'id')->all(),
            'branches' => $this->accessibleBranches()->pluck('name', 'id')->all(),
            'warehouses' => $this->warehouses(),
            'itemCategories' => ItemCategory::orderBy('name')->pluck('name', 'id')->all(),
            'stockStatuses' => ['IN_STOCK' => 'In Stock', 'ZERO_STOCK' => 'Zero Stock', 'NEGATIVE_STOCK' => 'Negative Stock', 'LOW_STOCK' => 'Low Stock'],
            'batchTrackingOptions' => ['batch' => 'Batch Tracked', 'non_batch' => 'Non-batch'],
            'reconciliationStatuses' => ['MATCHED' => 'Matched', 'MISMATCH' => 'Mismatch'],
        ]);
    }

    public function batches(string|int $record): View
    {
        $record = $this->accessibleRecord($record);
        $record->load(['company', 'branch', 'warehouse.branch', 'item.baseUnit', 'uom', 'baseUom']);
        $onHand = $this->onHand($record);
        $tracksBatch = (bool) $record->item?->is_batch_tracked;
        $batchBalances = $tracksBatch ? StockBatchBalance::query()
            ->where('item_id', $record->item_id)->where('warehouse_id', $record->warehouse_id)
            ->where('qty_on_hand', '!=', 0)->orderBy('expiry_date')->orderBy('batch_no')->get() : collect();
        $batchTotal = (float) $batchBalances->sum('qty_on_hand');

        return view('inventory.stock_balances.batches', [
            'record' => $record, 'onHand' => $onHand, 'reserved' => $this->reserved($record), 'available' => $onHand - $this->reserved($record),
            'batchBalances' => $batchBalances, 'batchTotal' => $batchTotal,
            'reconciliationDifference' => $onHand - $batchTotal,
            'statusForBatch' => fn (mixed $expiryDate): array => $this->batchStatus($expiryDate),
        ]);
    }

    public function show(string|int $record): View
    {
        return $this->batches($record);
    }

    private function accessibleRecord(string|int $record): StockBalance
    {
        return StockBalance::query()->accessibleFromBranches($this->accessibleBranches()->pluck('id')->all())->findOrFail($record);
    }

    private function accessibleBranches()
    {
        return Branch::query()->where('is_active', true)
            ->when(! Auth::user()?->isSuperAdmin(), fn (Builder $query) => $query->whereHas('users', fn (Builder $users) => $users->whereKey(Auth::id())))
            ->orderBy('name')->get();
    }

    private function warehouses()
    {
        return Warehouse::with('branch')->whereIn('branch_id', $this->accessibleBranches()->pluck('id'))->orderBy('branch_id')->orderBy('name')->get();
    }

    private function onHand(StockBalance $record): float
    {
        return (float) ($record->qty_on_hand ?: $record->quantity_on_hand ?: 0);
    }

    private function reserved(StockBalance $record): float
    {
        return (float) ($record->qty_reserved ?: $record->quantity_reserved ?: 0);
    }

    private function batchStatus(mixed $expiryDate): array
    {
        if (blank($expiryDate)) return ['NO_EXPIRY', 'bg-slate-100 text-slate-700 ring-slate-200'];
        $expiry = Carbon::parse($expiryDate)->startOfDay();
        if ($expiry->lt(now()->startOfDay())) return ['EXPIRED', 'bg-red-50 text-red-700 ring-red-100'];
        if ($expiry->lte(now()->startOfDay()->addDays(self::NEAR_EXPIRY_DAYS))) return ['NEAR_EXPIRY', 'bg-amber-50 text-amber-700 ring-amber-100'];
        return ['VALID', 'bg-emerald-50 text-emerald-700 ring-emerald-100'];
    }
}
