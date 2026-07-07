<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batch_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->string('batch_no');
            $table->date('expiry_date')->nullable();
            $table->decimal('qty_on_hand', 18, 6)->default(0);
            $table->decimal('qty_reserved', 18, 6)->default(0);
            $table->decimal('qty_available', 18, 6)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'warehouse_id', 'item_id', 'batch_no', 'expiry_date'], 'stock_batch_balances_unique');
            $table->index(['warehouse_id', 'item_id', 'batch_no'], 'stock_batch_balances_warehouse_item_batch_index');
            $table->index(['branch_id', 'item_id'], 'stock_batch_balances_branch_item_index');
        });

        $this->backfillBatchBalances();
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batch_balances');
    }

    private function backfillBatchBalances(): void
    {
        $batches = DB::table('stock_movements')
            ->select([
                'company_id',
                'branch_id',
                'warehouse_id',
                'item_id',
                'batch_no',
                'expiry_date',
                DB::raw('SUM(COALESCE(quantity_in, 0) - COALESCE(quantity_out, 0)) as qty_on_hand'),
            ])
            ->whereNotNull('batch_no')
            ->where('batch_no', '!=', '')
            ->groupBy('company_id', 'branch_id', 'warehouse_id', 'item_id', 'batch_no', 'expiry_date')
            ->get();

        foreach ($batches as $batch) {
            $qtyOnHand = (float) $batch->qty_on_hand;

            DB::table('stock_batch_balances')->updateOrInsert(
                [
                    'company_id' => $batch->company_id,
                    'branch_id' => $batch->branch_id,
                    'warehouse_id' => $batch->warehouse_id,
                    'item_id' => $batch->item_id,
                    'batch_no' => $batch->batch_no,
                    'expiry_date' => $batch->expiry_date,
                ],
                [
                    'qty_on_hand' => $qtyOnHand,
                    'qty_reserved' => 0,
                    'qty_available' => $qtyOnHand,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
};
