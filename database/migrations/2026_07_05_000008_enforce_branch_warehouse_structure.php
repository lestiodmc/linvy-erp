<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $legacyWarehouseCodes = [
        'RM-WH',
        'FG-WH',
        'QC-WH',
        'PK-WH',
        'PROD-WH',
        'TRANSIT-WH',
        'REJECT-WH',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'branch_id'], 'branch_user_user_branch_unique');
            });
        }

        $this->cleanupLegacyWarehouses();

        Schema::table('warehouses', function (Blueprint $table) {
            $table->unique(['branch_id', 'warehouse_type_id'], 'warehouses_branch_type_unique');
            $table->unique(['branch_id', 'code'], 'warehouses_branch_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique('warehouses_branch_type_unique');
            $table->dropUnique('warehouses_branch_code_unique');
        });

        Schema::dropIfExists('branch_user');
    }

    private function cleanupLegacyWarehouses(): void
    {
        $legacyWarehouses = DB::table('warehouses')
            ->whereIn('code', $this->legacyWarehouseCodes)
            ->get();

        foreach ($legacyWarehouses as $legacyWarehouse) {
            $replacementId = DB::table('warehouses')
                ->where('id', '!=', $legacyWarehouse->id)
                ->where('branch_id', $legacyWarehouse->branch_id)
                ->where('warehouse_type_id', $legacyWarehouse->warehouse_type_id)
                ->where('is_active', true)
                ->whereNotIn('code', $this->legacyWarehouseCodes)
                ->orderBy('id')
                ->value('id');

            if ($replacementId) {
                $this->moveWarehouseReferences((int) $legacyWarehouse->id, (int) $replacementId);
                DB::table('warehouses')->where('id', $legacyWarehouse->id)->delete();

                continue;
            }

            DB::table('warehouses')->where('id', $legacyWarehouse->id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
        }
    }

    private function moveWarehouseReferences(int $fromWarehouseId, int $toWarehouseId): void
    {
        $this->mergeStockBalances($fromWarehouseId, $toWarehouseId);

        foreach ($this->warehouseReferenceColumns() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::table($table)
                        ->where($column, $fromWarehouseId)
                        ->update([$column => $toWarehouseId]);
                }
            }
        }
    }

    private function warehouseReferenceColumns(): array
    {
        return [
            'receivings' => ['warehouse_id'],
            'receiving_lines' => ['warehouse_id'],
            'stock_movements' => ['warehouse_id'],
            'warehouse_transfers' => ['from_warehouse_id', 'to_warehouse_id'],
            'stock_adjustments' => ['warehouse_id'],
            'productions' => ['production_warehouse_id', 'output_warehouse_id'],
            'production_inputs' => ['warehouse_id'],
            'production_outputs' => ['warehouse_id'],
            'delivery_orders' => ['warehouse_id'],
        ];
    }

    private function mergeStockBalances(int $fromWarehouseId, int $toWarehouseId): void
    {
        if (! Schema::hasTable('stock_balances')) {
            return;
        }

        $sourceBalances = DB::table('stock_balances')
            ->where('warehouse_id', $fromWarehouseId)
            ->get();

        foreach ($sourceBalances as $sourceBalance) {
            $targetBalance = DB::table('stock_balances')
                ->where('warehouse_id', $toWarehouseId)
                ->where('item_id', $sourceBalance->item_id)
                ->first();

            if (! $targetBalance) {
                DB::table('stock_balances')
                    ->where('id', $sourceBalance->id)
                    ->update(['warehouse_id' => $toWarehouseId]);

                continue;
            }

            $sourceQuantity = (float) $sourceBalance->quantity_on_hand;
            $targetQuantity = (float) $targetBalance->quantity_on_hand;
            $newQuantity = $sourceQuantity + $targetQuantity;
            $averageCost = $newQuantity > 0
                ? (($sourceQuantity * (float) $sourceBalance->average_cost) + ($targetQuantity * (float) $targetBalance->average_cost)) / $newQuantity
                : (float) $targetBalance->average_cost;

            DB::table('stock_balances')->where('id', $targetBalance->id)->update([
                'quantity_on_hand' => $newQuantity,
                'quantity_reserved' => (float) $targetBalance->quantity_reserved + (float) $sourceBalance->quantity_reserved,
                'average_cost' => $averageCost,
                'last_movement_at' => max($sourceBalance->last_movement_at, $targetBalance->last_movement_at),
                'updated_at' => now(),
            ]);

            DB::table('stock_balances')->where('id', $sourceBalance->id)->delete();
        }
    }
};
