<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $date = now()->startOfMonth()->addDays(5);

        $orders = [
            ['idx' => 1, 'supplier' => 'SUP001', 'pr' => 1, 'status' => 'approved', 'lines' => [['RM001', 100, 95000], ['RM002', 80, 78000], ['PK001', 500, 450], ['PK004', 120, 8500]]],
            ['idx' => 2, 'supplier' => 'SUP003', 'pr' => 2, 'status' => 'approved', 'lines' => [['RM002', 120, 78000], ['RM005', 75, 88000], ['PK002', 400, 700], ['PK005', 800, 150]]],
            ['idx' => 3, 'supplier' => 'SUP006', 'pr' => 3, 'status' => 'approved', 'lines' => [['RM004', 60, 115000], ['PK003', 250, 1100], ['PK004', 150, 8500], ['PK010', 300, 2500]]],
            ['idx' => 4, 'supplier' => 'SUP004', 'pr' => null, 'status' => 'draft', 'lines' => [['CS001', 20, 45000], ['CS002', 30, 38000], ['CS003', 25, 42000]]],
            ['idx' => 5, 'supplier' => 'SUP009', 'pr' => null, 'status' => 'approved', 'lines' => [['RM003', 90, 62000], ['PK006', 40, 12000], ['PK009', 500, 900]]],
        ];

        foreach ($orders as $order) {
            $supplier = Supplier::where('code', $order['supplier'])->firstOrFail();
            $purchaseRequest = $order['pr'] ? PurchaseRequest::where('number', $this->number('PR', $order['pr']))->firstOrFail() : null;
            $totals = $this->totals($order['lines']);

            $record = PurchaseOrder::updateOrCreate(
                ['number' => $this->number('PO', $order['idx'])],
                [
                    'supplier_id' => $supplier->id,
                    'purchase_request_id' => $purchaseRequest?->id,
                    'order_date' => $date->copy()->addDays($order['idx'] - 1)->toDateString(),
                    'expected_date' => $date->copy()->addDays(7 + $order['idx'])->toDateString(),
                    'status' => $order['status'],
                    'subtotal' => $totals['subtotal'],
                    'tax_total' => $totals['tax_total'],
                    'grand_total' => $totals['grand_total'],
                    'notes' => $purchaseRequest ? 'Converted from approved purchase request.' : 'Direct PO for special procurement case.',
                ]
            );

            foreach ($order['lines'] as [$sku, $quantity, $price]) {
                $item = Item::where('sku', $sku)->firstOrFail();
                $requestLine = $purchaseRequest?->lines()->where('item_id', $item->id)->first();
                $subtotal = $quantity * $price;
                $existingLine = $record->lines()->where('item_id', $item->id)->first();
                $receivedQuantity = (float) ($existingLine?->received_quantity ?? 0);

                $record->lines()->updateOrCreate(
                    ['item_id' => $item->id],
                    [
                        'purchase_request_line_id' => $requestLine?->id,
                        'description' => $item->name,
                        'quantity' => $quantity,
                        'received_quantity' => $receivedQuantity,
                        'remaining_quantity' => max(0, $quantity - $receivedQuantity),
                        'unit_id' => $item->unit_of_measure_id,
                        'unit_price' => $price,
                        'tax_percent' => 11,
                        'subtotal' => $subtotal,
                    ]
                );
            }

            $this->syncConvertedQuantities($purchaseRequest);
        }
    }

    private function totals(array $lines): array
    {
        $subtotal = collect($lines)->sum(fn ($line) => $line[1] * $line[2]);
        $taxTotal = $subtotal * 0.11;

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $subtotal + $taxTotal,
        ];
    }

    private function number(string $prefix, int $idx): string
    {
        return $prefix.'/'.now()->format('Y').'/'.now()->format('m').'/'.str_pad((string) $idx, 4, '0', STR_PAD_LEFT);
    }

    private function syncConvertedQuantities(?PurchaseRequest $purchaseRequest): void
    {
        if (! $purchaseRequest) {
            return;
        }

        foreach ($purchaseRequest->lines as $requestLine) {
            $convertedQuantity = PurchaseOrderLine::where('purchase_request_line_id', $requestLine->id)->sum('quantity');

            PurchaseRequestLine::whereKey($requestLine->id)->update([
                'converted_quantity' => $convertedQuantity,
            ]);
        }
    }
}
