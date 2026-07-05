<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['purchase_requests', 'purchase_orders', 'receivings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'company_id')) {
                    $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
                }
            });
        }

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->unique(['company_id', 'number'], 'purchase_requests_company_number_unique');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unique(['company_id', 'number'], 'purchase_orders_company_number_unique');
        });

        Schema::table('receivings', function (Blueprint $table) {
            $table->unique(['company_id', 'number'], 'receivings_company_number_unique');
        });
    }

    public function down(): void
    {
        foreach ([
            'purchase_requests' => 'purchase_requests_company_number_unique',
            'purchase_orders' => 'purchase_orders_company_number_unique',
            'receivings' => 'receivings_company_number_unique',
        ] as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
                $table->dropUnique($indexName);

                foreach (['branch_id', 'company_id'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
            });
        }
    }
};
