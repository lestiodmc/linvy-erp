<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\Receiving;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ReceivingSeeder extends Seeder
{
    public function run(): void
    {
        Receiving::query()->delete();

        foreach (PurchaseOrder::with('lines')->get() as $po) {
            foreach ($po->lines as $line) {
                $line->update([
                    'received_quantity' => 0,
                    'remaining_quantity' => $line->quantity,
                ]);
            }

            if ($po->status !== 'draft') {
                $po->update(['status' => 'approved']);
            }
        }

        $warehouse = Warehouse::where('code', 'RM-WH')->firstOrFail();
        $date = now()->startOfMonth()->addDays(12);

        $receivings = [
            [
                'idx' => 1,
                'po' => 1,
                'delivery' => 'SJ-LBS-0001',
                'date' => $date->toDateString(),
                'lines' => [
                    ['RM001', 60],
                    ['RM002', 80],
                    ['PK001', 500],
                    ['PK004', 120],
                ],
            ],
            [
                'idx' => 2,
                'po' => 1,
                'delivery' => 'SJ-LBS-0002',
                'date' => $date->copy()->addDay()->toDateString(),
                'lines' => [
                    ['RM001', 40],
                ],
            ],
            [
                'idx' => 3,
                'po' => 2,
                'delivery' => 'SJ-SSI-0007',
                'date' => $date->copy()->addDays(2)->toDateString(),
                'lines' => [
                    ['RM002', 50],
                    ['PK002', 200],
                ],
            ],
        ];

        foreach ($receivings as $receiving) {
            $po = PurchaseOrder::with('lines.item')->where('number', $this->number('PO', $receiving['po']))->firstOrFail();

            $record = Receiving::create([
                'number' => $this->number('RCV', $receiving['idx']),
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'warehouse_id' => $warehouse->id,
                'received_date' => $receiving['date'],
                'supplier_delivery_number' => $receiving['delivery'],
                'status' => 'posted',
                'notes' => 'Posted receiving demo for PT Linvy Seafood Indonesia.',
            ]);

            foreach ($receiving['lines'] as [$sku, $quantity]) {
                $poLine = $po->lines->first(fn ($line) => $line->item?->sku === $sku);
                $previouslyReceived = (float) $poLine->received_quantity;
                $remainingBefore = (float) $poLine->quantity - $previouslyReceived;
                $remainingAfter = $remainingBefore - $quantity;

                $record->lines()->create([
                    'purchase_order_line_id' => $poLine->id,
                    'item_id' => $poLine->item_id,
                    'description' => $poLine->description,
                    'ordered_quantity' => $poLine->quantity,
                    'previously_received_quantity' => $previouslyReceived,
                    'received_quantity' => $quantity,
                    'remaining_quantity' => $remainingAfter,
                    'unit_id' => $poLine->unit_id,
                    'unit_cost' => $poLine->unit_price,
                    'notes' => 'Accepted by warehouse checker.',
                ]);

                $poLine->update([
                    'received_quantity' => $previouslyReceived + $quantity,
                    'remaining_quantity' => $remainingAfter,
                ]);
            }

            $this->refreshPurchaseOrderStatus($po->fresh('lines'));
        }
    }

    private function refreshPurchaseOrderStatus(PurchaseOrder $po): void
    {
        $allReceived = $po->lines->every(fn ($line) => (float) $line->received_quantity >= (float) $line->quantity);
        $anyReceived = $po->lines->contains(fn ($line) => (float) $line->received_quantity > 0);

        $po->update([
            'status' => $allReceived ? 'fully_received' : ($anyReceived ? 'partially_received' : 'approved'),
        ]);
    }

    private function number(string $prefix, int $idx): string
    {
        return $prefix.'/'.now()->format('Y').'/'.now()->format('m').'/'.str_pad((string) $idx, 4, '0', STR_PAD_LEFT);
    }
}
