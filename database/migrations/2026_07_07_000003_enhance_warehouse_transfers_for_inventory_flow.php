<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transfers', function (Blueprint $table): void {
            if (! Schema::hasColumn('warehouse_transfers', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('warehouse_transfers', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            }

            if (! Schema::hasColumn('warehouse_transfers', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('warehouse_transfers', 'posted_by')) {
                $table->foreignId('posted_by')->nullable()->after('posted_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('warehouse_transfers', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('posted_by');
            }

            if (! Schema::hasColumn('warehouse_transfers', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('warehouse_transfer_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('warehouse_transfer_lines', 'batch_no')) {
                $table->string('batch_no')->nullable()->after('item_id');
            }

            if (! Schema::hasColumn('warehouse_transfer_lines', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transfer_lines', function (Blueprint $table): void {
            foreach (['expiry_date', 'batch_no'] as $column) {
                if (Schema::hasColumn('warehouse_transfer_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('warehouse_transfers', function (Blueprint $table): void {
            foreach (['cancelled_by', 'posted_by', 'branch_id', 'company_id'] as $column) {
                if (Schema::hasColumn('warehouse_transfers', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['cancelled_at', 'posted_at'] as $column) {
                if (Schema::hasColumn('warehouse_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
