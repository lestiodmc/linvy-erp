<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            if (! Schema::hasColumn('document_sequences', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (Schema::hasColumn('document_sequences', 'separator')) {
                $table->string('separator', 3)->nullable()->default('-')->change();
            }
        });

        DB::table('document_sequences')
            ->whereNull('separator')
            ->update(['separator' => '-']);
    }

    public function down(): void
    {
        Schema::table('document_sequences', function (Blueprint $table) {
            if (Schema::hasColumn('document_sequences', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('document_sequences', 'separator')) {
                $table->string('separator')->default('/')->change();
            }
        });
    }
};
