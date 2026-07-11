<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseTransferRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockMovement;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\Inventory\WarehouseTransferPostingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WarehouseTransferController extends Controller
{
    public function __construct(private readonly WarehouseTransferPostingService $warehouseTransferPostingService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->indexFilters($request);
        $branches = $this->accessibleBranches();
        $branchIds = $branches->pluck('id');

        $records = WarehouseTransfer::query()
            ->with(['company', 'branch', 'fromWarehouse.branch', 'toWarehouse.branch', 'lines'])
            ->withCount('lines')
            ->withSum('lines', 'quantity')
            ->when(! Auth::user()?->isSuperAdmin(), fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->when(filled($filters['keyword'] ?? null), function (Builder $query) use ($filters): void {
                $keyword = $filters['keyword'];

                $query->where(function (Builder $search) use ($keyword): void {
                    $search->where('number', 'like', '%'.$keyword.'%')
                        ->orWhereHas('fromWarehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('toWarehouse', fn (Builder $warehouse) => $warehouse->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('transfer_date', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('transfer_date', '<=', $filters['date_to']))
            ->when(filled($filters['company_id'] ?? null), fn (Builder $query) => $query->where('company_id', $filters['company_id']))
            ->when(filled($filters['branch_id'] ?? null), fn (Builder $query) => $query->where('branch_id', $filters['branch_id']))
            ->when(filled($filters['from_warehouse_id'] ?? null), fn (Builder $query) => $query->where('from_warehouse_id', $filters['from_warehouse_id']))
            ->when(filled($filters['to_warehouse_id'] ?? null), fn (Builder $query) => $query->where('to_warehouse_id', $filters['to_warehouse_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.warehouse_transfers.index', [
            'records' => $records,
            'filters' => $filters,
            'companies' => Company::orderBy('name')->pluck('name', 'id')->all(),
            'branches' => $branches->pluck('name', 'id')->all(),
            'warehouses' => $this->warehouses(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): View
    {
        $branches = $this->accessibleBranches();
        $branch = $branches->count() === 1 ? $branches->first() : null;

        return $this->formView(new WarehouseTransfer([
            'company_id' => $branch?->company_id,
            'branch_id' => $branch?->id,
            'transfer_date' => now()->toDateString(),
            'status' => WarehouseTransfer::STATUS_DRAFT,
        ]));
    }

    public function store(WarehouseTransferRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureBranchAccess((int) $data['branch_id']);

        $record = $this->warehouseTransferPostingService->create($data);

        if ($request->input('action') === 'post') {
            $this->warehouseTransferPostingService->post($record);

            return redirect()->route('warehouse-transfers.show', $record)->with('status', 'Warehouse transfer posted.');
        }

        return redirect()->route('warehouse-transfers.show', $record)->with('status', 'Warehouse transfer saved as draft.');
    }

    public function show(string|int $record): View
    {
        $record = WarehouseTransfer::query()->findOrFail($record);
        $this->ensureBranchAccess((int) $record->branch_id);

        $movements = StockMovement::query()
            ->with(['warehouse', 'item', 'uom', 'createdBy'])
            ->where('reference_type', WarehouseTransfer::class)
            ->where('reference_id', $record->id)
            ->whereIn('transaction_type', [
                StockMovement::TRANSACTION_TRF_OUT,
                StockMovement::TRANSACTION_TRF_IN,
                StockMovement::LEGACY_TRANSACTION_TRF_OUT,
                StockMovement::LEGACY_TRANSACTION_TRF_IN,
            ])
            ->orderBy('id')
            ->get();

        return view('inventory.warehouse_transfers.show', [
            'record' => $record->load(['company', 'branch', 'fromWarehouse', 'toWarehouse', 'postedBy', 'cancelledBy', 'lines.item', 'lines.unit']),
            'movements' => $movements,
        ]);
    }

    public function edit(string|int $record): View
    {
        $record = WarehouseTransfer::query()->findOrFail($record);
        $this->ensureBranchAccess((int) $record->branch_id);
        abort_if($record->status !== WarehouseTransfer::STATUS_DRAFT, 422, 'Posted or cancelled transfer cannot be edited.');

        return $this->formView($record->load(['lines.item', 'lines.unit']));
    }

    public function update(WarehouseTransferRequest $request, string|int $record): RedirectResponse
    {
        $record = WarehouseTransfer::query()->findOrFail($record);
        $data = $request->validated();

        $this->ensureBranchAccess((int) $record->branch_id);
        $this->ensureBranchAccess((int) $data['branch_id']);
        $record = $this->warehouseTransferPostingService->update($record, $data);

        if ($request->input('action') === 'post') {
            $this->warehouseTransferPostingService->post($record);

            return redirect()->route('warehouse-transfers.show', $record)->with('status', 'Warehouse transfer posted.');
        }

        return redirect()->route('warehouse-transfers.show', $record)->with('status', 'Warehouse transfer updated.');
    }

    public function destroy(string|int $record): RedirectResponse
    {
        $record = WarehouseTransfer::query()->findOrFail($record);
        $this->ensureBranchAccess((int) $record->branch_id);
        abort_if($record->status !== WarehouseTransfer::STATUS_DRAFT, 422, 'Only draft transfer can be deleted.');
        $record->delete();

        return redirect()->route('warehouse-transfers.index')->with('status', 'Warehouse transfer deleted.');
    }

    public function post(string|int $record): RedirectResponse
    {
        $record = WarehouseTransfer::query()->findOrFail($record);
        $this->ensureBranchAccess((int) $record->branch_id);
        $this->warehouseTransferPostingService->post($record);

        return back()->with('status', 'Warehouse transfer posted.');
    }

    public function cancel(string|int $record): RedirectResponse
    {
        $record = WarehouseTransfer::query()->findOrFail($record);
        $this->ensureBranchAccess((int) $record->branch_id);
        $this->warehouseTransferPostingService->cancel($record);

        return back()->with('status', 'Warehouse transfer cancelled.');
    }

    public function items(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $warehouseId = $request->filled('warehouse_id') ? (int) $request->query('warehouse_id') : null;

        if (strlen($search) < 2 || ! $warehouseId) {
            return response()->json([]);
        }

        $warehouse = $this->accessibleWarehouse($warehouseId);

        if (! $warehouse) {
            return response()->json([]);
        }

        $items = Item::query()
            ->with(['baseUnit:id,code,name', 'unitOfMeasure:id,code,name'])
            ->whereHas('stockBalances', function (Builder $balance) use ($warehouse): void {
                $balance
                    ->where('warehouse_id', $warehouse->id)
                    ->where('qty_on_hand', '>', 0);
            })
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($search): void {
                $query->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            })
            ->orderBy('sku')
            ->limit(20)
            ->get(['id', 'sku', 'name', 'unit_of_measure_id', 'base_unit_id', 'is_batch_tracked', 'has_expiry_date', 'allow_negative_stock']);

        return response()->json($items->map(function (Item $item) use ($warehouseId): array {
            $info = $warehouseId
                ? $this->warehouseTransferPostingService->itemInfo($item, $warehouseId)
                : [
                    'available_qty' => 0.0,
                    'unit_id' => $item->base_unit_id ?: $item->unit_of_measure_id,
                    'unit_text' => $item->baseUnit?->code ?: $item->unitOfMeasure?->code ?: '-',
                    'batches' => [],
                    'tracking' => [
                        'is_batch_tracked' => (bool) $item->is_batch_tracked,
                        'has_expiry_date' => (bool) $item->has_expiry_date,
                        'allow_negative_stock' => (bool) $item->allow_negative_stock,
                    ],
                ];

            return $info + [
                'id' => $item->id,
                'text' => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
                'meta_text' => 'Available: '.$this->formatQuantity($info['available_qty']).' '.$info['unit_text'],
                'sku' => $item->sku,
                'name' => $item->name,
            ];
        })->values());
    }

    public function itemInfo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'batch_no' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $warehouse = $this->accessibleWarehouse((int) $data['warehouse_id']);

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'You do not have access to this warehouse.',
            ]);
        }

        $item = Item::query()->with(['baseUnit:id,code,name', 'unitOfMeasure:id,code,name'])->findOrFail($data['item_id']);

        return response()->json($this->warehouseTransferPostingService->itemInfo(
            $item,
            $warehouse->id,
            $data['batch_no'] ?? null,
            $data['expiry_date'] ?? null
        ));
    }

    public function warehouseStats(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        if (blank($data['warehouse_id'] ?? null)) {
            return response()->json([
                'items_count' => 0,
                'total_qty' => 0.0,
                'total_qty_text' => '0.00',
                'last_movement_at' => null,
                'last_movement_text' => '-',
            ]);
        }

        $warehouseId = (int) $data['warehouse_id'];
        $warehouse = $this->accessibleWarehouse($warehouseId);

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'You do not have access to this warehouse.',
            ]);
        }

        $balances = StockBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('qty_on_hand', '>', 0);

        $lastMovement = StockMovement::query()
            ->where('warehouse_id', $warehouse->id)
            ->latest('created_at')
            ->value('created_at');

        return response()->json([
            'items_count' => (clone $balances)->distinct('item_id')->count('item_id'),
            'total_qty' => (float) (clone $balances)->sum('qty_on_hand'),
            'total_qty_text' => $this->formatQuantity((float) (clone $balances)->sum('qty_on_hand')),
            'last_movement_at' => $lastMovement,
            'last_movement_text' => $lastMovement ? \Illuminate\Support\Carbon::parse($lastMovement)->format('d M Y H:i') : '-',
        ]);
    }

    private function formView(WarehouseTransfer $record): View
    {
        $branches = $this->accessibleBranches();

        return view('inventory.warehouse_transfers.'.($record->exists ? 'edit' : 'create'), [
            'record' => $record,
            'companies' => Company::orderBy('name')->pluck('name', 'id')->all(),
            'branches' => $branches,
            'warehouses' => $this->warehouses(),
            'selectedItems' => $this->selectedItemOptions($record),
            'lineItemTracking' => $this->lineItemTracking($record),
        ]);
    }

    private function selectedItemOptions(WarehouseTransfer $record): array
    {
        $lines = session()->getOldInput('lines', $record->lines->toArray());
        $ids = collect($lines)->pluck('item_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Item::query()
            ->whereIn('id', $ids)
            ->get(['id', 'sku', 'name'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
            ])
            ->all();
    }

    private function lineItemTracking(WarehouseTransfer $record): array
    {
        $lines = session()->getOldInput('lines', $record->lines->toArray());
        $ids = collect($lines)->pluck('item_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Item::query()
            ->whereIn('id', $ids)
            ->get(['id', 'is_batch_tracked', 'has_expiry_date', 'allow_negative_stock'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => [
                    'is_batch_tracked' => (bool) $item->is_batch_tracked,
                    'has_expiry_date' => (bool) $item->has_expiry_date,
                    'allow_negative_stock' => (bool) $item->allow_negative_stock,
                ],
            ])
            ->all();
    }

    private function warehouses()
    {
        $branchIds = $this->accessibleBranches()->pluck('id');

        return Warehouse::query()
            ->with(['branch', 'company'])
            ->where('is_active', true)
            ->whereNotNull('branch_id')
            ->whereIn('branch_id', $branchIds)
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();
    }

    private function accessibleWarehouse(int $warehouseId): ?Warehouse
    {
        $branchIds = $this->accessibleBranches()->pluck('id');

        return Warehouse::query()
            ->whereKey($warehouseId)
            ->where('is_active', true)
            ->whereIn('branch_id', $branchIds)
            ->first();
    }

    private function accessibleBranches()
    {
        $query = Branch::query()->where('is_active', true)->orderBy('name');

        if (! Auth::user()?->isSuperAdmin()) {
            $query->whereHas('users', fn (Builder $branchQuery) => $branchQuery->whereKey(Auth::id()));
        }

        return $query->get();
    }

    private function ensureBranchAccess(int $branchId): void
    {
        if (Auth::user()?->isSuperAdmin()) {
            return;
        }

        if (! Auth::user()?->branches()->whereKey($branchId)->exists()) {
            throw ValidationException::withMessages([
                'branch_id' => 'You do not have access to this branch.',
            ]);
        }
    }

    private function statuses(): array
    {
        return collect(WarehouseTransfer::STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => str($status)->title()->toString()])
            ->all();
    }

    private function formatQuantity(float $quantity): string
    {
        return number_format($quantity, 2);
    }

    private function indexFilters(Request $request): array
    {
        $filters = $request->only(['keyword', 'date_from', 'date_to', 'company_id', 'branch_id', 'from_warehouse_id', 'to_warehouse_id', 'status']);

        if (! $request->has('date_from')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
        }

        if (! $request->has('date_to')) {
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }
}
