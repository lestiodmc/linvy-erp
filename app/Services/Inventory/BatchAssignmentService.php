<?php

namespace App\Services\Inventory;

use App\Models\BatchAssignment;
use App\Models\BatchAssignmentLine;
use App\Models\Branch;
use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockBatchBalance;
use App\Models\Inventory\StockMovement;
use App\Services\DocumentSequenceService;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatchAssignmentService
{
    private const TOLERANCE = 0.000001;

    public function create(array $data): BatchAssignment
    {
        return DB::transaction(function () use ($data): BatchAssignment {
            $branch = Branch::query()->findOrFail($data['branch_id']);
            $record = BatchAssignment::query()->create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'warehouse_id' => $data['warehouse_id'],
                'number' => app(DocumentSequenceService::class)->generate('BATCH_ASSIGNMENT', $branch->company_id, $branch->id),
                'assignment_date' => $data['assignment_date'],
                'status' => BatchAssignment::STATUS_DRAFT,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
            $this->replaceLines($record, $data['lines']);

            return $record;
        });
    }

    public function update(BatchAssignment $record, array $data): BatchAssignment
    {
        return DB::transaction(function () use ($record, $data): BatchAssignment {
            $record = BatchAssignment::query()->lockForUpdate()->findOrFail($record->id);
            $this->ensureDraft($record);
            $branch = Branch::query()->findOrFail($data['branch_id']);
            $record->update(['company_id' => $branch->company_id, 'branch_id' => $branch->id, 'warehouse_id' => $data['warehouse_id'], 'assignment_date' => $data['assignment_date'], 'reason' => $data['reason'] ?? null, 'notes' => $data['notes'] ?? null]);
            $record->lines()->delete();
            $this->replaceLines($record, $data['lines']);

            return $record;
        });
    }

    public function post(BatchAssignment $record): BatchAssignment
    {
        return DB::transaction(function () use ($record): BatchAssignment {
            $record = BatchAssignment::query()->with('lines.item')->lockForUpdate()->findOrFail($record->id);
            $this->ensureDraft($record);
            $warehouse = Warehouse::query()->with('branch')->lockForUpdate()->findOrFail($record->warehouse_id);
            $warehouseCompanyId = $warehouse->company_id ?: $warehouse->branch?->company_id;
            if ((int) $warehouse->branch_id !== (int) $record->branch_id || (int) $warehouseCompanyId !== (int) $record->company_id) throw ValidationException::withMessages(['warehouse_id' => 'Assignment scope does not match the warehouse.']);
            if ($record->lines->isEmpty()) throw ValidationException::withMessages(['lines' => 'At least one assignment line is required.']);
            if (StockMovement::query()->where('reference_type', BatchAssignment::class)->where('reference_id', $record->id)->exists()) throw ValidationException::withMessages(['status' => 'Batch assignment movements already exist.']);

            foreach ($record->lines as $line) $this->postLine($record, $line);

            $record->update(['status' => BatchAssignment::STATUS_POSTED, 'posted_at' => now(), 'posted_by' => Auth::id()]);
            return $record;
        });
    }

    public function cancel(BatchAssignment $record): void
    {
        DB::transaction(function () use ($record): void {
            $record = BatchAssignment::query()->lockForUpdate()->findOrFail($record->id);
            $this->ensureDraft($record);
            $record->update(['status' => BatchAssignment::STATUS_CANCELLED]);
        });
    }

    private function postLine(BatchAssignment $record, BatchAssignmentLine $line): void
    {
        $item = $line->item;
        if (! $item || ! $item->track_inventory || ! $item->is_batch_tracked || filled($line->source_batch_no)) throw ValidationException::withMessages(['lines' => 'Only legacy No Batch stock for batch-tracked inventory items can be assigned.']);
        if ($item->has_expiry_date && blank($line->destination_expiry_date)) throw ValidationException::withMessages(['lines' => "Expiry date is required for {$item->sku}."]);

        $balance = StockBalance::query()->where('company_id', $record->company_id)->where('branch_id', $record->branch_id)->where('warehouse_id', $record->warehouse_id)->where('item_id', $line->item_id)->lockForUpdate()->firstOrFail();
        $batchRows = StockBatchBalance::query()->where('warehouse_id', $record->warehouse_id)->where('item_id', $line->item_id)->lockForUpdate()->get();
        $warehouseTotal = (float) ($balance->qty_on_hand ?: $balance->quantity_on_hand ?: 0);
        $unallocated = $warehouseTotal - (float) $batchRows->sum('qty_on_hand');
        $quantity = (float) $line->quantity;
        if ($quantity <= 0 || $quantity - $unallocated > self::TOLERANCE) throw ValidationException::withMessages(['lines' => "{$item->sku} assignment exceeds current unallocated quantity."]);

        $sameBatchRows = $batchRows->where('batch_no', $line->destination_batch_no);
        $expiry = $line->destination_expiry_date?->format('Y-m-d');
        if ($sameBatchRows->isNotEmpty() && ! $sameBatchRows->contains(fn (StockBatchBalance $batch): bool => $batch->expiry_date?->format('Y-m-d') === $expiry)) throw ValidationException::withMessages(['lines' => "Destination expiry does not match existing batch {$line->destination_batch_no}."]);
        $destination = $sameBatchRows->first(fn (StockBatchBalance $batch): bool => $batch->expiry_date?->format('Y-m-d') === $expiry);
        if ($destination) {
            $destination->fill(['qty_on_hand' => (float) $destination->qty_on_hand + $quantity, 'qty_available' => (float) $destination->qty_available + $quantity])->save();
        } else {
            StockBatchBalance::query()->create(['company_id' => $record->company_id, 'branch_id' => $record->branch_id, 'warehouse_id' => $record->warehouse_id, 'item_id' => $line->item_id, 'batch_no' => $line->destination_batch_no, 'expiry_date' => $line->destination_expiry_date, 'qty_on_hand' => $quantity, 'qty_reserved' => 0, 'qty_available' => $quantity]);
        }

        $this->movement($record, $line, StockMovement::TRANSACTION_BATCH_ASSIGNMENT_OUT, StockMovement::MOVEMENT_OUT, null, null);
        $this->movement($record, $line, StockMovement::TRANSACTION_BATCH_ASSIGNMENT_IN, StockMovement::MOVEMENT_IN, $line->destination_batch_no, $line->destination_expiry_date);
    }

    private function movement(BatchAssignment $record, BatchAssignmentLine $line, string $type, string $direction, ?string $batchNo, mixed $expiry): void
    {
        $quantity = (float) $line->quantity;
        StockMovement::query()->create(['company_id' => $record->company_id, 'branch_id' => $record->branch_id, 'warehouse_id' => $record->warehouse_id, 'item_id' => $line->item_id, 'uom_id' => $line->unit_of_measure_id, 'base_uom_id' => $line->item->base_unit_id ?: $line->item->unit_of_measure_id, 'transaction_type' => $type, 'transaction_id' => $record->id, 'transaction_number' => $record->number, 'transaction_date' => $record->assignment_date, 'movement_type' => $direction, 'qty' => $quantity, 'base_qty' => $quantity, 'quantity_in' => $direction === StockMovement::MOVEMENT_IN ? $quantity : 0, 'quantity_out' => $direction === StockMovement::MOVEMENT_OUT ? $quantity : 0, 'batch_no' => $batchNo, 'expiry_date' => $expiry, 'reference_type' => BatchAssignment::class, 'reference_id' => $record->id, 'reference_number' => $record->number, 'movement_date' => $record->assignment_date, 'remarks' => $record->reason ?: 'Legacy No Batch assignment', 'notes' => $line->notes, 'created_by' => Auth::id(), 'updated_by' => Auth::id()]);
    }

    private function replaceLines(BatchAssignment $record, array $lines): void
    {
        foreach ($lines as $line) { $item = \App\Models\Item::query()->findOrFail($line['item_id']); $record->lines()->create(['item_id' => $line['item_id'], 'source_batch_no' => null, 'destination_batch_no' => trim($line['destination_batch_no']), 'destination_expiry_date' => $line['destination_expiry_date'] ?? null, 'quantity' => $line['quantity'], 'unit_of_measure_id' => $item->base_unit_id ?: $item->unit_of_measure_id, 'notes' => $line['notes'] ?? null]); }
    }

    private function ensureDraft(BatchAssignment $record): void
    {
        if (! $record->isDraft()) throw ValidationException::withMessages(['status' => 'Only draft batch assignments may be changed.']);
    }
}
