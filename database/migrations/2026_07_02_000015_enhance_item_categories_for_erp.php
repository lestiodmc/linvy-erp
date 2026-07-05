<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('item_categories', 'item_type')) {
                $table->string('item_type')->default('INVENTORY')->after('name');
            }

            if (! Schema::hasColumn('item_categories', 'default_warehouse_type_id')) {
                $table->foreignId('default_warehouse_type_id')
                    ->nullable()
                    ->after('item_type')
                    ->constrained('warehouse_types')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('item_categories', 'allow_purchase')) {
                $table->boolean('allow_purchase')->default(true)->after('default_warehouse_type_id');
            }

            if (! Schema::hasColumn('item_categories', 'allow_sales')) {
                $table->boolean('allow_sales')->default(false)->after('allow_purchase');
            }

            if (! Schema::hasColumn('item_categories', 'description')) {
                $table->text('description')->nullable()->after('allow_sales');
            }
        });
    }

    public function down(): void
    {
        //
    }
};
