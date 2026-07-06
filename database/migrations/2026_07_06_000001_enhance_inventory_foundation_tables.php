<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_movements', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_movements', 'uom_id')) {
                $table->foreignId('uom_id')->nullable()->after('item_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_movements', 'base_uom_id')) {
                $table->foreignId('base_uom_id')->nullable()->after('uom_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_movements', 'transaction_type')) {
                $table->string('transaction_type', 32)->nullable()->after('base_uom_id')->index();
            }

            if (! Schema::hasColumn('stock_movements', 'transaction_id')) {
                $table->unsignedBigInteger('transaction_id')->nullable()->after('transaction_type');
            }

            if (! Schema::hasColumn('stock_movements', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->after('transaction_id')->index();
            }

            if (! Schema::hasColumn('stock_movements', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->after('transaction_number')->index();
            }

            if (! Schema::hasColumn('stock_movements', 'qty')) {
                $table->decimal('qty', 18, 6)->default(0)->after('movement_type');
            }

            if (! Schema::hasColumn('stock_movements', 'base_qty')) {
                $table->decimal('base_qty', 18, 6)->default(0)->after('qty');
            }

            if (! Schema::hasColumn('stock_movements', 'batch_no')) {
                $table->string('batch_no')->nullable()->after('total_cost');
            }

            if (! Schema::hasColumn('stock_movements', 'serial_no')) {
                $table->string('serial_no')->nullable()->after('batch_no');
            }

            if (! Schema::hasColumn('stock_movements', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('serial_no');
            }

            if (! Schema::hasColumn('stock_movements', 'remarks')) {
                $table->text('remarks')->nullable()->after('reference_id');
            }

            if (! Schema::hasColumn('stock_movements', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            $table->index('movement_type', 'stock_movements_movement_type_index');
            $table->index(['company_id', 'branch_id', 'warehouse_id', 'item_id'], 'stock_movements_company_branch_warehouse_item_index');
            $table->index(['transaction_type', 'transaction_id'], 'stock_movements_transaction_type_id_index');
            $table->index(['transaction_date', 'item_id'], 'stock_movements_transaction_date_item_index');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE stock_movements MODIFY movement_type VARCHAR(32) NOT NULL');
            DB::statement('ALTER TABLE stock_movements MODIFY unit_cost DECIMAL(18, 6) NULL');
            DB::statement('ALTER TABLE stock_movements MODIFY total_cost DECIMAL(18, 6) NULL');
        }

        Schema::table('stock_balances', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_balances', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_balances', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_balances', 'uom_id')) {
                $table->foreignId('uom_id')->nullable()->after('item_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_balances', 'base_uom_id')) {
                $table->foreignId('base_uom_id')->nullable()->after('uom_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_balances', 'qty_on_hand')) {
                $table->decimal('qty_on_hand', 18, 6)->default(0)->after('base_uom_id');
            }

            if (! Schema::hasColumn('stock_balances', 'qty_reserved')) {
                $table->decimal('qty_reserved', 18, 6)->default(0)->after('qty_on_hand');
            }

            if (! Schema::hasColumn('stock_balances', 'qty_available')) {
                $table->decimal('qty_available', 18, 6)->default(0)->after('qty_reserved');
            }

            if (! Schema::hasColumn('stock_balances', 'qty_incoming')) {
                $table->decimal('qty_incoming', 18, 6)->default(0)->after('qty_available');
            }

            if (! Schema::hasColumn('stock_balances', 'qty_outgoing')) {
                $table->decimal('qty_outgoing', 18, 6)->default(0)->after('qty_incoming');
            }

            if (! Schema::hasColumn('stock_balances', 'last_cost')) {
                $table->decimal('last_cost', 18, 6)->default(0)->after('average_cost');
            }

            if (! Schema::hasColumn('stock_balances', 'total_value')) {
                $table->decimal('total_value', 18, 6)->default(0)->after('last_cost');
            }

            if (! Schema::hasColumn('stock_balances', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('total_value')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_balances', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            $table->unique(['company_id', 'branch_id', 'warehouse_id', 'item_id'], 'stock_balances_company_branch_warehouse_item_unique');
            $table->index(['branch_id', 'item_id'], 'stock_balances_branch_item_index');
            $table->index(['warehouse_id', 'item_id'], 'stock_balances_warehouse_item_index');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE stock_balances MODIFY average_cost DECIMAL(18, 6) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_movement_type_index');
            $table->dropIndex('stock_movements_company_branch_warehouse_item_index');
            $table->dropIndex('stock_movements_transaction_type_id_index');
            $table->dropIndex('stock_movements_transaction_date_item_index');

            foreach ([
                'updated_by',
                'base_uom_id',
                'uom_id',
                'branch_id',
                'company_id',
            ] as $column) {
                if (Schema::hasColumn('stock_movements', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach ([
                'transaction_type',
                'transaction_id',
                'transaction_number',
                'transaction_date',
                'qty',
                'base_qty',
                'batch_no',
                'serial_no',
                'expiry_date',
                'remarks',
            ] as $column) {
                if (Schema::hasColumn('stock_movements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropUnique('stock_balances_company_branch_warehouse_item_unique');
            $table->dropIndex('stock_balances_branch_item_index');
            $table->dropIndex('stock_balances_warehouse_item_index');

            foreach ([
                'updated_by',
                'created_by',
                'base_uom_id',
                'uom_id',
                'branch_id',
                'company_id',
            ] as $column) {
                if (Schema::hasColumn('stock_balances', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach ([
                'qty_on_hand',
                'qty_reserved',
                'qty_available',
                'qty_incoming',
                'qty_outgoing',
                'last_cost',
                'total_value',
            ] as $column) {
                if (Schema::hasColumn('stock_balances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
