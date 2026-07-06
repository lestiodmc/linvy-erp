<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StockBalance;
use App\Models\Inventory\StockMovement;
use App\Models\Item;
use App\Models\Receiving;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryPostingService
{
    public function postReceive(Model $receive): void
    {
        DB::transaction(function () use ($receive): void {
            /** @var Receiving $receive */
            $receive = Receiving::query()
                ->with(['lines.item', 'lines.warehouse', 'lines.unit'])
                ->lockForUpdate()
                ->findOrFail($receive->getKey());

            $alreadyPosted = StockMovement::query()
                ->where('transaction_type', StockMovement::TRANSACTION_RCV)
                ->where('transaction_id', $receive->id)
                ->exists();

            if ($alreadyPosted) {
                throw new RuntimeException('Receive has already been posted to inventory.');
            }

            foreach ($receive->lines as $line) {
                $warehouseId = $line->warehouse_id ?: $receive->warehouse_id;
                $item = $line->item;

                if (! (bool) ($item?->track_inventory ?? true)) {
                    continue;
                }

                $qty = (float) $line->received_quantity;
                $baseQty = $this->baseQuantity($qty, $line->unit_id, $item);
                $unitCost = (float) ($line->unit_cost ?? $line->purchaseOrderLine?->unit_price ?? 0);

                $movementData = [
                    'company_id' => $receive->company_id,
                    'branch_id' => $receive->branch_id,
                    'warehouse_id' => $warehouseId,
                    'item_id' => $line->item_id,
                    'uom_id' => $line->unit_id,
                    'base_uom_id' => $item?->base_unit_id ?: $item?->unit_of_measure_id ?: $line->unit_id,
                    'transaction_type' => StockMovement::TRANSACTION_RCV,
                    'transaction_id' => $receive->id,
                    'transaction_number' => $receive->number,
                    'transaction_date' => $receive->received_date,
                    'movement_type' => StockMovement::MOVEMENT_IN,
                    'qty' => $qty,
                    'base_qty' => $baseQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $baseQty * $unitCost,
                    'reference_type' => $receive::class,
                    'reference_id' => $receive->id,
                    'remarks' => $line->notes,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ];

                $this->validateStock($movementData);
                $this->createMovement($movementData);
                $this->updateStockBalance($movementData);
            }

            if (array_key_exists('status', $receive->getAttributes())) {
                $receive->update(['status' => 'posted']);
            }
        });
    }

    public function createMovement(array $data): StockMovement
    {
        $this->validateMovementPayload($data);

        $baseQty = (float) $data['base_qty'];

        return StockMovement::create($data + [
            'quantity_in' => $data['movement_type'] === StockMovement::MOVEMENT_IN ? $baseQty : 0,
            'quantity_out' => $data['movement_type'] === StockMovement::MOVEMENT_OUT ? $baseQty : 0,
            'reference_number' => $data['transaction_number'] ?? null,
            'movement_date' => $data['transaction_date'],
            'notes' => $data['remarks'] ?? null,
        ]);
    }

    public function updateStockBalance(array $data): StockBalance
    {
        $this->validateStock($data);

        return DB::transaction(function () use ($data): StockBalance {
            $balance = StockBalance::query()
                ->where('company_id', $data['company_id'])
                ->where('branch_id', $data['branch_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('item_id', $data['item_id'])
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = StockBalance::query()
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('item_id', $data['item_id'])
                    ->lockForUpdate()
                    ->first();
            }

            if (! $balance) {
                $balance = new StockBalance([
                    'company_id' => $data['company_id'],
                    'branch_id' => $data['branch_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'item_id' => $data['item_id'],
                    'uom_id' => $data['uom_id'] ?? null,
                    'base_uom_id' => $data['base_uom_id'] ?? null,
                    'qty_on_hand' => 0,
                    'qty_reserved' => 0,
                    'qty_available' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'average_cost' => 0,
                    'last_cost' => 0,
                    'total_value' => 0,
                    'created_by' => $data['created_by'] ?? Auth::id(),
                ]);
            }

            $newQtyOnHand = (float) ($balance->qty_on_hand ?? 0);
            $legacyQtyOnHand = (float) ($balance->quantity_on_hand ?? 0);
            $oldQty = $newQtyOnHand !== 0.0 ? $newQtyOnHand : $legacyQtyOnHand;
            $oldAverageCost = (float) ($balance->average_cost ?? 0);
            $movementQty = (float) $data['base_qty'];
            $unitCost = (float) ($data['unit_cost'] ?? 0);

            if ($data['movement_type'] === StockMovement::MOVEMENT_IN) {
                $newQty = $oldQty + $movementQty;
                $newAverageCost = $newQty > 0
                    ? (($oldQty * $oldAverageCost) + ($movementQty * $unitCost)) / $newQty
                    : $unitCost;
                $lastCost = $unitCost;
            } else {
                $newQty = $oldQty - $movementQty;
                $newAverageCost = $oldAverageCost;
                $lastCost = (float) ($balance->last_cost ?? $unitCost);
            }

            $reservedQty = (float) ($balance->qty_reserved ?? $balance->quantity_reserved ?? 0);
            $balance->fill([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'uom_id' => $data['uom_id'] ?? $balance->uom_id,
                'base_uom_id' => $data['base_uom_id'] ?? $balance->base_uom_id,
                'qty_on_hand' => $newQty,
                'qty_reserved' => $reservedQty,
                'qty_available' => $newQty - $reservedQty,
                'quantity_on_hand' => $newQty,
                'quantity_reserved' => $reservedQty,
                'average_cost' => $newAverageCost,
                'last_cost' => $lastCost,
                'total_value' => $newQty * $newAverageCost,
                'last_movement_at' => $data['transaction_date'] ?? now(),
                'updated_by' => $data['updated_by'] ?? Auth::id(),
            ]);

            $balance->save();

            return $balance;
        });
    }

    public function validateStock(array $data): void
    {
        $this->validateMovementPayload($data);

        $item = Item::find($data['item_id']);
        if (! $item) {
            throw new InvalidArgumentException('Item is required.');
        }

        $warehouse = Warehouse::find($data['warehouse_id']);
        if (! $warehouse) {
            throw new InvalidArgumentException('Warehouse is required.');
        }

        if ($data['movement_type'] === StockMovement::MOVEMENT_OUT && ! (bool) $item->allow_negative_stock) {
            $balance = StockBalance::query()
                ->where('company_id', $data['company_id'])
                ->where('branch_id', $data['branch_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('item_id', $data['item_id'])
                ->first();

            $currentNewQty = (float) ($balance?->qty_on_hand ?? 0);
            $currentLegacyQty = (float) ($balance?->quantity_on_hand ?? 0);
            $currentQty = $currentNewQty !== 0.0 ? $currentNewQty : $currentLegacyQty;

            if ($currentQty < (float) $data['base_qty']) {
                throw new RuntimeException('Insufficient stock.');
            }
        }
    }

    private function validateMovementPayload(array $data): void
    {
        if (blank($data['transaction_type'] ?? null)) {
            throw new InvalidArgumentException('Transaction type is required.');
        }

        if (! in_array($data['movement_type'] ?? null, [StockMovement::MOVEMENT_IN, StockMovement::MOVEMENT_OUT], true)) {
            throw new InvalidArgumentException('Movement type must be IN or OUT.');
        }

        if (blank($data['warehouse_id'] ?? null)) {
            throw new InvalidArgumentException('Warehouse is required.');
        }

        if (blank($data['item_id'] ?? null)) {
            throw new InvalidArgumentException('Item is required.');
        }

        if ((float) ($data['qty'] ?? 0) <= 0 || (float) ($data['base_qty'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }

    private function baseQuantity(float $qty, ?int $uomId, ?Item $item): float
    {
        if (! $item || ! $uomId || ! $item->base_unit_id || (int) $uomId === (int) $item->base_unit_id) {
            return $qty;
        }

        return $qty;
    }
}
