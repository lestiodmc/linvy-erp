<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
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
use InvalidArgumentException;
use RuntimeException;

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
            'statuses' => Receiving::STATUSES,
            'branches' => $branches,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function create(): View
    {
        $branchIds = $this->accessibleBranches()->pluck('id');
        $po = PurchaseOrder::with(['supplier', 'lines.item.defaultWarehouseType', 'lines.unit'])
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->when(! Auth::user()?->isSuperAdmin(), fn ($query) => $query->where(function ($branchQuery) use ($branchIds): void {
                $branchQuery->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            }))
            ->latest('id')
            ->first();

        return $po ? $this->buildFromPo($po) : $this->formView(new Receiving([
            'received_date' => now()->toDateString(),
            'status' => Receiving::STATUS_DRAFT,
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

                $purchaseOrder = PurchaseOrder::with('lines.item')->lockForUpdate()->findOrFail($data['purchase_order_id']);
                $this->ensureReceivable($purchaseOrder);
                $this->ensureBranchAccess((int) $data['branch_id']);
                $this->ensurePurchaseOrderBranch($purchaseOrder, (int) $data['branch_id']);
                $branch = Branch::findOrFail($data['branch_id']);
                $legacyWarehouseId = collect($lines)->pluck('warehouse_id')->filter()->first();

                $record = Receiving::create($data + [
                    'company_id' => $branch->company_id ?: $purchaseOrder->company_id,
                    'branch_id' => $branch->id,
                    'number' => app(DocumentSequenceService::class)->generate('GOODS_RECEIPT', $branch->company_id ?: $purchaseOrder->company_id, $branch->id),
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'warehouse_id' => $legacyWarehouseId,
                    'status' => Receiving::STATUS_DRAFT,
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
        abort_if($record->status !== Receiving::STATUS_DRAFT, 422, 'Posted receiving cannot be edited.');

        return $this->formView($record->load(['purchaseOrder.lines.item', 'lines.warehouse']));
    }

    public function update(Request $request, Receiving $record): RedirectResponse
    {
        abort_if($record->status !== Receiving::STATUS_DRAFT, 422, 'Posted receiving cannot be edited.');

        DB::transaction(function () use ($request, $record): void {
            $data = $this->validated($request);
            $lines = $data['lines'];
            unset($data['lines']);

            $purchaseOrder = PurchaseOrder::with('lines.item')->lockForUpdate()->findOrFail($data['purchase_order_id']);
            $this->ensureReceivable($purchaseOrder);
            $this->ensureBranchAccess((int) $data['branch_id']);
            $this->ensurePurchaseOrderBranch($purchaseOrder, (int) $data['branch_id']);
            $branch = Branch::findOrFail($data['branch_id']);
            $legacyWarehouseId = collect($lines)->pluck('warehouse_id')->filter()->first();

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
        abort_if($record->status !== Receiving::STATUS_DRAFT, 422, 'Only draft receiving can be deleted.');
        $record->delete();

        return redirect()->route('receivings.index')->with('status', 'Receiving dihapus.');
    }

    public function post(Receiving $record, InventoryPostingService $inventoryPostingService): RedirectResponse
    {
        try {
            DB::transaction(function () use ($record, $inventoryPostingService): void {
                $record = Receiving::whereKey($record->id)->lockForUpdate()->firstOrFail();
                abort_if($record->status !== Receiving::STATUS_DRAFT, 422, 'Only draft receiving can be posted.');
                $this->ensureBranchAccess((int) $record->branch_id);

                $record->load(['purchaseOrder.lines', 'lines.item']);
                $purchaseOrder = PurchaseOrder::whereKey($record->purchase_order_id)->lockForUpdate()->firstOrFail();
                $this->ensureReceivable($purchaseOrder);

                foreach ($record->lines as $line) {
                    if ((bool) ($line->item?->track_inventory ?? true)) {
                        $warehouse = Warehouse::whereKey($line->warehouse_id)
                            ->where('is_active', true)
                            ->where('branch_id', $record->branch_id)
                            ->first();

                        if (! $warehouse) {
                            throw ValidationException::withMessages([
                                'lines' => 'Warehouse line must belong to the selected receiving branch.',
                            ]);
                        }
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
        } catch (InvalidArgumentException|RuntimeException $exception) {
            throw ValidationException::withMessages([
                'inventory' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Receiving posted dan stock masuk tercatat.');
    }

    public function cancel(Receiving $record): RedirectResponse
    {
        if ($record->status === Receiving::STATUS_POSTED) {
            return back()->withErrors([
                'status' => 'Receiving yang sudah posted tidak dapat dibatalkan karena stok sudah masuk. Buat fitur reversal terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($record): void {
            $record = Receiving::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if($record->status !== Receiving::STATUS_DRAFT, 422, 'Only draft receiving can be cancelled.');
            $record->update(['status' => Receiving::STATUS_CANCELLED]);
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
            'status' => Receiving::STATUS_DRAFT,
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
                'warehouse_id' => (bool) ($line->item?->track_inventory ?? true) ? $this->suggestWarehouseId($line->item, $branch) : null,
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
            'lineItemTracking' => $this->lineItemTracking($record),
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

        $purchaseOrder = PurchaseOrder::with(['supplier:id,name', 'branch:id,name'])->find($purchaseOrderId);

        if (! $purchaseOrder) {
            return [];
        }

        return [
            'id' => $purchaseOrder->id,
            'text' => trim($purchaseOrder->number.' - '.$purchaseOrder->supplier?->name),
            'number' => $purchaseOrder->number,
            'status' => $purchaseOrder->status,
            'supplier' => $purchaseOrder->supplier?->name,
            'branch' => $purchaseOrder->branch?->name,
            'order_date' => $purchaseOrder->order_date?->format('d M Y'),
            'expected_date' => $purchaseOrder->expected_date?->format('d M Y'),
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
        $data = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'received_date' => ['required', 'date'],
            'supplier_delivery_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'exists:purchase_order_lines,id'],
            'lines.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'lines.*.received_quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'lines.*.batch_no' => ['nullable', 'string', 'max:255'],
            'lines.*.serial_numbers' => ['nullable', 'string'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $this->validateReceivingLineRules($data);

        return $data;
    }

    private function syncLines(Receiving $record, PurchaseOrder $purchaseOrder, array $lines): void
    {
        $record->lines()->delete();

        foreach ($lines as $line) {
            $poLine = $purchaseOrder->lines->firstWhere('id', (int) $line['purchase_order_line_id']);
            abort_if(! $poLine, 422, 'Receiving line must come from selected purchase order.');
            $item = $poLine->item;
            $tracksInventory = (bool) ($item?->track_inventory ?? true);

            if ($tracksInventory) {
                $warehouse = Warehouse::whereKey($line['warehouse_id'] ?? null)
                    ->where('is_active', true)
                    ->whereNotNull('branch_id')
                    ->first();

                if (! $warehouse || (int) $warehouse->branch_id !== (int) $record->branch_id) {
                    throw ValidationException::withMessages([
                        'lines' => 'Warehouse line must belong to the selected receiving branch.',
                    ]);
                }
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
                'warehouse_id' => $tracksInventory ? ($line['warehouse_id'] ?? null) : null,
                'unit_id' => $poLine->unit_id,
                'unit_cost' => $line['unit_cost'],
                'batch_no' => $tracksInventory && (bool) ($item?->is_batch_tracked ?? false) ? ($line['batch_no'] ?? null) : null,
                'serial_numbers' => $tracksInventory && (bool) ($item?->is_serial_tracked ?? false) ? ($line['serial_numbers'] ?? null) : null,
                'expiry_date' => $tracksInventory && (bool) ($item?->has_expiry_date ?? false) ? ($line['expiry_date'] ?? null) : null,
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }

    private function validateReceivingLineRules(array $data): void
    {
        $poLineIds = collect($data['lines'])->pluck('purchase_order_line_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $poLines = PurchaseOrderLine::with('item')
            ->whereIn('id', $poLineIds)
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($data['lines'] as $index => $line) {
            $poLine = $poLines->get((int) $line['purchase_order_line_id']);
            $item = $poLine?->item;
            $sku = $item?->sku ?: 'selected item';

            if (! (bool) ($item?->track_inventory ?? true)) {
                continue;
            }

            if (blank($line['warehouse_id'] ?? null)) {
                $errors["lines.$index.warehouse_id"] = 'Warehouse is required for inventory item '.$sku.'.';
            }

            if ((bool) ($item?->is_batch_tracked ?? false) && blank($line['batch_no'] ?? null)) {
                $errors["lines.$index.batch_no"] = 'Batch number is required for batch tracked item '.$sku.'.';
            }

            if ((bool) ($item?->has_expiry_date ?? false) && blank($line['expiry_date'] ?? null)) {
                $errors["lines.$index.expiry_date"] = 'Expiry date is required for expiry tracked item '.$sku.'.';
            }

            if (! (bool) ($item?->is_serial_tracked ?? false)) {
                continue;
            }

            $serialNumbers = $this->serialNumbers($line['serial_numbers'] ?? null);

            if ($serialNumbers === []) {
                $errors["lines.$index.serial_numbers"] = 'Serial numbers are required for serial tracked item '.$sku.'.';

                continue;
            }

            if (count($serialNumbers) !== count(array_unique($serialNumbers))) {
                $errors["lines.$index.serial_numbers"] = 'Serial numbers must be unique for item '.$sku.'.';

                continue;
            }

            if (abs((float) ($line['received_quantity'] ?? 0) - count($serialNumbers)) > 0.000001) {
                $errors["lines.$index.serial_numbers"] = 'Receiving quantity must match serial number count for item '.$sku.'.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function serialNumbers(?string $serialNumbers): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $serialNumbers))
            ->map(fn (string $serialNumber): string => trim($serialNumber))
            ->filter()
            ->values()
            ->all();
    }

    private function ensureReceivable(PurchaseOrder $purchaseOrder): void
    {
        abort_if(! in_array($purchaseOrder->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true), 422, 'PO can only be received when approved or partially received.');
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

    private function lineItemTracking(Receiving $record): array
    {
        $lines = session()->getOldInput('lines', $record->lines->toArray());
        $ids = collect($lines)->pluck('item_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Item::whereIn('id', $ids)
            ->get(['id', 'track_inventory', 'is_batch_tracked', 'is_serial_tracked', 'has_expiry_date'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => [
                    'track_inventory' => (bool) $item->track_inventory,
                    'is_batch_tracked' => (bool) $item->is_batch_tracked,
                    'is_serial_tracked' => (bool) $item->is_serial_tracked,
                    'has_expiry_date' => (bool) $item->has_expiry_date,
                ],
            ])
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
            'status' => $allReceived ? PurchaseOrder::STATUS_FULLY_RECEIVED : ($anyReceived ? PurchaseOrder::STATUS_PARTIALLY_RECEIVED : PurchaseOrder::STATUS_APPROVED),
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
