<?php

namespace App\Http\Controllers\Admin;

use App\Models\BatchAssignment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Receiving;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Support\InventoryMovementSource;

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
        $branches = $this->accessibleBranches();
        $filters = $this->filters($branches);
        $query = $this->filteredQuery($filters, $branches);
        $summary = $this->summary(clone $query);
        $records = $query->with(['company', 'branch', 'warehouse.branch', 'warehouse.company', 'item.category', 'item.baseUnit', 'uom', 'baseUom'])
            ->orderByRaw('COALESCE(transaction_date, movement_date) DESC')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('inventory.stock_movements.index', [
            'records' => $records, 'filters' => $filters, 'summary' => $summary, 'sourceLinks' => InventoryMovementSource::links($records->getCollection()),
            'companies' => Company::query()->whereIn('id', $branches->pluck('company_id'))->orderBy('name')->get(),
            'branches' => filled($filters['company_id'] ?? null) ? $branches->where('company_id', (int) $filters['company_id'])->values() : $branches,
            'warehouses' => $this->warehouses($branches, $filters['company_id'] ?? null, $filters['branch_id'] ?? null),
            'items' => $this->itemOptions($branches), 'batches' => $this->batchOptions($filters, $branches),
            'movementTypes' => StockMovement::transactionTypeLabels(), 'directions' => ['IN' => 'IN', 'OUT' => 'OUT'],
        ]);
    }

    public function branchOptions(): JsonResponse
    {
        $companyId = (int) request()->validate(['company_id' => ['required', 'exists:companies,id']])['company_id'];
        $rows = $this->accessibleBranches()->where('company_id', $companyId)->values(); abort_if($rows->isEmpty(), 403);
        return response()->json($rows->map(fn (Branch $branch) => ['id' => $branch->id, 'name' => $branch->name]));
    }

    public function warehouseOptions(): JsonResponse
    {
        $data = request()->validate(['company_id' => ['nullable', 'exists:companies,id'], 'branch_id' => ['required', 'exists:branches,id']]);
        $branch = $this->accessibleBranches()->firstWhere('id', (int) $data['branch_id']); abort_unless($branch && (! filled($data['company_id'] ?? null) || (int) $branch->company_id === (int) $data['company_id']), 403);
        return response()->json($this->warehouses($this->accessibleBranches(), $branch->company_id, $branch->id)->map(fn (Warehouse $warehouse) => ['id' => $warehouse->id, 'label' => $branch->name.' - '.$warehouse->name]));
    }

    protected function query()
    {
        return StockMovement::query()->accessibleFromBranches($this->accessibleBranches()->pluck('id')->map(fn ($id) => (int) $id)->all())->with($this->with);
    }

    private function filteredQuery(array $filters, Collection $branches): Builder
    {
        return StockMovement::query()->accessibleFromBranches($branches->pluck('id')->map(fn ($id) => (int) $id)->all())
            ->when(filled($filters['company_id'] ?? null), fn (Builder $query) => $query->forCompany((int) $filters['company_id']))
            ->when(filled($filters['branch_id'] ?? null), fn (Builder $query) => $query->forBranch((int) $filters['branch_id']))
            ->when(filled($filters['warehouse_id'] ?? null), fn (Builder $query) => $query->where('warehouse_id', $filters['warehouse_id']))
            ->when(filled($filters['item_id'] ?? null), fn (Builder $query) => $query->where('item_id', $filters['item_id']))
            ->when(($filters['batch_no'] ?? '__all') !== '__all', function (Builder $query) use ($filters): void { $filters['batch_no'] === '__no_batch' ? $query->where(fn (Builder $batch) => $batch->whereNull('batch_no')->orWhere('batch_no', '')) : $query->where('batch_no', $filters['batch_no']); })
            ->when(filled($filters['transaction_type'] ?? null), fn (Builder $query) => $query->where(fn (Builder $type) => $type->where('transaction_type', $filters['transaction_type'])->orWhere('movement_type', $filters['transaction_type'])))
            ->when(($filters['direction'] ?? '') === 'IN', fn (Builder $query) => $query->where('quantity_in', '>', 0)->where('quantity_out', '<=', 0))
            ->when(($filters['direction'] ?? '') === 'OUT', fn (Builder $query) => $query->where('quantity_out', '>', 0)->where('quantity_in', '<=', 0))
            ->when(filled($filters['reference'] ?? null), fn (Builder $query) => $query->where(fn (Builder $reference) => $reference->where('transaction_number', 'like', '%'.$filters['reference'].'%')->orWhere('reference_number', 'like', '%'.$filters['reference'].'%')))
            ->when(filled($filters['keyword'] ?? null), function (Builder $query) use ($filters): void { $term = '%'.$filters['keyword'].'%'; $query->where(fn (Builder $search) => $search->where('stock_movements.transaction_number', 'like', $term)->orWhere('stock_movements.reference_number', 'like', $term)->orWhere('stock_movements.batch_no', 'like', $term)->orWhere('stock_movements.notes', 'like', $term)->orWhere('stock_movements.remarks', 'like', $term)->orWhereHas('item', fn (Builder $item) => $item->where('sku', 'like', $term)->orWhere('name', 'like', $term))); })
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->where(fn (Builder $date) => $date->whereDate('transaction_date', '>=', $filters['date_from'])->orWhere(fn (Builder $legacy) => $legacy->whereNull('transaction_date')->whereDate('movement_date', '>=', $filters['date_from']))))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->where(fn (Builder $date) => $date->whereDate('transaction_date', '<=', $filters['date_to'])->orWhere(fn (Builder $legacy) => $legacy->whereNull('transaction_date')->whereDate('movement_date', '<=', $filters['date_to']))));
    }

    private function filters(Collection $branches): array
    {
        $filters = request()->only(['keyword', 'company_id', 'branch_id', 'warehouse_id', 'item_id', 'batch_no', 'transaction_type', 'direction', 'reference', 'date_from', 'date_to']);
        $companyIds = $branches->pluck('company_id')->filter()->unique(); $companyId = filled($filters['company_id'] ?? null) && $companyIds->contains((int) $filters['company_id']) ? (int) $filters['company_id'] : null;
        $branchId = filled($filters['branch_id'] ?? null) && $branches->contains(fn (Branch $branch) => (int) $branch->id === (int) $filters['branch_id'] && (! $companyId || (int) $branch->company_id === $companyId)) ? (int) $filters['branch_id'] : null;
        $warehouse = filled($filters['warehouse_id'] ?? null) ? $this->warehouses($branches, $companyId, $branchId)->firstWhere('id', (int) $filters['warehouse_id']) : null;
        $filters['company_id'] = $companyId; $filters['branch_id'] = $branchId; $filters['warehouse_id'] = $warehouse?->id; $filters['batch_no'] = filled($filters['batch_no'] ?? null) ? $filters['batch_no'] : '__all';
        if (! request()->has('date_from')) $filters['date_from'] = now()->startOfMonth()->toDateString(); if (! request()->has('date_to')) $filters['date_to'] = now()->toDateString();
        return $filters;
    }

    private function accessibleBranches(): Collection { return Branch::query()->with('company')->where('is_active', true)->when(! Auth::user()?->isSuperAdmin(), fn (Builder $query) => $query->whereHas('users', fn (Builder $users) => $users->whereKey(Auth::id())))->orderBy('name')->get(); }
    private function warehouses(Collection $branches, mixed $companyId = null, mixed $branchId = null): Collection { return Warehouse::query()->with(['branch', 'company'])->where('is_active', true)->whereIn('branch_id', $branches->pluck('id'))->when(filled($branchId), fn (Builder $query) => $query->where('branch_id', $branchId))->when(filled($companyId), fn (Builder $query) => $query->where(fn (Builder $scope) => $scope->where('company_id', $companyId)->orWhere(fn (Builder $legacy) => $legacy->whereNull('company_id')->whereHas('branch', fn (Builder $branch) => $branch->where('company_id', $companyId)))))->orderBy('name')->get(); }
    private function itemOptions(Collection $branches): Collection { $ids = StockMovement::query()->accessibleFromBranches($branches->pluck('id')->all())->select('item_id')->distinct()->limit(500)->pluck('item_id'); return Item::query()->whereIn('id', $ids)->orderBy('sku')->get(['id', 'sku', 'name'])->mapWithKeys(fn (Item $item) => [$item->id => trim($item->sku.' - '.$item->name)]); }
    private function batchOptions(array $filters, Collection $branches): array { $query = StockMovement::query()->accessibleFromBranches($branches->pluck('id')->all())->when(filled($filters['company_id'] ?? null), fn (Builder $q) => $q->forCompany((int) $filters['company_id']))->when(filled($filters['branch_id'] ?? null), fn (Builder $q) => $q->forBranch((int) $filters['branch_id']))->when(filled($filters['warehouse_id'] ?? null), fn (Builder $q) => $q->where('warehouse_id', $filters['warehouse_id']))->when(filled($filters['item_id'] ?? null), fn (Builder $q) => $q->where('item_id', $filters['item_id'])); $options = ['__no_batch' => 'No Batch']; foreach ($query->whereNotNull('batch_no')->where('batch_no', '!=', '')->distinct()->orderBy('batch_no')->limit(500)->pluck('batch_no') as $batch) $options[$batch] = $batch; return $options; }
    private function summary(Builder $query): array
    {
        $uomId = 'COALESCE(stock_movements.base_uom_id, stock_movements.uom_id, items.base_unit_id)';
        $groups = $query
            ->leftJoin('items', 'items.id', '=', 'stock_movements.item_id')
            ->leftJoin('units_of_measure', 'units_of_measure.id', '=', DB::raw($uomId))
            ->selectRaw($uomId.' effective_uom_id, units_of_measure.code uom_code')
            ->selectRaw('COUNT(*) rows_count, COALESCE(SUM(quantity_in), 0) total_in, COALESCE(SUM(quantity_out), 0) total_out')
            ->selectRaw('SUM(CASE WHEN quantity_in > 0 AND quantity_out <= 0 THEN 1 ELSE 0 END) in_count')
            ->selectRaw('SUM(CASE WHEN quantity_out > 0 AND quantity_in <= 0 THEN 1 ELSE 0 END) out_count')
            ->groupByRaw($uomId.', units_of_measure.code')
            ->orderBy('units_of_measure.code')
            ->get()
            ->map(fn ($group): array => [
                'id' => $group->effective_uom_id,
                'uom' => $group->uom_code ?: 'Unknown UOM',
                'rows' => (int) $group->rows_count,
                'in_count' => (int) $group->in_count,
                'out_count' => (int) $group->out_count,
                'in' => (float) $group->total_in,
                'out' => (float) $group->total_out,
                'net' => (float) $group->total_in - (float) $group->total_out,
            ])
            ->values();

        return [
            'rows' => $groups->sum('rows'),
            'in_count' => $groups->sum('in_count'),
            'out_count' => $groups->sum('out_count'),
            'uom_count' => $groups->count(),
            'single_uom' => $groups->count() === 1,
            'groups' => $groups->all(),
        ];
    }
}
