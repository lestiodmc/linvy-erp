<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ItemCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockMovementController extends ResourceController
{
    protected string $model = StockMovement::class;
    protected string $route = 'stock-movements';
    protected string $title = 'Stock Movement';
    protected string $viewPath = 'inventory.stock_movements';
    protected array $with = ['item', 'warehouse'];
    protected array $columns = ['movement_date', 'item.name', 'warehouse.name', 'movement_type', 'quantity_in', 'quantity_out', 'reference_number'];
    protected array $fields = [];

    public function index(): View
    {
        $filters = $this->filters();

        $records = StockMovement::query()
            ->accessibleFromBranches($this->accessibleBranches()->pluck('id')->map(fn ($id) => (int) $id)->all())
            ->with(['company', 'branch', 'warehouse.branch', 'warehouse.company', 'item.category', 'item.baseUnit', 'uom'])
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = $filters['keyword'];

                $query->where(function ($search) use ($keyword): void {
                    $search->where('reference_number', 'like', '%'.$keyword.'%')
                        ->orWhere('transaction_number', 'like', '%'.$keyword.'%')
                        ->orWhereHas('item', fn ($item) => $item
                            ->where('sku', 'like', '%'.$keyword.'%')
                            ->orWhere('name', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('warehouse', fn ($warehouse) => $warehouse->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->where(function ($dateQuery) use ($filters): void {
                $dateQuery->whereDate('transaction_date', '>=', $filters['date_from'])
                    ->orWhereDate('movement_date', '>=', $filters['date_from']);
            }))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->where(function ($dateQuery) use ($filters): void {
                $dateQuery->whereDate('transaction_date', '<=', $filters['date_to'])
                    ->orWhereDate('movement_date', '<=', $filters['date_to']);
            }))
            ->when(filled($filters['company_id'] ?? null), fn ($query) => $query->forCompany((int) $filters['company_id']))
            ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->forBranch((int) $filters['branch_id']))
            ->when(filled($filters['warehouse_id'] ?? null), fn ($query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['movement_type'] ?? null), function ($query) use ($filters): void {
                $query->where(function ($movementQuery) use ($filters): void {
                    $movementQuery->where('movement_type', $filters['movement_type'])
                        ->orWhere('transaction_type', $filters['movement_type']);
                });
            })
            ->when(filled($filters['item_category_id'] ?? null), fn ($query) => $query->whereHas('item', fn ($item) => $item->where('item_category_id', $filters['item_category_id'])))
            ->orderByRaw('COALESCE(transaction_date, movement_date) DESC')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.stock_movements.index', [
            'records' => $records,
            'filters' => $filters,
            'companies' => Company::whereIn('id', $this->accessibleBranches()->pluck('company_id'))->orderBy('name')->pluck('name', 'id')->all(),
            'branches' => $this->accessibleBranches()->pluck('name', 'id')->all(),
            'warehouses' => $this->warehouseRecords(),
            'movementTypes' => $this->movementTypes(),
            'itemCategories' => ItemCategory::orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    private function filters(): array
    {
        $request = request();
        $filters = $request->only(['keyword', 'date_from', 'date_to', 'company_id', 'branch_id', 'warehouse_id', 'movement_type', 'item_category_id']);

        if (! $request->has('date_from')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
        }

        if (! $request->has('date_to')) {
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }

    private function movementTypes(): array
    {
        return collect([
            'RCV',
            'IN',
            'OUT',
            'PURCHASE_RECEIVE',
            'TRANSFER_IN',
            'TRANSFER_OUT',
            'TRF-IN',
            'TRF-OUT',
            'ADJUSTMENT_IN',
            'ADJUSTMENT_OUT',
            'ADJ-IN',
            'ADJ-OUT',
            'BATCH_ASSIGNMENT_IN',
            'BATCH_ASSIGNMENT_OUT',
            'DO',
            'SERVICE',
            'PRODUCTION_OUTPUT',
            'PRODUCTION_INPUT',
        ])->mapWithKeys(fn (string $type): array => [$type => str($type)->replace(['_', '-'], ' ')->title()])->all();
    }

    private function warehouseRecords()
    {
        return Warehouse::with('branch')
            ->whereIn('branch_id', $this->accessibleBranches()->pluck('id'))
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();
    }

    private function accessibleBranches()
    {
        return Branch::query()
            ->where('is_active', true)
            ->when(! Auth::user()?->isSuperAdmin(), fn (Builder $query) => $query->whereHas('users', fn (Builder $users) => $users->whereKey(Auth::id())))
            ->orderBy('name')
            ->get();
    }
}
