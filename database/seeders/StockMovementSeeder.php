<?php

namespace Database\Seeders;

use App\Models\Receiving;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $receivings = Receiving::with('lines')
            ->where('status', 'posted')
            ->orderBy('received_date')
            ->orderBy('id')
            ->get();

        $itemWarehousePairs = $receivings
            ->flatMap(fn ($receiving) => $receiving->lines->map(fn ($line) => [$line->item_id, $receiving->warehouse_id]))
            ->unique(fn ($pair) => $pair[0].'-'.$pair[1]);

        StockMovement::where('movement_type', 'PURCHASE_RECEIVE')
            ->where('reference_type', Receiving::class)
            ->delete();

        foreach ($itemWarehousePairs as [$itemId, $warehouseId]) {
            StockBalance::where('item_id', $itemId)->where('warehouse_id', $warehouseId)->delete();
        }

        $userId = User::where('email', 'admin@linvy.local')->value('id') ?? User::query()->value('id');

        foreach ($receivings as $receiving) {
            foreach ($receiving->lines as $line) {
                StockMovement::create([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $receiving->warehouse_id,
                    'movement_type' => 'PURCHASE_RECEIVE',
                    'quantity_in' => $line->received_quantity,
                    'quantity_out' => 0,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => (float) $line->received_quantity * (float) $line->unit_cost,
                    'reference_type' => Receiving::class,
                    'reference_id' => $receiving->id,
                    'reference_number' => $receiving->number,
                    'movement_date' => $receiving->received_date,
                    'notes' => 'Auto generated from posted receiving seed.',
                    'created_by' => $userId,
                ]);

                $this->updateStockBalance(
                    $line->item_id,
                    $receiving->warehouse_id,
                    (float) $line->received_quantity,
                    (float) $line->unit_cost,
                    $receiving->received_date
                );
            }
        }
    }

    private function updateStockBalance(int $itemId, int $warehouseId, float $quantity, float $unitCost, mixed $movementDate): void
    {
        $balance = StockBalance::firstOrCreate(
            ['item_id' => $itemId, 'warehouse_id' => $warehouseId],
            [
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'average_cost' => 0,
                'last_movement_at' => null,
            ]
        );

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
}
