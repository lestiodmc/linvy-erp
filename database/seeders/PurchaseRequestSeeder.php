<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseRequestSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('email', 'admin@linvy.local')->value('id') ?? User::query()->value('id');
        $date = now()->startOfMonth()->addDays(1);

        $requests = [
            ['idx' => 1, 'status' => 'approved', 'department' => 'Procurement Seafood', 'items' => [['RM001', 100], ['RM002', 80], ['PK001', 500], ['PK004', 120]]],
            ['idx' => 2, 'status' => 'approved', 'department' => 'Production Planning', 'items' => [['RM002', 120], ['RM005', 75], ['PK002', 400], ['PK005', 800]]],
            ['idx' => 3, 'status' => 'approved', 'department' => 'Export Operations', 'items' => [['RM004', 60], ['PK003', 250], ['PK004', 150], ['PK010', 300]]],
            ['idx' => 4, 'status' => 'submitted', 'department' => 'Quality Control', 'items' => [['CS001', 20], ['CS002', 30], ['CS003', 25]]],
            ['idx' => 5, 'status' => 'draft', 'department' => 'Warehouse', 'items' => [['PK006', 40], ['PK007', 25], ['PK008', 25], ['PK009', 500]]],
        ];

        foreach ($requests as $request) {
            $record = PurchaseRequest::updateOrCreate(
                ['number' => $this->number('PR', $request['idx'])],
                [
                    'request_date' => $date->copy()->addDays($request['idx'] - 1)->toDateString(),
                    'requested_by' => $userId,
                    'department' => $request['department'],
                    'status' => $request['status'],
                    'notes' => 'Demo request for PT Linvy Seafood Indonesia purchase flow.',
                ]
            );

            $record->lines()->delete();

            foreach ($request['items'] as [$sku, $quantity]) {
                $item = Item::where('sku', $sku)->firstOrFail();
                $record->lines()->create([
                    'item_id' => $item->id,
                    'description' => $item->name,
                    'quantity' => $quantity,
                    'unit_id' => $item->unit_of_measure_id,
                    'required_date' => $date->copy()->addDays(7 + $request['idx'])->toDateString(),
                    'notes' => 'Needed for seafood production and packing schedule.',
                    'converted_quantity' => 0,
                ]);
            }
        }
    }

    private function number(string $prefix, int $idx): string
    {
        return $prefix.'/'.now()->format('Y').'/'.now()->format('m').'/'.str_pad((string) $idx, 4, '0', STR_PAD_LEFT);
    }
}
