<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $documents = [
        'PR' => ['old' => 'purchase_request', 'name' => 'Purchase Request', 'table' => 'purchase_requests'],
        'PO' => ['old' => 'purchase_order', 'name' => 'Purchase Order', 'table' => 'purchase_orders'],
        'RCV' => ['old' => 'receiving', 'name' => 'Receiving', 'table' => 'receivings'],
        'SO' => ['old' => 'sales_order', 'name' => 'Sales Order', 'table' => 'sales_orders'],
        'DO' => ['old' => 'delivery_order', 'name' => 'Delivery Order', 'table' => 'delivery_orders'],
        'TRF' => ['old' => 'warehouse_transfer', 'name' => 'Warehouse Transfer', 'table' => 'warehouse_transfers'],
        'ADJ' => ['old' => 'stock_adjustment', 'name' => 'Stock Adjustment', 'table' => 'stock_adjustments'],
        'PRD' => ['old' => 'production', 'name' => 'Production / Repacking', 'table' => 'productions'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('document_sequences')) {
            return;
        }

        foreach ($this->documents as $code => $document) {
            $oldSequence = DB::table('document_sequences')->where('document_type', $document['old'])->first();
            $codeSequence = DB::table('document_sequences')->where('document_type', $code)->first();

            if ($oldSequence && ! $codeSequence) {
                DB::table('document_sequences')
                    ->where('id', $oldSequence->id)
                    ->update(['document_type' => $code]);
            } elseif ($oldSequence && $codeSequence) {
                DB::table('document_sequences')->where('id', $oldSequence->id)->delete();
            }

            $sequence = DB::table('document_sequences')->where('document_type', $code)->first();

            DB::table('document_sequences')->updateOrInsert(
                ['document_type' => $code],
                [
                    'name' => $document['name'],
                    'prefix' => $code,
                    'period_type' => 'monthly',
                    'current_period' => now()->format('Ym'),
                    'last_number' => (int) ($sequence->last_number ?? 0),
                    'padding' => 4,
                    'separator' => '/',
                    'is_active' => true,
                    'created_at' => $sequence->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
