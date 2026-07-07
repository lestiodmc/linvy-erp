<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\StockBatchBalance;
use App\Models\ItemCategory;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StockBalanceController extends ResourceController
{
    protected string $model = StockBalance::class;
    protected string $route = 'stock-balances';
    protected string $title = 'Stock Balance';
    protected string $viewPath = 'inventory.stock_balances';
    protected array $with = ['item', 'warehouse'];
    protected array $columns = ['item.name', 'warehouse.name', 'quantity_on_hand', 'quantity_reserved', 'average_cost', 'last_movement_at'];
    protected array $fields = [];

    public function index(): View
    {
        $request = request();
        $filters = $request->only(['keyword', 'company_id', 'branch_id', 'warehouse_id', 'item_category_id', 'stock_status']);
        $availableExpression = '(COALESCE(NULLIF(stock_balances.qty_on_hand, 0), stock_balances.quantity_on_hand, 0) - COALESCE(NULLIF(stock_balances.qty_reserved, 0), stock_balances.quantity_reserved, 0))';

        $records = StockBalance::query()
            ->select('stock_balances.*')
            ->with(['company', 'branch', 'warehouse.branch', 'warehouse.company', 'warehouse.warehouseType', 'item.category'])
            ->when(($filters['stock_status'] ?? '') === 'low', fn ($query) => $query->join('items as low_stock_items', 'low_stock_items.id', '=', 'stock_balances.item_id'))
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = $filters['keyword'];

                $query->where(function ($search) use ($keyword): void {
                    $search->whereHas('item', fn ($item) => $item
                        ->where('sku', 'like', '%'.$keyword.'%')
                        ->orWhere('name', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('warehouse', fn ($warehouse) => $warehouse->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->when(filled($filters['company_id'] ?? null), fn ($query) => $query->where('company_id', $filters['company_id']))
            ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['warehouse_id'] ?? null), fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['item_category_id'] ?? null), fn ($query) => $query->whereHas('item', fn ($item) => $item->where('item_category_id', $filters['item_category_id'])))
            ->when(($filters['stock_status'] ?? '') === 'available', fn ($query) => $query->whereRaw($availableExpression.' > 0'))
            ->when(($filters['stock_status'] ?? '') === 'low', fn ($query) => $query
                ->whereRaw($availableExpression.' > 0')
                ->where('low_stock_items.minimum_order_qty', '>', 0)
                ->whereRaw($availableExpression.' <= low_stock_items.minimum_order_qty'))
            ->when(($filters['stock_status'] ?? '') === 'out', fn ($query) => $query->whereRaw($availableExpression.' = 0'))
            ->when(($filters['stock_status'] ?? '') === 'negative', fn ($query) => $query->whereRaw($availableExpression.' < 0'))
            ->orderBy('warehouse_id')
            ->orderBy('item_id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.stock_balances.index', [
            'records' => $records,
            'filters' => $filters,
            'companies' => Company::orderBy('name')->pluck('name', 'id')->all(),
            'branches' => Branch::orderBy('name')->pluck('name', 'id')->all(),
            'warehouses' => $this->warehouseRecords(),
            'itemCategories' => ItemCategory::orderBy('name')->pluck('name', 'id')->all(),
            'stockStatuses' => [
                'available' => 'Available Only',
                'low' => 'Low Stock',
                'out' => 'Out of Stock',
                'negative' => 'Negative Stock',
            ],
        ]);
    }

    public function batches(string|int $record): View
    {
        $record = StockBalance::query()->findOrFail($record);
        $record->load(['company', 'branch', 'warehouse.branch', 'item.baseUnit', 'uom', 'baseUom']);

        $onHand = $this->onHand($record);
        $reserved = $this->reserved($record);
        $batchBalances = collect();
        $knownBatchQty = 0.0;

        if ((bool) ($record->item?->is_batch_tracked ?? false) || (bool) ($record->item?->has_expiry_date ?? false)) {
            $batchBalances = StockBatchBalance::query()
                ->where('item_id', $record->item_id)
                ->where('warehouse_id', $record->warehouse_id)
                ->when(filled($record->company_id), fn ($query) => $query->where('company_id', $record->company_id))
                ->when(filled($record->branch_id), fn ($query) => $query->where('branch_id', $record->branch_id))
                ->orderByRaw("CASE WHEN batch_no IS NULL OR batch_no = '' THEN 0 ELSE 1 END")
                ->orderBy('expiry_date')
                ->orderBy('batch_no')
                ->get();
            $knownBatchQty = (float) $batchBalances->sum(fn (StockBatchBalance $batch): float => (float) $batch->qty_on_hand);
        }

        return view('inventory.stock_balances.batches', [
            'record' => $record,
            'onHand' => $onHand,
            'reserved' => $reserved,
            'available' => $onHand - $reserved,
            'batchBalances' => $batchBalances,
            'noBatchQty' => max(0, $onHand - $knownBatchQty),
            'statusForBatch' => fn (?string $batchNo, mixed $expiryDate): array => $this->batchStatus($batchNo, $expiryDate),
        ]);
    }

    public function show(string|int $record): View
    {
        $record = StockBalance::query()->findOrFail($record);
        $record->load(['company', 'branch', 'warehouse.branch', 'item.baseUnit', 'uom', 'baseUom']);
        $onHand = $this->onHand($record);
        $knownBatchQty = 0.0;
        $batchBalances = collect();

        if ((bool) ($record->item?->is_batch_tracked ?? false)) {
            $batchBalances = StockBatchBalance::query()
                ->where('warehouse_id', $record->warehouse_id)
                ->where('item_id', $record->item_id)
                ->orderBy('expiry_date')
                ->orderBy('batch_no')
                ->get();
            $knownBatchQty = (float) $batchBalances->sum(fn (StockBatchBalance $batch): float => (float) $batch->qty_on_hand);
        }

        return view('inventory.stock_balances.show', [
            'record' => $record,
            'onHand' => $onHand,
            'reserved' => $this->reserved($record),
            'available' => $onHand - $this->reserved($record),
            'batchBalances' => $batchBalances,
            'noBatchQty' => max(0, $onHand - $knownBatchQty),
        ]);
    }

    private function warehouseRecords()
    {
        return Warehouse::with('branch')
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();
    }

    private function onHand(StockBalance $record): float
    {
        $newQty = (float) ($record->qty_on_hand ?? 0);
        $legacyQty = (float) ($record->quantity_on_hand ?? 0);

        return $newQty !== 0.0 ? $newQty : $legacyQty;
    }

    private function reserved(StockBalance $record): float
    {
        $newQty = (float) ($record->qty_reserved ?? 0);
        $legacyQty = (float) ($record->quantity_reserved ?? 0);

        return $newQty !== 0.0 ? $newQty : $legacyQty;
    }

    private function batchStatus(?string $batchNo, mixed $expiryDate): array
    {
        if (blank($batchNo)) {
            return ['No Batch', 'bg-slate-100 text-slate-700 ring-slate-200'];
        }

        if (blank($expiryDate)) {
            return ['Normal', 'bg-emerald-50 text-emerald-700 ring-emerald-100'];
        }

        $expiry = Carbon::parse($expiryDate)->startOfDay();
        $today = now()->startOfDay();

        if ($expiry->lt($today)) {
            return ['Expired', 'bg-red-50 text-red-700 ring-red-100'];
        }

        if ($expiry->lte($today->copy()->addDays(30))) {
            return ['Expiring Soon', 'bg-amber-50 text-amber-700 ring-amber-100'];
        }

        return ['Normal', 'bg-emerald-50 text-emerald-700 ring-emerald-100'];
    }
}
