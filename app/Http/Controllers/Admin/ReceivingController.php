<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceivingController extends Controller
{
    public function index(): View
    {
        return view('purchase.receivings.index', [
            'records' => Receiving::with(['purchaseOrder', 'supplier', 'lines.warehouse'])->latest('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        $po = PurchaseOrder::with(['supplier', 'lines.item.defaultWarehouseType', 'lines.unit'])
            ->whereIn('status', ['approved', 'partially_received'])
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
        $record = DB::transaction(function () use ($request): Receiving {
            $data = $this->validated($request);
            $lines = $data['lines'];
            unset($data['lines']);

            $purchaseOrder = PurchaseOrder::with('lines')->lockForUpdate()->findOrFail($data['purchase_order_id']);
            $this->ensureReceivable($purchaseOrder);
            $legacyWarehouseId = $lines[0]['warehouse_id'] ?? null;

            $record = Receiving::create($data + [
                'number' => app(DocumentNumberService::class)->generate('RCV'),
                'supplier_id' => $purchaseOrder->supplier_id,
                'warehouse_id' => $legacyWarehouseId,
                'status' => 'draft',
            ]);

            $this->syncLines($record, $purchaseOrder, $lines);

            return $record;
        });

        return redirect()->route('receivings.show', $record)->with('status', 'Receiving dibuat.');
    }

    public function show(Receiving $record): View
    {
        return view('purchase.receivings.show', [
            'record' => $record->load(['purchaseOrder', 'supplier', 'lines.item', 'lines.unit', 'lines.warehouse.branch', 'lines.purchaseOrderLine']),
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
            $legacyWarehouseId = $lines[0]['warehouse_id'] ?? null;

            $record->update($data + ['supplier_id' => $purchaseOrder->supplier_id, 'warehouse_id' => $legacyWarehouseId]);
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

    public function post(Receiving $record): RedirectResponse
    {
        abort_if($record->status !== 'draft', 422, 'Only draft receiving can be posted.');

        DB::transaction(function () use ($record): void {
            $record->load(['purchaseOrder.lines', 'lines']);
            $purchaseOrder = PurchaseOrder::whereKey($record->purchase_order_id)->lockForUpdate()->firstOrFail();
            $this->ensureReceivable($purchaseOrder);

            foreach ($record->lines as $line) {
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

                StockMovement::create([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $line->warehouse_id,
                    'movement_type' => 'PURCHASE_RECEIVE',
                    'quantity_in' => $line->received_quantity,
                    'quantity_out' => 0,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => (float) $line->received_quantity * (float) $line->unit_cost,
                    'reference_type' => Receiving::class,
                    'reference_id' => $record->id,
                    'reference_number' => $record->number,
                    'movement_date' => $record->received_date,
                    'notes' => $line->notes,
                    'created_by' => Auth::id(),
                ]);

                $this->updateStockBalance($line->item_id, $line->warehouse_id, (float) $line->received_quantity, (float) $line->unit_cost, $record->received_date);
            }

            $record->update(['status' => 'posted']);
            $this->refreshPurchaseOrderStatus($purchaseOrder->fresh('lines'));
        });

        return back()->with('status', 'Receiving posted dan stock masuk tercatat.');
    }

    public function cancel(Receiving $record): RedirectResponse
    {
        abort_if($record->status === 'posted', 422, 'Posted receiving cannot be cancelled.');
        $record->update(['status' => 'cancelled']);

        return back()->with('status', 'Receiving cancelled.');
    }

    private function buildFromPo(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'lines.item.defaultWarehouseType', 'lines.unit']);
        $this->ensureReceivable($purchaseOrder);
        $branch = $this->currentBranch();

        $record = new Receiving([
            'purchase_order_id' => $purchaseOrder->id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'received_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $record->setRelation('lines', $purchaseOrder->lines->where('remaining_quantity', '>', 0)->map(function ($line) use ($branch) {
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
        return view('purchase.receivings.'.($record->exists ? 'edit' : 'create'), [
            'record' => $record,
            'purchaseOrders' => PurchaseOrder::with('supplier')->whereIn('status', ['approved', 'partially_received'])->orderBy('number')->get(),
            'warehouses' => Warehouse::with(['branch', 'warehouseType'])->where('is_active', true)->orderBy('branch_id')->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
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

    private function updateStockBalance(int $itemId, int $warehouseId, float $quantity, float $unitCost, mixed $movementDate): void
    {
        $balance = StockBalance::where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            StockBalance::create([
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'quantity_on_hand' => $quantity,
                'quantity_reserved' => 0,
                'average_cost' => $unitCost,
                'last_movement_at' => $movementDate,
            ]);

            return;
        }

        $oldQuantity = (float) $balance->quantity_on_hand;
        $newQuantity = $oldQuantity + $quantity;
        $averageCost = $newQuantity > 0
            ? (($oldQuantity * (float) $balance->average_cost) + ($quantity * $unitCost)) / $newQuantity
            : $unitCost;

        $balance->update([
            'quantity_on_hand' => $newQuantity,
            'average_cost' => $averageCost,
            'last_movement_at' => $movementDate,
        ]);
    }

    private function currentBranch(): ?Branch
    {
        return Branch::where('is_active', true)->orderBy('id')->first();
    }

    private function suggestWarehouseId(?\App\Models\Item $item, ?Branch $branch): ?int
    {
        $warehouseTypeId = $item?->default_warehouse_type_id;

        if (! $warehouseTypeId) {
            return Warehouse::where('is_active', true)->orderBy('id')->value('id');
        }

        $query = Warehouse::where('is_active', true)->where('warehouse_type_id', $warehouseTypeId);

        if ($branch) {
            $branchWarehouseId = (clone $query)->where('branch_id', $branch->id)->orderBy('id')->value('id');

            if ($branchWarehouseId) {
                return $branchWarehouseId;
            }
        }

        return $query->orderBy('id')->value('id');
    }

    private function refreshPurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $allReceived = $purchaseOrder->lines->every(fn ($line) => (float) $line->received_quantity >= (float) $line->quantity);
        $anyReceived = $purchaseOrder->lines->contains(fn ($line) => (float) $line->received_quantity > 0);

        $purchaseOrder->update([
            'status' => $allReceived ? 'fully_received' : ($anyReceived ? 'partially_received' : 'approved'),
        ]);
    }
}
