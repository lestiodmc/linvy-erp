<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockBatchBalance;
use App\Models\Inventory\StockMovement;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Services\DocumentSequenceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StockAdjustmentService
{
    public function __construct(private readonly InventoryPostingService $inventoryPostingService)
    {
    }

    public function create(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data): StockAdjustment {
            $branch = Branch::query()->findOrFail($data['branch_id']);
            $warehouse = $this->warehouseForBranch((int) $data['warehouse_id'], $branch->id);

            $record = StockAdjustment::query()->create([
                'number' => app(DocumentSequenceService::class)->generate('STOCK_ADJUSTMENT', $branch->company_id, $branch->id),
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'adjustment_date' => $data['adjustment_date'],
                'status' => StockAdjustment::STATUS_DRAFT,
                'reason_code' => $data['reason_code'],
                'reason' => $data['reason'] ?? $data['reason_code'],
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncLines($record, $data['lines']);

            return $record;
        });
    }

    public function update(StockAdjustment $record, array $data): StockAdjustment
    {
        return DB::transaction(function () use ($record, $data): StockAdjustment {
            $record = StockAdjustment::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->ensureDraft($record);

            $branch = Branch::query()->findOrFail($data['branch_id']);
            $warehouse = $this->warehouseForBranch((int) $data['warehouse_id'], $branch->id);

            $record->update([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'adjustment_date' => $data['adjustment_date'],
                'reason_code' => $data['reason_code'],
                'reason' => $data['reason'] ?? $data['reason_code'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($record, $data['lines']);

            return $record;
        });
    }

    public function post(StockAdjustment $record): StockAdjustment
    {
        try {
            return DB::transaction(function () use ($record): StockAdjustment {
                $record = StockAdjustment::query()
                    ->with(['lines.item'])
                    ->whereKey($record->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureDraft($record);

                if ($record->lines->isEmpty()) {
                    throw ValidationException::withMessages(['lines' => 'At least one adjustment line is required.']);
                }

                if (
                    StockMovement::query()
                        ->where('reference_type', StockAdjustment::class)
                        ->where('reference_id', $record->id)
                        ->whereIn('transaction_type', [
                            StockMovement::TRANSACTION_ADJ_IN,
                            StockMovement::TRANSACTION_ADJ_OUT,
                            StockMovement::LEGACY_TRANSACTION_ADJ_IN,
                            StockMovement::LEGACY_TRANSACTION_ADJ_OUT,
                        ])
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'inventory' => 'Stock adjustment has already created stock movements.',
                    ]);
                }

                $this->validateDuplicateNormalItems($record->lines);
                $postedMovementCount = 0;

                foreach ($record->lines as $line) {
                    $item = $line->item;
                    $adjustmentQty = (float) $line->adjustment_qty;
                    $currentQty = $this->currentStockQty($item, (int) $record->warehouse_id, $line->batch_no, $line->expiry_date?->format('Y-m-d'));

                    $this->validateTracking($item, [
                        'batch_no' => $line->batch_no,
                        'serial_numbers' => $line->serial_numbers,
                        'expiry_date' => $line->expiry_date,
                        'adjustment_qty' => $adjustmentQty,
                    ], $record);

                    if (abs($adjustmentQty) < 0.000001) {
                        continue;
                    }

                    if ($adjustmentQty < 0 && ! (bool) $item->allow_negative_stock && ($currentQty + $adjustmentQty) < -0.000001) {
                        throw ValidationException::withMessages([
                            'lines' => 'Adjustment out cannot make stock negative for item '.($item->sku ?: $item->name).'.',
                        ]);
                    }

                    $movementType = $adjustmentQty > 0 ? StockMovement::MOVEMENT_IN : StockMovement::MOVEMENT_OUT;
                    $transactionType = $adjustmentQty > 0 ? StockMovement::TRANSACTION_ADJ_IN : StockMovement::TRANSACTION_ADJ_OUT;
                    $movementQty = abs($adjustmentQty);
                    $unitCost = (float) ($item->standard_cost ?? 0);
                    $movementData = [
                        'company_id' => $record->company_id,
                        'branch_id' => $record->branch_id,
                        'warehouse_id' => $record->warehouse_id,
                        'item_id' => $line->item_id,
                        'uom_id' => $line->uom_id ?: $line->unit_of_measure_id ?: $item->base_unit_id ?: $item->unit_of_measure_id,
                        'base_uom_id' => $item->base_unit_id ?: $item->unit_of_measure_id,
                        'transaction_type' => $transactionType,
                        'transaction_id' => $record->id,
                        'transaction_number' => $record->number,
                        'transaction_date' => $record->adjustment_date,
                        'movement_type' => $movementType,
                        'qty' => $movementQty,
                        'base_qty' => $movementQty,
                        'unit_cost' => $unitCost,
                        'total_cost' => $movementQty * $unitCost,
                        'batch_no' => (bool) $item->is_batch_tracked ? $line->batch_no : null,
                        'expiry_date' => (bool) $item->is_serial_tracked ? null : $line->expiry_date,
                        'reference_type' => StockAdjustment::class,
                        'reference_id' => $record->id,
                        'remarks' => trim($record->reason.($record->notes ? ' - '.$record->notes : '')),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];

                    if ((bool) $item->is_serial_tracked) {
                        foreach ($this->serialNumbers($line->serial_numbers) as $serialNumber) {
                            $serialMovementData = $movementData + ['serial_no' => $serialNumber];
                            $serialMovementData['qty'] = 1;
                            $serialMovementData['base_qty'] = 1;
                            $serialMovementData['total_cost'] = $unitCost;
                            $this->inventoryPostingService->createMovement($serialMovementData);
                            $this->inventoryPostingService->updateStockBalance($serialMovementData);
                            $postedMovementCount++;
                        }

                        continue;
                    }

                    $this->inventoryPostingService->createMovement($movementData);
                    $this->inventoryPostingService->updateStockBalance($movementData);
                    $postedMovementCount++;
                }

                if ($postedMovementCount === 0) {
                    throw ValidationException::withMessages([
                        'lines' => 'At least one line must have a stock difference before posting.',
                    ]);
                }

                $record->update([
                    'status' => StockAdjustment::STATUS_POSTED,
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                ]);

                return $record;
            });
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['inventory' => $exception->getMessage()]);
        }
    }

    public function cancel(StockAdjustment $record): void
    {
        DB::transaction(function () use ($record): void {
            $record = StockAdjustment::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->ensureDraft($record);
            $record->update(['status' => StockAdjustment::STATUS_CANCELLED]);
        });
    }

    public function currentStockQty(Item $item, int $warehouseId, ?string $batchNo = null, ?string $expiryDate = null): float
    {
        if (((bool) $item->is_batch_tracked || (bool) $item->has_expiry_date) && (filled($batchNo) || filled($expiryDate))) {
            $balanceBatchNo = filled($batchNo) ? $batchNo : 'NO_BATCH';

            if ($balanceBatchNo === 'NO_BATCH' && blank($expiryDate)) {
                return max(0, $this->totalStockQty($item, $warehouseId) - $this->knownBatchStockQty($item, $warehouseId));
            }

            $query = StockBatchBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $item->id)
                ->where('batch_no', $balanceBatchNo);

            if (filled($expiryDate)) {
                $query->whereDate('expiry_date', $expiryDate);
            }

            return (float) $query->sum('qty_on_hand');
        }

        return $this->totalStockQty($item, $warehouseId);
    }

    private function totalStockQty(Item $item, int $warehouseId): float
    {
        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->first();

        $newQty = (float) ($balance?->qty_on_hand ?? 0);
        $legacyQty = (float) ($balance?->quantity_on_hand ?? 0);

        return $newQty !== 0.0 ? $newQty : $legacyQty;
    }

    private function knownBatchStockQty(Item $item, int $warehouseId): float
    {
        return (float) StockBatchBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->sum('qty_on_hand');
    }

    private function syncLines(StockAdjustment $record, array $lines): void
    {
        $record->lines()->delete();
        $itemIds = collect($lines)->pluck('item_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $items = Item::query()->whereIn('id', $itemIds)->get()->keyBy('id');
        $this->validateDuplicateNormalLineData($lines, $items);

        foreach ($lines as $index => $line) {
            $item = $items->get((int) $line['item_id']);

            if (! $item) {
                throw ValidationException::withMessages([
                    "lines.$index.item_id" => 'Selected item is invalid.',
                ]);
            }

            if (! (bool) $item->track_inventory) {
                throw ValidationException::withMessages([
                    "lines.$index.item_id" => 'Non inventory item cannot be adjusted.',
                ]);
            }

            $systemQty = $this->currentStockQty($item, (int) $record->warehouse_id, $line['batch_no'] ?? null, $line['expiry_date'] ?? null);
            $countedQty = (float) $line['counted_qty'];
            $this->validateTracking($item, $line + ['adjustment_qty' => $countedQty - $systemQty], $record, $index);

            $record->lines()->create($this->lineQuantityPayload($item, $systemQty, $countedQty, $line['batch_no'] ?? null, $line));
        }
    }

    private function validateDuplicateNormalLineData(array $lines, Collection $items): void
    {
        $normalItemIndexes = [];

        foreach ($lines as $index => $line) {
            $item = $items->get((int) ($line['item_id'] ?? 0));

            if (! $item || (bool) $item->is_batch_tracked || (bool) $item->is_serial_tracked) {
                continue;
            }

            if (array_key_exists($item->id, $normalItemIndexes)) {
                throw ValidationException::withMessages([
                    "lines.$index.item_id" => 'Duplicate item is not allowed unless the item is batch or serial tracked.',
                ]);
            }

            $normalItemIndexes[$item->id] = $index;
        }
    }

    private function validateDuplicateNormalItems(Collection $lines): void
    {
        $normalItemIds = [];

        foreach ($lines as $index => $line) {
            $item = $line->item;

            if (! $item || (bool) $item->is_batch_tracked || (bool) $item->is_serial_tracked) {
                continue;
            }

            if (array_key_exists($item->id, $normalItemIds)) {
                throw ValidationException::withMessages([
                    'lines' => 'Duplicate item is not allowed unless the item is batch or serial tracked.',
                ]);
            }

            $normalItemIds[$item->id] = $index;
        }
    }

    private function lineQuantityPayload(Item $item, float $systemQty, float $countedQty, ?string $batchNo, array $line): array
    {
        $adjustmentQty = $countedQty - $systemQty;
        $legacyMovementType = $adjustmentQty < 0 ? 'ADJUSTMENT_MINUS' : 'ADJUSTMENT_PLUS';
        $uomId = $line['uom_id'] ?? $item->base_unit_id ?? $item->unit_of_measure_id;

        return [
            'item_id' => $item->id,
            'uom_id' => $uomId,
            'unit_of_measure_id' => $uomId,
            'system_qty' => $systemQty,
            'counted_qty' => $countedQty,
            'adjustment_qty' => $adjustmentQty,
            'movement_type' => $legacyMovementType,
            'quantity' => abs($adjustmentQty),
            'unit_cost' => (float) ($item->standard_cost ?? 0),
            'batch_no' => (bool) $item->is_batch_tracked ? $batchNo : null,
            'serial_numbers' => (bool) $item->is_serial_tracked ? ($line['serial_numbers'] ?? null) : null,
            'expiry_date' => (bool) $item->is_batch_tracked ? ($line['expiry_date'] ?? null) : null,
            'remarks' => $line['remarks'] ?? null,
            'notes' => $line['remarks'] ?? null,
        ];
    }

    private function validateTracking(Item $item, array $line, StockAdjustment $record, ?int $index = null): void
    {
        $prefix = $index === null ? 'lines' : "lines.$index";
        $errors = [];
        $sku = $item->sku ?: $item->name;
        $adjustmentQty = (float) ($line['adjustment_qty'] ?? 0);

        if ((bool) $item->is_serial_tracked) {
            $serialNumbers = $this->serialNumbers($line['serial_numbers'] ?? null);

            if ($serialNumbers === []) {
                $errors["$prefix.serial_numbers"] = 'Serial numbers are required for serial tracked item '.$sku.'.';
            } elseif (count($serialNumbers) !== count(array_unique($serialNumbers))) {
                $errors["$prefix.serial_numbers"] = 'Serial numbers must be unique for item '.$sku.'.';
            } elseif (abs($adjustmentQty) > 0.000001 && abs(abs($adjustmentQty) - count($serialNumbers)) > 0.000001) {
                $errors["$prefix.serial_numbers"] = 'Serial number count must match adjustment quantity for item '.$sku.'.';
            }
        } else {
            if ((bool) $item->is_batch_tracked && blank($line['batch_no'] ?? null)) {
                $errors["$prefix.batch_no"] = 'Batch number is required for batch tracked item '.$sku.'.';
            }

            if ((bool) $item->is_batch_tracked) {
                $batch = $this->batchBalance($item, (int) $record->warehouse_id, $line['batch_no'] ?? null, $line['expiry_date'] ?? null);

                if (! $batch) {
                    $errors["$prefix.batch_no"] = 'Selected batch does not have stock in the adjustment warehouse.';
                } elseif (filled($line['expiry_date'] ?? null) && $batch->expiry_date?->format('Y-m-d') !== $line['expiry_date']) {
                    $errors["$prefix.expiry_date"] = 'Expiry date must follow the selected batch.';
                } elseif ((bool) $item->has_expiry_date && blank($batch->expiry_date)) {
                    $errors["$prefix.expiry_date"] = 'Selected batch does not have an expiry date.';
                }
            } elseif (filled($line['batch_no'] ?? null)) {
                $errors["$prefix.batch_no"] = 'Batch number is only allowed for batch tracked items.';
            }

            if (! (bool) $item->is_batch_tracked && filled($line['expiry_date'] ?? null)) {
                $errors["$prefix.expiry_date"] = 'Expiry date is only allowed for batch tracked items.';
            }
        }

        if ($adjustmentQty < 0 && ! (bool) $item->allow_negative_stock) {
            $currentQty = $this->currentStockQty($item, (int) $record->warehouse_id, $line['batch_no'] ?? null, $line['expiry_date'] ?? null);

            if (($currentQty + $adjustmentQty) < -0.000001) {
                $errors["$prefix.counted_qty"] = 'Adjustment out cannot make stock negative for item '.$sku.'.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function warehouseForBranch(int $warehouseId, int $branchId): Warehouse
    {
        $warehouse = Warehouse::query()
            ->whereKey($warehouseId)
            ->where('is_active', true)
            ->where('branch_id', $branchId)
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Warehouse must belong to the selected branch.',
            ]);
        }

        return $warehouse;
    }

    private function batchBalance(Item $item, int $warehouseId, ?string $batchNo, mixed $expiryDate): ?StockBatchBalance
    {
        if (blank($batchNo)) {
            return null;
        }

        $expiry = $expiryDate instanceof \DateTimeInterface ? $expiryDate->format('Y-m-d') : $expiryDate;

        $query = StockBatchBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $item->id)
            ->where('batch_no', $batchNo)
            ->where('qty_on_hand', '>', 0);

        filled($expiry)
            ? $query->whereDate('expiry_date', $expiry)
            : $query->whereNull('expiry_date');

        return $query->first();
    }

    private function ensureDraft(StockAdjustment $record): void
    {
        if ($record->status !== StockAdjustment::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Only draft stock adjustment can be changed.',
            ]);
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
}
