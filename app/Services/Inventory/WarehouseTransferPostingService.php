<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockBatchBalance;
use App\Models\Inventory\StockMovement;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WarehouseTransferPostingService
{
    public function __construct(private readonly InventoryPostingService $inventoryPostingService)
    {
    }

    public function create(array $data): WarehouseTransfer
    {
        return DB::transaction(function () use ($data): WarehouseTransfer {
            $branch = Branch::query()->findOrFail($data['branch_id']);
            $this->ensureCompanyBranch((int) $data['company_id'], $branch);
            $fromWarehouse = $this->warehouseForBranch((int) $data['from_warehouse_id'], $branch->id, 'from_warehouse_id');
            $toWarehouse = $this->warehouseForBranch((int) $data['to_warehouse_id'], $branch->id, 'to_warehouse_id');
            $this->ensureDifferentWarehouses($fromWarehouse, $toWarehouse);

            $record = WarehouseTransfer::query()->create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'number' => app(DocumentSequenceService::class)->generate('WAREHOUSE_TRANSFER', $branch->company_id, $branch->id),
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'transfer_date' => $data['transfer_date'],
                'status' => WarehouseTransfer::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($record, $data['lines']);

            return $record;
        });
    }

    public function update(WarehouseTransfer $record, array $data): WarehouseTransfer
    {
        return DB::transaction(function () use ($record, $data): WarehouseTransfer {
            $record = WarehouseTransfer::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->ensureDraft($record);

            $branch = Branch::query()->findOrFail($data['branch_id']);
            $this->ensureCompanyBranch((int) $data['company_id'], $branch);
            $fromWarehouse = $this->warehouseForBranch((int) $data['from_warehouse_id'], $branch->id, 'from_warehouse_id');
            $toWarehouse = $this->warehouseForBranch((int) $data['to_warehouse_id'], $branch->id, 'to_warehouse_id');
            $this->ensureDifferentWarehouses($fromWarehouse, $toWarehouse);

            $record->update([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($record, $data['lines']);

            return $record;
        });
    }

    public function post(WarehouseTransfer $record): WarehouseTransfer
    {
        try {
            return DB::transaction(function () use ($record): WarehouseTransfer {
                $record = WarehouseTransfer::query()
                    ->with(['lines.item'])
                    ->whereKey($record->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureDraft($record);

                if (
                    StockMovement::query()
                        ->whereIn('transaction_type', [StockMovement::TRANSACTION_TRF_IN, StockMovement::TRANSACTION_TRF_OUT])
                        ->where('transaction_id', $record->id)
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'inventory' => 'Warehouse transfer has already created stock movements.',
                    ]);
                }

                if ($record->lines->isEmpty()) {
                    throw ValidationException::withMessages(['lines' => 'At least one transfer line is required.']);
                }

                $this->validateLines($record, $record->lines);

                foreach ($record->lines as $line) {
                    $item = $line->item;
                    $qty = (float) $line->quantity;
                    $unitCost = (float) ($item->standard_cost ?? 0);
                    $uomId = $line->unit_of_measure_id ?: $item->base_unit_id ?: $item->unit_of_measure_id;
                    $baseUomId = $item->base_unit_id ?: $item->unit_of_measure_id;
                    $batchNo = ((bool) $item->is_batch_tracked || (bool) $item->has_expiry_date) && blank($line->batch_no)
                        ? 'NO_BATCH'
                        : $line->batch_no;

                    $baseMovement = [
                        'company_id' => $record->company_id,
                        'branch_id' => $record->branch_id,
                        'item_id' => $line->item_id,
                        'uom_id' => $uomId,
                        'base_uom_id' => $baseUomId,
                        'transaction_id' => $record->id,
                        'transaction_number' => $record->number,
                        'transaction_date' => $record->transfer_date,
                        'qty' => $qty,
                        'base_qty' => $qty,
                        'unit_cost' => $unitCost,
                        'total_cost' => $qty * $unitCost,
                        'batch_no' => $batchNo,
                        'expiry_date' => $line->expiry_date,
                        'reference_type' => WarehouseTransfer::class,
                        'reference_id' => $record->id,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];

                    $out = $baseMovement + [
                        'warehouse_id' => $record->from_warehouse_id,
                        'transaction_type' => StockMovement::TRANSACTION_TRF_OUT,
                        'movement_type' => StockMovement::MOVEMENT_OUT,
                        'remarks' => 'Transfer to '.$record->toWarehouse?->name,
                    ];

                    $in = $baseMovement + [
                        'warehouse_id' => $record->to_warehouse_id,
                        'transaction_type' => StockMovement::TRANSACTION_TRF_IN,
                        'movement_type' => StockMovement::MOVEMENT_IN,
                        'remarks' => 'Transfer from '.$record->fromWarehouse?->name,
                    ];

                    $this->inventoryPostingService->createMovement($out);
                    $this->inventoryPostingService->updateStockBalance($out);
                    $this->inventoryPostingService->createMovement($in);
                    $this->inventoryPostingService->updateStockBalance($in);
                }

                $record->update([
                    'status' => WarehouseTransfer::STATUS_POSTED,
                    'posted_at' => now(),
                    'posted_by' => Auth::id(),
                ]);

                return $record;
            });
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['inventory' => $exception->getMessage()]);
        }
    }

    public function cancel(WarehouseTransfer $record): void
    {
        DB::transaction(function () use ($record): void {
            $record = WarehouseTransfer::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->ensureDraft($record);

            $record->update([
                'status' => WarehouseTransfer::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
            ]);
        });
    }

    public function itemInfo(Item $item, int $warehouseId, ?string $batchNo = null, ?string $expiryDate = null): array
    {
        $availableQty = $this->availableQty($item, $warehouseId, $batchNo, $expiryDate);

        return [
            'id' => $item->id,
            'sku' => $item->sku,
            'name' => $item->name,
            'text' => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
            'available_qty' => $availableQty,
            'unit_id' => $item->base_unit_id ?: $item->unit_of_measure_id,
            'unit_text' => $item->baseUnit?->code ?: $item->unitOfMeasure?->code ?: '-',
            'batches' => $this->batchOptions($item, $warehouseId),
            'tracking' => [
                'is_batch_tracked' => (bool) $item->is_batch_tracked,
                'has_expiry_date' => (bool) $item->has_expiry_date,
                'allow_negative_stock' => (bool) $item->allow_negative_stock,
            ],
        ];
    }

    public function availableQty(Item $item, int $warehouseId, ?string $batchNo = null, ?string $expiryDate = null): float
    {
        if (((bool) $item->is_batch_tracked || (bool) $item->has_expiry_date) && (filled($batchNo) || filled($expiryDate))) {
            if ($batchNo === 'NO_BATCH' && blank($expiryDate)) {
                return max(0, $this->totalAvailableQty($item, $warehouseId) - $this->knownBatchAvailableQty($item, $warehouseId));
            }

            $query = StockBatchBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $item->id)
                ->where('batch_no', filled($batchNo) ? $batchNo : 'NO_BATCH');

            if (filled($expiryDate)) {
                $query->whereDate('expiry_date', $expiryDate);
            }

            return (float) $query->sum('qty_on_hand');
        }

        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->first();

        $newQty = (float) ($balance?->qty_on_hand ?? 0);
        $legacyQty = (float) ($balance?->quantity_on_hand ?? 0);

        return $newQty !== 0.0 ? $newQty : $legacyQty;
    }

    private function syncLines(WarehouseTransfer $record, array $lines): void
    {
        $record->lines()->delete();
        $items = Item::query()
            ->with(['baseUnit', 'unitOfMeasure'])
            ->whereIn('id', collect($lines)->pluck('item_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $this->validateLineData($record, $lines, $items);

        foreach ($lines as $line) {
            $item = $items->get((int) $line['item_id']);
            $uomId = $line['unit_of_measure_id'] ?? $item->base_unit_id ?? $item->unit_of_measure_id;

            $record->lines()->create([
                'item_id' => $item->id,
                'batch_no' => ((bool) $item->is_batch_tracked || (bool) $item->has_expiry_date) && blank($line['batch_no'] ?? null)
                    ? 'NO_BATCH'
                    : ($line['batch_no'] ?? null),
                'expiry_date' => $line['expiry_date'] ?? null,
                'quantity' => $line['quantity'],
                'unit_of_measure_id' => $uomId,
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }

    private function validateLines(WarehouseTransfer $record, Collection $lines): void
    {
        $items = Item::query()->whereIn('id', $lines->pluck('item_id')->filter()->unique())->get()->keyBy('id');
        $this->validateLineData($record, $lines->map(fn ($line): array => [
            'item_id' => $line->item_id,
            'batch_no' => $line->batch_no,
            'expiry_date' => $line->expiry_date?->format('Y-m-d'),
            'quantity' => $line->quantity,
            'unit_of_measure_id' => $line->unit_of_measure_id,
            'notes' => $line->notes,
        ])->all(), $items);
    }

    private function validateLineData(WarehouseTransfer $record, array $lines, Collection $items): void
    {
        foreach ($lines as $index => $line) {
            $item = $items->get((int) ($line['item_id'] ?? 0));
            $prefix = "lines.$index";

            if (! $item) {
                throw ValidationException::withMessages(["$prefix.item_id" => 'Selected item is invalid.']);
            }

            if (! (bool) $item->track_inventory) {
                throw ValidationException::withMessages(["$prefix.item_id" => 'Non inventory item cannot be transferred.']);
            }

            if ((bool) $item->is_batch_tracked && blank($line['batch_no'] ?? null)) {
                throw ValidationException::withMessages(["$prefix.batch_no" => 'Batch number is required for item '.($item->sku ?: $item->name).'.']);
            }

            if (! (bool) $item->is_batch_tracked && filled($line['batch_no'] ?? null)) {
                throw ValidationException::withMessages(["$prefix.batch_no" => 'Batch number is only allowed for batch tracked items.']);
            }

            if (! (bool) $item->is_batch_tracked && filled($line['expiry_date'] ?? null)) {
                throw ValidationException::withMessages(["$prefix.expiry_date" => 'Expiry date is only allowed for batch tracked items.']);
            }

            if ((bool) $item->is_batch_tracked) {
                $batch = $this->batchBalance($item, (int) $record->from_warehouse_id, $line['batch_no'] ?? null, $line['expiry_date'] ?? null);

                if (! $batch) {
                    throw ValidationException::withMessages(["$prefix.batch_no" => 'Selected batch does not have stock in the source warehouse.']);
                }

                if ((bool) $item->has_expiry_date && blank($batch->expiry_date)) {
                    throw ValidationException::withMessages(["$prefix.expiry_date" => 'Selected batch does not have an expiry date.']);
                }
            }

            $qty = (float) ($line['quantity'] ?? 0);
            $available = $this->availableQty($item, (int) $record->from_warehouse_id, $line['batch_no'] ?? null, $line['expiry_date'] ?? null);

            if ($qty <= 0) {
                throw ValidationException::withMessages(["$prefix.quantity" => 'Transfer quantity must be greater than zero.']);
            }

            if (! (bool) $item->allow_negative_stock && $qty > $available + 0.000001) {
                throw ValidationException::withMessages(["$prefix.quantity" => 'Transfer quantity exceeds available stock for item '.($item->sku ?: $item->name).'.']);
            }
        }
    }

    private function batchOptions(Item $item, int $warehouseId): array
    {
        if (! (bool) $item->is_batch_tracked) {
            return [];
        }

        return StockBatchBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->where('qty_on_hand', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('batch_no')
            ->get(['batch_no', 'expiry_date', 'qty_on_hand'])
            ->map(fn (StockBatchBalance $batch): array => [
                'batch_no' => $batch->batch_no ?: 'NO_BATCH',
                'label' => $batch->batch_no ?: 'No Batch',
                'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                'expiry_text' => $batch->expiry_date?->format('d M Y'),
                'available_qty' => (float) $batch->qty_on_hand,
            ])
            ->values()
            ->all();
    }

    private function batchBalance(Item $item, int $warehouseId, ?string $batchNo, ?string $expiryDate): ?StockBatchBalance
    {
        if (blank($batchNo)) {
            return null;
        }

        $query = StockBatchBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->where('batch_no', $batchNo)
            ->where('qty_on_hand', '>', 0);

        if (filled($expiryDate)) {
            $query->whereDate('expiry_date', $expiryDate);
        }

        return $query->first();
    }

    private function totalAvailableQty(Item $item, int $warehouseId): float
    {
        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->first();

        $newQty = (float) ($balance?->qty_on_hand ?? 0);
        $legacyQty = (float) ($balance?->quantity_on_hand ?? 0);

        return $newQty !== 0.0 ? $newQty : $legacyQty;
    }

    private function knownBatchAvailableQty(Item $item, int $warehouseId): float
    {
        return (float) StockBatchBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->sum('qty_on_hand');
    }

    private function warehouseForBranch(int $warehouseId, int $branchId, string $field): Warehouse
    {
        $warehouse = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([$field => 'Warehouse must belong to the selected branch.']);
        }

        return $warehouse;
    }

    private function ensureDifferentWarehouses(Warehouse $fromWarehouse, Warehouse $toWarehouse): void
    {
        if ((int) $fromWarehouse->id === (int) $toWarehouse->id) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Destination warehouse must be different from source warehouse.',
            ]);
        }
    }

    private function ensureCompanyBranch(int $companyId, Branch $branch): void
    {
        if ((int) $branch->company_id !== $companyId) {
            throw ValidationException::withMessages([
                'company_id' => 'Company must match the selected branch.',
            ]);
        }
    }

    private function ensureDraft(WarehouseTransfer $record): void
    {
        if ($record->status !== WarehouseTransfer::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Only draft warehouse transfer can be changed.',
            ]);
        }
    }
}
