<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchAssignment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\ProductionBom;
use App\Models\Receiving;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Support\CommandPaletteMenuRegistry;
use App\Support\ModuleManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GlobalSearchController extends Controller
{
    private const RESULT_LIMIT = 3;

    public function __invoke(Request $request, CommandPaletteMenuRegistry $menus): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $query = Str::lower(trim((string) $request->query('q', '')));
        $query = Str::limit(str_replace(['%', '_'], '', $query), 80, '');
        $groups = ['Menu' => $menus->search($user, $query, 5)];

        if (mb_strlen($query) < 2) {
            return response()->json(['query' => $query, 'groups' => $groups]);
        }

        $branchIds = $this->accessibleBranchIds($user);

        if ($this->allowed($user, 'master-data')) {
            $groups['Items'] = $this->items($query, $user);
            $groups['Master Data'] = array_merge(
                $this->suppliers($query),
                $this->customers($query),
                $this->warehouses($query, $branchIds)
            );
        }

        if ($this->allowed($user, 'purchase')) {
            $groups['Purchase Documents'] = array_merge(
                $this->purchaseRequests($query, $user, $branchIds),
                $this->purchaseOrders($query, $user, $branchIds),
                $this->receivings($query, $user, $branchIds)
            );
        }

        if ($this->allowed($user, 'inventory')) {
            $groups['Inventory Documents'] = array_merge(
                $this->warehouseTransfers($query, $branchIds),
                $this->stockAdjustments($query, $branchIds),
                $this->batchAssignments($query, $branchIds)
            );
        }
        if ($this->allowed($user, 'production')) {
            $groups['Production Formulas'] = $this->productionFormulas($query, $user, $branchIds);
        }

        $groups = collect($groups)->map(fn (array $results): array => array_slice($results, 0, 9))->filter()->all();

        return response()->json(['query' => $query, 'groups' => $groups]);
    }

    private function items(string $query, User $user): array
    {
        return $this->ranked(Item::query(), $query, ['sku', 'name'], 'sku', 5)
            ->get(['id', 'sku', 'name'])
            ->map(function (Item $item) use ($user): array {
                $actions = [];
                if ($this->allowed($user, 'inventory')) {
                    $actions = [
                        ['label' => 'Stock Balance', 'url' => route('stock-balances.index', ['keyword' => $item->sku])],
                        ['label' => 'Item Ledger', 'url' => route('item-ledger.index', ['item_id' => $item->id])],
                        ['label' => 'Stock Movements', 'url' => route('stock-movements.index', ['item_id' => $item->id])],
                    ];
                }

                return $this->result('ITEM', trim($item->sku.' — '.$item->name), 'Inventory item', route('items.show', $item), null, $actions);
            })->all();
    }

    private function suppliers(string $query): array
    {
        return $this->ranked(Supplier::query(), $query, ['code', 'name'], 'code')->get(['id', 'code', 'name'])
            ->map(fn (Supplier $row): array => $this->result('SUPPLIER', trim(($row->code ? $row->code.' — ' : '').$row->name), 'Supplier', route('suppliers.show', $row)))->all();
    }

    private function customers(string $query): array
    {
        return $this->ranked(Customer::query(), $query, ['code', 'name'], 'code')->get(['id', 'code', 'name'])
            ->map(fn (Customer $row): array => $this->result('CUSTOMER', trim(($row->code ? $row->code.' — ' : '').$row->name), 'Customer', route('customers.show', $row)))->all();
    }

    private function warehouses(string $query, array $branchIds): array
    {
        return $this->ranked(Warehouse::query()->whereIn('branch_id', $branchIds)->with('branch:id,name'), $query, ['code', 'name'], 'code')
            ->get(['id', 'branch_id', 'code', 'name'])->map(fn (Warehouse $row): array => $this->result('WAREHOUSE', trim(($row->code ? $row->code.' — ' : '').$row->name), $row->branch?->name ?: 'Warehouse', route('warehouses.show', $row)))->all();
    }

    private function purchaseRequests(string $query, User $user, array $branchIds): array
    {
        $builder = $this->branchDocuments(PurchaseRequest::query(), $user, $branchIds)->with('requester:id,name')
            ->where(fn (Builder $search) => $this->contains($search, $query, ['number', 'department'])->orWhereHas('requester', fn (Builder $requester) => $this->contains($requester, $query, ['name'])));
        return $this->rankedNumber($builder, $query)->get(['id', 'requested_by', 'number', 'department', 'status'])
            ->map(fn (PurchaseRequest $row): array => $this->result('PURCHASE REQUEST', $row->number, $row->requester?->name ?: ($row->department ?: 'Purchase request'), route('purchase-requests.show', $row), $row->status))->all();
    }

    private function purchaseOrders(string $query, User $user, array $branchIds): array
    {
        $builder = $this->branchDocuments(PurchaseOrder::query(), $user, $branchIds)->with('supplier:id,name')
            ->where(fn (Builder $search) => $this->contains($search, $query, ['number'])->orWhereHas('supplier', fn (Builder $supplier) => $this->contains($supplier, $query, ['name'])));
        return $this->rankedNumber($builder, $query)->get(['id', 'supplier_id', 'number', 'status'])
            ->map(fn (PurchaseOrder $row): array => $this->result('PURCHASE ORDER', $row->number, $row->supplier?->name ?: 'Purchase order', route('purchase-orders.show', $row), $row->status))->all();
    }

    private function receivings(string $query, User $user, array $branchIds): array
    {
        $builder = $this->branchDocuments(Receiving::query(), $user, $branchIds)->with('supplier:id,name')
            ->where(fn (Builder $search) => $this->contains($search, $query, ['number', 'supplier_delivery_number'])->orWhereHas('supplier', fn (Builder $supplier) => $this->contains($supplier, $query, ['name'])));
        return $this->rankedNumber($builder, $query)->get(['id', 'supplier_id', 'number', 'status'])
            ->map(fn (Receiving $row): array => $this->result('RECEIVING', $row->number, $row->supplier?->name ?: 'Receiving', route('receivings.show', $row), $row->status))->all();
    }

    private function warehouseTransfers(string $query, array $branchIds): array
    {
        $builder = WarehouseTransfer::query()
            ->whereHas('fromWarehouse', fn (Builder $warehouse) => $warehouse->whereIn('branch_id', $branchIds))
            ->whereHas('toWarehouse', fn (Builder $warehouse) => $warehouse->whereIn('branch_id', $branchIds))
            ->where(fn (Builder $scope) => $scope->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
            ->with(['fromWarehouse:id,code,name', 'toWarehouse:id,code,name'])
            ->where(fn (Builder $search) => $this->contains($search, $query, ['number'])->orWhereHas('fromWarehouse', fn (Builder $warehouse) => $this->contains($warehouse, $query, ['code', 'name']))->orWhereHas('toWarehouse', fn (Builder $warehouse) => $this->contains($warehouse, $query, ['code', 'name'])));
        return $this->rankedNumber($builder, $query)->get(['id', 'from_warehouse_id', 'to_warehouse_id', 'number', 'status'])
            ->map(fn (WarehouseTransfer $row): array => $this->result('WAREHOUSE TRANSFER', $row->number, ($row->fromWarehouse?->code ?: '-').' → '.($row->toWarehouse?->code ?: '-'), route('warehouse-transfers.show', $row), $row->status))->all();
    }

    private function stockAdjustments(string $query, array $branchIds): array
    {
        $builder = StockAdjustment::query()->whereHas('warehouse', fn (Builder $warehouse) => $warehouse->whereIn('branch_id', $branchIds))
            ->where(fn (Builder $scope) => $scope->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))->with('warehouse:id,code,name')
            ->where(fn (Builder $search) => $this->contains($search, $query, ['number', 'reason_code'])->orWhereHas('warehouse', fn (Builder $warehouse) => $this->contains($warehouse, $query, ['code', 'name'])));
        return $this->rankedNumber($builder, $query)->get(['id', 'warehouse_id', 'number', 'reason_code', 'status'])
            ->map(fn (StockAdjustment $row): array => $this->result('STOCK ADJUSTMENT', $row->number, $row->warehouse?->name ?: str($row->reason_code)->replace('_', ' ')->title(), route('stock-adjustments.show', $row), $row->status))->all();
    }

    private function batchAssignments(string $query, array $branchIds): array
    {
        $builder = BatchAssignment::query()->whereHas('warehouse', fn (Builder $warehouse) => $warehouse->whereIn('branch_id', $branchIds))
            ->where(fn (Builder $scope) => $scope->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))->with('warehouse:id,code,name')
            ->where(fn (Builder $search) => $this->contains($search, $query, ['number', 'reason'])->orWhereHas('warehouse', fn (Builder $warehouse) => $this->contains($warehouse, $query, ['code', 'name'])));
        return $this->rankedNumber($builder, $query)->get(['id', 'warehouse_id', 'number', 'reason', 'status'])
            ->map(fn (BatchAssignment $row): array => $this->result('BATCH ASSIGNMENT', $row->number, $row->warehouse?->name ?: ($row->reason ?: 'Batch assignment'), route('batch-assignments.show', $row), $row->status))->all();
    }

    private function productionFormulas(string $query, User $user, array $branchIds): array
    {
        return ProductionBom::accessibleTo($user)->with('finishedItem:id,sku,name')
            ->where(fn (Builder $search) => $this->contains($search, $query, ['number', 'name'])->orWhereHas('finishedItem', fn (Builder $item) => $this->contains($item, $query, ['sku', 'name'])))
            ->orderByDesc('id')->limit(self::RESULT_LIMIT)->get(['id','finished_item_id','number','name','status'])
            ->map(fn (ProductionBom $row): array => $this->result('PRODUCTION FORMULA', $row->number.' — '.$row->name, $row->finishedItem?->sku ?: 'Production Formula', route('production-formulas.show', $row), $row->status))->all();
    }

    private function ranked(Builder $builder, string $query, array $columns, string $primary, int $limit = self::RESULT_LIMIT): Builder
    {
        return $builder->where(fn (Builder $search) => $this->contains($search, $query, $columns))
            ->orderByRaw("CASE WHEN LOWER({$primary}) = ? THEN 0 WHEN LOWER({$primary}) LIKE ? THEN 1 ELSE 2 END", [$query, $query.'%'])->orderBy($primary)->limit($limit);
    }

    private function rankedNumber(Builder $builder, string $query): Builder
    {
        return $builder->orderByRaw('CASE WHEN LOWER(number) = ? THEN 0 WHEN LOWER(number) LIKE ? THEN 1 ELSE 2 END', [$query, $query.'%'])->orderByDesc('id')->limit(self::RESULT_LIMIT);
    }

    private function contains(Builder $builder, string $query, array $columns): Builder
    {
        foreach ($columns as $index => $column) {
            $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
            $builder->{$method}("LOWER({$column}) LIKE ?", ['%'.$query.'%']);
        }
        return $builder;
    }

    private function branchDocuments(Builder $builder, User $user, array $branchIds): Builder
    {
        return $user->isSuperAdmin() ? $builder : $builder->where(fn (Builder $scope) => $scope->whereNull('branch_id')->orWhereIn('branch_id', $branchIds));
    }

    private function accessibleBranchIds(User $user): array
    {
        return Branch::query()->where('is_active', true)->when(! $user->isSuperAdmin(), fn (Builder $query) => $query->whereHas('users', fn (Builder $users) => $users->whereKey($user->id)))->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    private function allowed(User $user, string $module): bool
    {
        return $user->canAccessModule($module) && ModuleManager::enabled($module);
    }

    private function result(string $type, string $title, string $description, string $url, ?string $status = null, array $actions = []): array
    {
        return compact('type', 'title', 'description', 'status', 'url', 'actions');
    }
}
