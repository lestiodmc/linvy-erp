<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receiving_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_lines', 'batch_no')) {
                $table->string('batch_no')->nullable()->after('unit_cost');
            }

            if (! Schema::hasColumn('receiving_lines', 'serial_numbers')) {
                $table->text('serial_numbers')->nullable()->after('batch_no');
            }

            if (! Schema::hasColumn('receiving_lines', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('serial_numbers');
            }
        });
    }

    public function down(): void
    {
        Schema::table('receiving_lines', function (Blueprint $table) {
            if (Schema::hasColumn('receiving_lines', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }

            if (Schema::hasColumn('receiving_lines', 'serial_numbers')) {
                $table->dropColumn('serial_numbers');
            }

            if (Schema::hasColumn('receiving_lines', 'batch_no')) {
                $table->dropColumn('batch_no');
            }
        });
    }
};
