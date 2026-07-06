<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\DocumentSequenceService;
use App\Services\Inventory\InventoryPostingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceivingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->indexFilters($request);
        $branches = $this->accessibleBranches();
        $branchIds = $branches->pluck('id');

        return view('purchase.receivings.index', [
            'records' => Receiving::with(['purchaseOrder', 'supplier', 'branch', 'lines.warehouse.branch'])
                ->when(! Auth::user()?->isSuperAdmin(), fn ($query) => $query->where(function ($branchQuery) use ($branchIds): void {
                    $branchQuery->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
                }))
                ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                    $keyword = $filters['keyword'];

                    $query->where(function ($search) use ($keyword): void {
                        $search->where('number', 'like', '%'.$keyword.'%')
                            ->orWhere('supplier_delivery_number', 'like', '%'.$keyword.'%')
                            ->orWhereHas('purchaseOrder', fn ($purchaseOrder) => $purchaseOrder->where('number', 'like', '%'.$keyword.'%'))
                            ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$keyword.'%'));
                    });
                })
                ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('received_date', '>=', $filters['date_from']))
                ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('received_date', '<=', $filters['date_to']))
                ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
                ->when(filled($filters['branch_id'] ?? null), fn ($query) => $query->where('branch_id', $filters['branch_id']))
                ->when(filled($filters['supplier_id'] ?? null), fn ($query) => $query->where('supplier_id', $filters['supplier_id']))
                ->orderByDesc('received_date')
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => ['draft', 'posted', 'cancelled'],
            'branches' => $branches,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function create(): View
    {
        $branchIds = $this->accessibleBranches()->pluck('id');
        $po = PurchaseOrder::with(['supplier', 'lines.item.defaultWarehouseType', 'lines.unit'])
            ->whereIn('status', ['approved', 'partially_received'])
            ->when(! Auth::user()?->isSuperAdmin(), fn ($query) => $query->where(function ($branchQuery) use ($branchIds): void {
                $branchQuery->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            }))
            ->latest('id')
            ->first();

        return $po ? $this->buildFromPo($po) : $this->formView(new Receiving([
            'received_date' => now()->toDateString(),
            'status' => 'draft',
        ]));
    }

    public function createFromPo(PurchaseOrder $purchaseOrder): View
    {
        return $this->buildFromPo($purchaseOrder);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $record = DB::transaction(function () use ($request): Receiving {
                $data = $this->validated($request);
                $lines = $data['lines'];
                unset($data['lines']);

                $purchaseOrder = PurchaseOrder::with('lines')->lockForUpdate()->findOrFail($data['purchase_order_id']);
                $this->ensureReceivable($purchaseOrder);
                $this->ensureBranchAccess((int) $data['branch_id']);
                $this->ensurePurchaseOrderBranch($purchaseOrder, (int) $data['branch_id']);
                $branch = Branch::findOrFail($data['branch_id']);
                $legacyWarehouseId = $lines[0]['warehouse_id'] ?? null;

                $record = Receiving::create($data + [
                    'company_id' => $branch->company_id ?: $purchaseOrder->company_id,
                    'branch_id' => $branch->id,
                    'number' => app(DocumentSequenceService::class)->generate('GOODS_RECEIPT', $branch->company_id ?: $purchaseOrder->company_id, $branch->id),
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'warehouse_id' => $legacyWarehouseId,
                    'status' => 'draft',
                ]);

                $this->syncLines($record, $purchaseOrder, $lines);

                return $record;
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return back()
                    ->withInput()
                    ->withErrors(['number' => 'Nomor dokumen sudah digunakan. Silakan ulangi proses.']);
            }

            throw $exception;
        }

        return redirect()->route('receivings.show', $record)->with('status', 'Receiving dibuat.');
    }

    public function show(Receiving $record): View
    {
        return view('purchase.receivings.show', [
            'record' => $record->load(['purchaseOrder', 'supplier', 'branch', 'lines.item', 'lines.unit', 'lines.warehouse.branch', 'lines.purchaseOrderLine', 'approvalLogs.user']),
        ]);
    }

    public function edit(Receiving $record): View
    {
        abort_if($record->status !== 'draft', 422, 'Posted receiving cannot be edited.');

        return $this->formView($record->load(['purchaseOrder.lines', 'lines.warehouse']));
    }

    public function update(Request $request, Receiving $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Posted receiving cannot be edited.');

        DB::transaction(function () use ($request, $record): void {
            $data = $this->validated($request);
            $lines = $data['lines'];
            unset($data['lines']);

            $purchaseOrder = PurchaseOrder::with('lines')->lockForUpdate()->findOrFail($data['purchase_order_id']);
            $this->ensureReceivable($purchaseOrder);
            $this->ensureBranchAccess((int) $data['branch_id']);
            $this->ensurePurchaseOrderBranch($purchaseOrder, (int) $data['branch_id']);
            $branch = Branch::findOrFail($data['branch_id']);
            $legacyWarehouseId = $lines[0]['warehouse_id'] ?? null;

            $record->update($data + [
                'company_id' => $branch->company_id ?: $purchaseOrder->company_id,
                'branch_id' => $branch->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'warehouse_id' => $legacyWarehouseId,
            ]);
            $this->syncLines($record, $purchaseOrder, $lines);
        });

        return redirect()->route('receivings.show', $record)->with('status', 'Receiving diperbarui.');
    }

    public function destroy(Receiving $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft receiving can be deleted.');
        $record->delete();

        return redirect()->route('receivings.index')->with('status', 'Receiving dihapus.');
    }

    public function post(Receiving $record, InventoryPostingService $inventoryPostingService): RedirectResponse
    {
        DB::transaction(function () use ($record, $inventoryPostingService): void {
            $record = Receiving::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if($record->status !== 'draft', 422, 'Only draft receiving can be posted.');
            $this->ensureBranchAccess((int) $record->branch_id);

            $record->load(['purchaseOrder.lines', 'lines.item']);
            $purchaseOrder = PurchaseOrder::whereKey($record->purchase_order_id)->lockForUpdate()->firstOrFail();
            $this->ensureReceivable($purchaseOrder);

            foreach ($record->lines as $line) {
                $warehouse = Warehouse::whereKey($line->warehouse_id)
                    ->where('is_active', true)
                    ->where('branch_id', $record->branch_id)
                    ->first();

                if (! $warehouse) {
                    throw ValidationException::withMessages([
                        'lines' => 'Warehouse line must belong to the selected receiving branch.',
                    ]);
                }

                $poLine = $purchaseOrder->lines()->whereKey($line->purchase_order_line_id)->lockForUpdate()->firstOrFail();
                $remaining = (float) $poLine->quantity - (float) $poLine->received_quantity;

                if ((float) $line->received_quantity <= 0 || (float) $line->received_quantity > $remaining) {
                    throw ValidationException::withMessages([
                        'lines' => 'Receiving quantity cannot exceed remaining PO quantity.',
                    ]);
                }

                $newReceivedQuantity = (float) $poLine->received_quantity + (float) $line->received_quantity;
                $poLine->update([
                    'received_quantity' => $newReceivedQuantity,
                    'remaining_quantity' => max(0, (float) $poLine->quantity - $newReceivedQuantity),
                ]);
            }

            $inventoryPostingService->postReceive($record);
            $record->approvalLogs()->create([
                'action' => 'post',
                'user_id' => Auth::id(),
            ]);
            $this->refreshPurchaseOrderStatus($purchaseOrder->fresh('lines'));
        });

        return back()->with('status', 'Receiving posted dan stock masuk tercatat.');
    }

    public function cancel(Receiving $record): RedirectResponse
    {
        if ($record->status === 'posted') {
            return back()->withErrors([
                'status' => 'Receiving yang sudah posted tidak dapat dibatalkan karena stok sudah masuk. Buat fitur reversal terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($record): void {
            $record = Receiving::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if($record->status !== 'draft', 422, 'Only draft receiving can be cancelled.');
            $record->update(['status' => 'cancelled']);
            $record->approvalLogs()->create([
                'action' => 'cancel',
                'user_id' => Auth::id(),
            ]);
        });

        return back()->with('status', 'Receiving cancelled.');
    }

    private function buildFromPo(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'lines.item.defaultWarehouseType', 'lines.unit']);
        $this->ensureReceivable($purchaseOrder);
        if ($purchaseOrder->branch_id) {
            $this->ensureBranchAccess((int) $purchaseOrder->branch_id);
        }
        $branch = $this->defaultReceivingBranch($purchaseOrder);

        $record = new Receiving([
            'purchase_order_id' => $purchaseOrder->id,
            'company_id' => $branch?->company_id ?: $purchaseOrder->company_id,
            'branch_id' => $branch?->id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'received_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $receivableLines = $purchaseOrder->lines->where('remaining_quantity', '>', 0)->values();
        abort_if($receivableLines->isEmpty(), 422, 'No remaining PO quantity can be received.');

        $record->setRelation('lines', $receivableLines->map(function ($line) use ($branch) {
            return new \App\Models\ReceivingLine([
                'purchase_order_line_id' => $line->id,
                'item_id' => $line->item_id,
                'description' => $line->description ?: $line->item?->name,
                'ordered_quantity' => $line->quantity,
                'previously_received_quantity' => $line->received_quantity,
                'received_quantity' => $line->remaining_quantity,
                'remaining_quantity' => 0,
                'warehouse_id' => $this->suggestWarehouseId($line->item, $branch),
                'unit_id' => $line->unit_id,
                'unit_cost' => $line->unit_price,
            ]);
        }));

        return $this->formView($record);
    }

    private function formView(Receiving $record): View
    {
        $branches = $this->accessibleBranches();
        $selectedBranchId = (int) old('branch_id', $record->branch_id ?: ($branches->count() === 1 ? $branches->first()->id : 0));
        $accessibleBranchIds = $branches->pluck('id');

        return view('purchase.receivings.'.($record->exists ? 'edit' : 'create'), [
            'record' => $record,
            'selectedPo' => $this->selectedPurchaseOrderOption($record),
            'selectedItems' => $this->selectedItemOptions($record),
            'lineItemWarehouseTypes' => $this->lineItemWarehouseTypes($record),
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId ?: null,
            'warehouses' => Warehouse::with(['branch', 'warehouseType'])
                ->where('is_active', true)
                ->whereNotNull('branch_id')
                ->whereIn('branch_id', $accessibleBranchIds)
                ->orderBy('branch_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function selectedPurchaseOrderOption(Receiving $record): array
    {
        $purchaseOrderId = session()->getOldInput('purchase_order_id', $record->purchase_order_id);

        if (! $purchaseOrderId) {
            return [];
        }

        $purchaseOrder = PurchaseOrder::with('supplier:id,name')->find($purchaseOrderId);

        if (! $purchaseOrder) {
            return [];
        }

        return [
            'id' => $purchaseOrder->id,
            'text' => trim($purchaseOrder->number.' - '.$purchaseOrder->supplier?->name),
        ];
    }

    private function selectedItemOptions(Receiving $record): array
    {
        $lines = session()->getOldInput('lines', $record->lines->toArray());
        $ids = collect($lines)->pluck('item_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Item::whereIn('id', $ids)
            ->get(['id', 'sku', 'name'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
            ])
            ->all();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'received_date' => ['required', 'date'],
            'supplier_delivery_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'exists:purchase_order_lines,id'],
            'lines.*.warehouse_id' => ['required', 'exists:warehouses,id'],
            'lines.*.received_quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);
    }

    private function syncLines(Receiving $record, PurchaseOrder $purchaseOrder, array $lines): void
    {
        $record->lines()->delete();

        foreach ($lines as $line) {
            $poLine = $purchaseOrder->lines->firstWhere('id', (int) $line['purchase_order_line_id']);
            abort_if(! $poLine, 422, 'Receiving line must come from selected purchase order.');
            $warehouse = Warehouse::whereKey($line['warehouse_id'])
                ->where('is_active', true)
                ->whereNotNull('branch_id')
                ->first();

            if (! $warehouse || (int) $warehouse->branch_id !== (int) $record->branch_id) {
                throw ValidationException::withMessages([
                    'lines' => 'Warehouse line must belong to the selected receiving branch.',
                ]);
            }

            $remaining = (float) $poLine->quantity - (float) $poLine->received_quantity;
            if ((float) $line['received_quantity'] > $remaining) {
                throw ValidationException::withMessages([
                    'lines' => 'Receiving quantity cannot exceed remaining PO quantity.',
                ]);
            }

            $record->lines()->create([
                'purchase_order_line_id' => $poLine->id,
                'item_id' => $poLine->item_id,
                'description' => $poLine->description,
                'ordered_quantity' => $poLine->quantity,
                'previously_received_quantity' => $poLine->received_quantity,
                'received_quantity' => $line['received_quantity'],
                'remaining_quantity' => $remaining - (float) $line['received_quantity'],
                'warehouse_id' => $line['warehouse_id'],
                'unit_id' => $poLine->unit_id,
                'unit_cost' => $line['unit_cost'],
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }

    private function ensureReceivable(PurchaseOrder $purchaseOrder): void
    {
        abort_if(! in_array($purchaseOrder->status, ['approved', 'partially_received'], true), 422, 'PO can only be received when approved or partially received.');
    }

    private function accessibleBranches(): \Illuminate\Support\Collection
    {
        $query = Branch::where('is_active', true)->orderBy('name');

        if (! Auth::user()?->isSuperAdmin()) {
            $query->whereHas('users', fn ($branchQuery) => $branchQuery->whereKey(Auth::id()));
        }

        return $query->get();
    }

    private function defaultReceivingBranch(PurchaseOrder $purchaseOrder): ?Branch
    {
        $branches = $this->accessibleBranches();

        if ($purchaseOrder->branch_id && $branches->contains('id', $purchaseOrder->branch_id)) {
            return $branches->firstWhere('id', $purchaseOrder->branch_id);
        }

        return $branches->count() === 1 ? $branches->first() : null;
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

    private function ensurePurchaseOrderBranch(PurchaseOrder $purchaseOrder, int $branchId): void
    {
        if ($purchaseOrder->branch_id && (int) $purchaseOrder->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'Receiving branch must match the purchase order branch.',
            ]);
        }
    }

    private function lineItemWarehouseTypes(Receiving $record): array
    {
        $lines = session()->getOldInput('lines', $record->lines->toArray());
        $ids = collect($lines)->pluck('item_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Item::whereIn('id', $ids)
            ->pluck('default_warehouse_type_id', 'id')
            ->all();
    }

    private function suggestWarehouseId(?Item $item, ?Branch $branch): ?int
    {
        $warehouseTypeId = $item?->default_warehouse_type_id;

        if (! $warehouseTypeId || ! $branch) {
            return null;
        }

        return Warehouse::where('is_active', true)
            ->where('branch_id', $branch->id)
            ->where('warehouse_type_id', $warehouseTypeId)
            ->orderBy('id')
            ->value('id');
    }

    private function refreshPurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $allReceived = $purchaseOrder->lines->every(fn ($line) => (float) $line->received_quantity >= (float) $line->quantity);
        $anyReceived = $purchaseOrder->lines->contains(fn ($line) => (float) $line->received_quantity > 0);

        $purchaseOrder->update([
            'status' => $allReceived ? 'fully_received' : ($anyReceived ? 'partially_received' : 'approved'),
        ]);
    }

    private function indexFilters(Request $request): array
    {
        $filters = $request->only(['keyword', 'date_from', 'date_to', 'status', 'branch_id', 'supplier_id']);

        if (! $request->has('date_from')) {
            $filters['date_from'] = now()->startOfMonth()->toDateString();
        }

        if (! $request->has('date_to')) {
            $filters['date_to'] = now()->toDateString();
        }

        return $filters;
    }
}
