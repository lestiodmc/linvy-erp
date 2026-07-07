<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Models\Inventory\StockBatchBalance;
use App\Models\Branch;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\Inventory\StockAdjustmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $stockAdjustmentService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->indexFilters($request);
        $branches = $this->accessibleBranches();
        $branchIds = $branches->pluck('id');

        return view('inventory.stock_adjustments.index', [
            'records' => StockAdjustment::query()
                ->with(['warehouse', 'branch', 'createdBy', 'lines'])
                ->withCount('lines')
                ->when(! Auth::user()?->isSuperAdmin(), fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
                ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('adjustment_date', '>=', $filters['date_from']))
                ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('adjustment_date', '<=', $filters['date_to']))
                ->when(filled($filters['branch_id'] ?? null), fn (Builder $query) => $query->where('branch_id', $filters['branch_id']))
                ->when(filled($filters['warehouse_id'] ?? null), fn (Builder $query) => $query->where('warehouse_id', $filters['warehouse_id']))
                ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
                ->when(filled($filters['keyword'] ?? null), function (Builder $query) use ($filters): void {
                    $keyword = $filters['keyword'];

                    $query->where(function (Builder $search) use ($keyword): void {
                        $search->where('number', 'like', '%'.$keyword.'%')
                            ->orWhere('reason', 'like', '%'.$keyword.'%');
                    });
                })
                ->orderByDesc('adjustment_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => StockAdjustment::STATUSES,
            'branches' => $branches,
            'warehouses' => $this->warehouses(),
        ]);
    }

    public function create(): View
    {
        $branches = $this->accessibleBranches();

        return $this->formView(new StockAdjustment([
            'adjustment_date' => now()->toDateString(),
            'status' => StockAdjustment::STATUS_DRAFT,
            'branch_id' => $branches->count() === 1 ? $branches->first()->id : null,
        ]));
    }

    public function store(StockAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->ensureBranchAccess((int) $data['branch_id']);
        $record = $this->stockAdjustmentService->create($data);

        if ($request->input('action') === 'post') {
            $this->stockAdjustmentService->post($record);

            return redirect()->route('stock-adjustments.show', $record)->with('status', 'Stock adjustment posted.');
        }

        return redirect()->route('stock-adjustments.show', $record)->with('status', 'Stock adjustment saved as draft.');
    }

    public function show(StockAdjustment $record): View
    {
        $this->ensureBranchAccess((int) $record->branch_id);

        return view('inventory.stock_adjustments.show', [
            'record' => $record->load(['branch', 'warehouse', 'createdBy', 'postedBy', 'lines.item', 'lines.uom', 'lines.unit']),
        ]);
    }

    public function edit(StockAdjustment $record): View
    {
        $this->ensureBranchAccess((int) $record->branch_id);
        abort_if($record->status !== StockAdjustment::STATUS_DRAFT, 422, 'Posted adjustment cannot be edited.');

        return $this->formView($record->load(['lines.item', 'lines.uom', 'lines.unit']));
    }

    public function update(StockAdjustmentRequest $request, StockAdjustment $record): RedirectResponse
    {
        $data = $request->validated();

        $this->ensureBranchAccess((int) $record->branch_id);
        $this->ensureBranchAccess((int) $data['branch_id']);
        $record = $this->stockAdjustmentService->update($record, $data);

        if ($request->input('action') === 'post') {
            $this->stockAdjustmentService->post($record);

            return redirect()->route('stock-adjustments.show', $record)->with('status', 'Stock adjustment posted.');
        }

        return redirect()->route('stock-adjustments.show', $record)->with('status', 'Stock adjustment updated.');
    }

    public function destroy(StockAdjustment $record): RedirectResponse
    {
        $this->ensureBranchAccess((int) $record->branch_id);
        abort_if($record->status !== StockAdjustment::STATUS_DRAFT, 422, 'Only draft adjustment can be deleted.');
        $record->delete();

        return redirect()->route('stock-adjustments.index')->with('status', 'Stock adjustment deleted.');
    }

    public function post(StockAdjustment $record): RedirectResponse
    {
        $this->ensureBranchAccess((int) $record->branch_id);
        $this->stockAdjustmentService->post($record);

        return back()->with('status', 'Stock adjustment posted.');
    }

    public function cancel(StockAdjustment $record): RedirectResponse
    {
        $this->ensureBranchAccess((int) $record->branch_id);
        $this->stockAdjustmentService->cancel($record);

        return back()->with('status', 'Stock adjustment cancelled.');
    }

    public function itemInfo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'batch_no' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $item = Item::query()
            ->with(['baseUnit:id,code,name', 'unitOfMeasure:id,code,name', 'category:id,name', 'brand:id,name'])
            ->findOrFail($data['item_id']);
        $warehouse = Warehouse::query()->with('branch:id,name')->findOrFail($data['warehouse_id']);

        if (! (bool) $item->track_inventory) {
            throw ValidationException::withMessages([
                'item_id' => 'Non inventory item cannot be adjusted.',
            ]);
        }

        $currentStock = $this->stockAdjustmentService->currentStockQty($item, (int) $data['warehouse_id'], $data['batch_no'] ?? null, $data['expiry_date'] ?? null);
        $batches = $this->batchOptions($item, (int) $data['warehouse_id']);

        return response()->json([
            'sku' => $item->sku,
            'name' => $item->name,
            'text' => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
            'current_stock' => $currentStock,
            'system_qty' => $currentStock,
            'uom_id' => $item->base_unit_id ?: $item->unit_of_measure_id,
            'uom' => $item->baseUnit?->code ?: $item->unitOfMeasure?->code ?: $item->unitOfMeasure?->name,
            'uom_text' => $item->baseUnit?->code ?: $item->unitOfMeasure?->code ?: $item->unitOfMeasure?->name,
            'warehouse' => trim(($warehouse->branch?->name ? $warehouse->branch->name.' - ' : '').$warehouse->name),
            'category' => $item->category?->name,
            'brand' => $item->brand?->name,
            'batches' => $batches,
            'tracking' => [
                'track_inventory' => (bool) $item->track_inventory,
                'is_batch_tracked' => (bool) $item->is_batch_tracked,
                'is_serial_tracked' => (bool) $item->is_serial_tracked,
                'has_expiry_date' => (bool) $item->has_expiry_date,
                'allow_negative_stock' => (bool) $item->allow_negative_stock,
            ],
            'batch' => (bool) $item->is_batch_tracked,
            'serial' => (bool) $item->is_serial_tracked,
            'expiry' => (bool) $item->has_expiry_date,
        ]);
    }

    public function currentStock(Request $request): JsonResponse
    {
        return $this->itemInfo($request);
    }

    public function items(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $warehouseId = $request->filled('warehouse_id') ? (int) $request->query('warehouse_id') : null;

        $items = Item::query()
            ->with([
                'baseUnit:id,code,name',
                'unitOfMeasure:id,code,name',
                'category:id,name',
                'brand:id,name',
            ])
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($search): void {
                $query->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            })
            ->orderBy('sku')
            ->limit(20)
            ->get(['id', 'sku', 'name', 'item_category_id', 'brand_id', 'unit_of_measure_id', 'base_unit_id', 'is_batch_tracked', 'is_serial_tracked', 'has_expiry_date', 'allow_negative_stock']);

        return response()->json($items->map(fn (Item $item): array => [
            'id' => $item->id,
            'text' => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
            'sku' => $item->sku,
            'name' => $item->name,
            'category' => $item->category?->name,
            'brand' => $item->brand?->name,
            'available_qty' => $warehouseId ? $this->stockAdjustmentService->currentStockQty($item, $warehouseId) : 0.0,
            'batches' => $warehouseId ? $this->batchOptions($item, $warehouseId) : [],
            'unit_id' => $item->base_unit_id ?: $item->unit_of_measure_id,
            'unit_text' => $item->baseUnit?->code ?: $item->unitOfMeasure?->code,
            'tracking' => [
                'is_batch_tracked' => (bool) $item->is_batch_tracked,
                'is_serial_tracked' => (bool) $item->is_serial_tracked,
                'has_expiry_date' => (bool) $item->has_expiry_date,
                'allow_negative_stock' => (bool) $item->allow_negative_stock,
            ],
        ])->values());
    }

    private function batchOptions(Item $item, int $warehouseId): array
    {
        if (! (bool) $item->is_batch_tracked) {
            return [];
        }

        $batches = StockBatchBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->orderBy('expiry_date')
            ->orderBy('batch_no')
            ->get(['batch_no', 'expiry_date', 'qty_on_hand', 'qty_available'])
            ->map(fn (StockBatchBalance $batch): array => [
                'batch_no' => $batch->batch_no,
                'label' => $batch->batch_no.($batch->expiry_date ? ' - Exp '.$batch->expiry_date->format('Y-m-d') : ''),
                'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                'qty_on_hand' => (float) $batch->qty_on_hand,
                'qty_available' => (float) $batch->qty_available,
            ])
            ->values();

        $totalQty = $this->stockAdjustmentService->currentStockQty($item, $warehouseId);
        $knownBatchQty = $batches->sum('qty_on_hand');
        $legacyNoBatchQty = max(0, $totalQty - $knownBatchQty);

        if ($legacyNoBatchQty > 0.000001) {
            $batches->prepend([
                'batch_no' => 'NO_BATCH',
                'label' => 'NO_BATCH - Legacy unbatched stock',
                'expiry_date' => null,
                'qty_on_hand' => $legacyNoBatchQty,
                'qty_available' => $legacyNoBatchQty,
            ]);
        }

        return $batches->all();
    }

    private function formView(StockAdjustment $record): View
    {
        return view('inventory.stock_adjustments.'.($record->exists ? 'edit' : 'create'), [
            'record' => $record,
            'branches' => $this->accessibleBranches(),
            'warehouses' => $this->warehouses(),
            'selectedItems' => $this->selectedItemOptions($record),
            'lineItemTracking' => $this->lineItemTracking($record),
        ]);
    }

    private function selectedItemOptions(StockAdjustment $record): array
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

    private function lineItemTracking(StockAdjustment $record): array
    {
        $lines = session()->getOldInput('lines', $record->lines->toArray());
        $ids = collect($lines)->pluck('item_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Item::query()
            ->whereIn('id', $ids)
            ->get(['id', 'is_batch_tracked', 'is_serial_tracked', 'has_expiry_date', 'allow_negative_stock'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => [
                    'is_batch_tracked' => (bool) $item->is_batch_tracked,
                    'is_serial_tracked' => (bool) $item->is_serial_tracked,
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
            ->with(['branch', 'warehouseType'])
            ->where('is_active', true)
            ->whereNotNull('branch_id')
            ->whereIn('branch_id', $branchIds)
            ->orderBy('branch_id')
            ->orderBy('name')
            ->get();
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

    private function indexFilters(Request $request): array
    {
        $filters = $request->only(['keyword', 'date_from', 'date_to', 'branch_id', 'warehouse_id', 'status']);

        if (! $request->has('date_from')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
        }

        if (! $request->has('date_to')) {
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }
}
