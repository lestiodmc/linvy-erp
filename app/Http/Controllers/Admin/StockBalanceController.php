<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ItemCategory;
use App\Models\StockBalance;
use App\Models\Warehouse;
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
        $availableExpression = '(COALESCE(NULLIF(qty_on_hand, 0), quantity_on_hand, 0) - COALESCE(NULLIF(qty_reserved, 0), quantity_reserved, 0))';

        $records = StockBalance::query()
            ->with(['company', 'branch', 'warehouse.branch', 'warehouse.company', 'warehouse.warehouseType', 'item.category'])
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
                ->whereHas('item', fn ($item) => $item->where('minimum_order_qty', '>', 0))
                ->whereRaw($availableExpression.' <= (select minimum_order_qty from items where items.id = stock_balances.item_id limit 1)'))
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
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id')->all(),
            'itemCategories' => ItemCategory::orderBy('name')->pluck('name', 'id')->all(),
            'stockStatuses' => [
                'available' => 'Available Only',
                'low' => 'Low Stock',
                'out' => 'Out of Stock',
                'negative' => 'Negative Stock',
            ],
        ]);
    }
}
