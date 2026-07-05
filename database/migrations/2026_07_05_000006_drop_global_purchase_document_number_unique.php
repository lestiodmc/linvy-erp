<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'purchase_requests' => 'purchase_requests_number_unique',
        'purchase_orders' => 'purchase_orders_number_unique',
        'receivings' => 'receivings_number_unique',
    ];

    public function up(): void
    {
        foreach ($this->indexes as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->unique('number', $indexName);
            });
        }
    }
};
