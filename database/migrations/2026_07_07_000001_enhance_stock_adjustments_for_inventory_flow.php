<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_adjustments', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('number')->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustments', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustments', 'posted_by')) {
                $table->foreignId('posted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustments', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('posted_by');
            }

            $table->index(['company_id', 'branch_id', 'warehouse_id'], 'stock_adjustments_company_branch_warehouse_index');
            $table->index(['adjustment_date', 'status'], 'stock_adjustments_date_status_index');
        });

        Schema::table('stock_adjustment_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_adjustment_lines', 'uom_id')) {
                $table->foreignId('uom_id')->nullable()->after('item_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_adjustment_lines', 'system_qty')) {
                $table->decimal('system_qty', 18, 6)->default(0)->after('uom_id');
            }

            if (! Schema::hasColumn('stock_adjustment_lines', 'counted_qty')) {
                $table->decimal('counted_qty', 18, 6)->default(0)->after('system_qty');
            }

            if (! Schema::hasColumn('stock_adjustment_lines', 'adjustment_qty')) {
                $table->decimal('adjustment_qty', 18, 6)->default(0)->after('counted_qty');
            }

            if (! Schema::hasColumn('stock_adjustment_lines', 'batch_no')) {
                $table->string('batch_no')->nullable()->after('unit_cost');
            }

            if (! Schema::hasColumn('stock_adjustment_lines', 'serial_numbers')) {
                $table->text('serial_numbers')->nullable()->after('batch_no');
            }

            if (! Schema::hasColumn('stock_adjustment_lines', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('serial_numbers');
            }

            if (! Schema::hasColumn('stock_adjustment_lines', 'remarks')) {
                $table->text('remarks')->nullable()->after('expiry_date');
            }

            $table->index(['stock_adjustment_id', 'item_id'], 'stock_adjustment_lines_adjustment_item_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_adjustment_lines', function (Blueprint $table) {
            $table->dropIndex('stock_adjustment_lines_adjustment_item_index');

            foreach (['uom_id'] as $column) {
                if (Schema::hasColumn('stock_adjustment_lines', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['system_qty', 'counted_qty', 'adjustment_qty', 'batch_no', 'serial_numbers', 'expiry_date', 'remarks'] as $column) {
                if (Schema::hasColumn('stock_adjustment_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropIndex('stock_adjustments_company_branch_warehouse_index');
            $table->dropIndex('stock_adjustments_date_status_index');

            foreach (['posted_by', 'created_by', 'branch_id', 'company_id'] as $column) {
                if (Schema::hasColumn('stock_adjustments', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            if (Schema::hasColumn('stock_adjustments', 'posted_at')) {
                $table->dropColumn('posted_at');
            }
        });
    }
};
