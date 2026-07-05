<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Receiving;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class DemoTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetDemoTransactions();

        $this->call([
            PurchaseRequestSeeder::class,
            PurchaseOrderSeeder::class,
            ReceivingSeeder::class,
            StockMovementSeeder::class,
        ]);
    }

    private function resetDemoTransactions(): void
    {
        $receivings = Receiving::with('lines')->where('number', 'like', 'RCV/%')->get();
        $receivingIds = $receivings->pluck('id');
        $receivingLines = $receivings->flatMap->lines;
        $itemWarehousePairs = $receivingLines
            ->map(fn ($line): array => [$line->item_id, $line->warehouse_id])
            ->filter(fn (array $pair): bool => filled($pair[0]) && filled($pair[1]))
            ->unique(fn (array $pair): string => $pair[0].'-'.$pair[1]);

        StockMovement::where('reference_type', Receiving::class)
            ->whereIn('reference_id', $receivingIds)
            ->delete();

        foreach ($itemWarehousePairs as [$itemId, $warehouseId]) {
            StockBalance::where('item_id', $itemId)->where('warehouse_id', $warehouseId)->delete();
        }

        foreach ($receivings as $receiving) {
            $receiving->lines()->delete();
            $receiving->delete();
        }

        foreach (PurchaseOrder::with('lines')->where('number', 'like', 'PO/%')->get() as $purchaseOrder) {
            $purchaseOrder->lines()->delete();
            $purchaseOrder->delete();
        }

        foreach (PurchaseRequest::with('lines')->where('number', 'like', 'PR/%')->get() as $purchaseRequest) {
            $purchaseRequest->lines()->delete();
            $purchaseRequest->delete();
        }
    }
}
