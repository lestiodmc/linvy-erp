<?php

use App\Models\StockAdjustment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table): void {
            if (! Schema::hasColumn('stock_adjustments', 'reason_code')) {
                $table->string('reason_code', 40)->nullable()->after('status')->index();
            }
        });

        DB::table('stock_adjustments')
            ->whereNull('reason_code')
            ->update(['reason_code' => StockAdjustment::REASON_CORRECTION]);
    }

    public function down(): void
    {
        Schema::table('stock_adjustments', function (Blueprint $table): void {
            if (Schema::hasColumn('stock_adjustments', 'reason_code')) {
                $table->dropIndex('stock_adjustments_reason_code_index');
                $table->dropColumn('reason_code');
            }
        });
    }
};
