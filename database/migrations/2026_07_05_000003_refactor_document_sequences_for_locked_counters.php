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
            if (! Schema::hasColumn('document_sequences', 'code')) {
                $table->string('code')->nullable()->after('id');
            }

            if (! Schema::hasColumn('document_sequences', 'date_format')) {
                $table->string('date_format')->default('YYYYMM')->after('prefix');
            }

            if (! Schema::hasColumn('document_sequences', 'digits')) {
                $table->unsignedTinyInteger('digits')->default(5)->after('date_format');
            }

            if (! Schema::hasColumn('document_sequences', 'reset_type')) {
                $table->string('reset_type')->default('monthly')->after('digits');
            }

            if (! Schema::hasColumn('document_sequences', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('reset_type')->constrained('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('document_sequences', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            }
        });

        DB::table('document_sequences')->whereNull('code')->update([
            'code' => DB::raw('document_type'),
            'date_format' => 'YYYYMM',
            'digits' => DB::raw('padding'),
            'reset_type' => DB::raw("case when period_type = 'yearly' then 'yearly' else 'monthly' end"),
        ]);

        Schema::table('document_sequences', function (Blueprint $table) {
            if (Schema::hasColumn('document_sequences', 'code')) {
                $table->string('code')->nullable(false)->change();
                $table->unique(['code', 'company_id', 'branch_id'], 'document_sequences_code_scope_unique');
            }
        });

        Schema::create('document_sequence_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_sequence_id')->constrained('document_sequences')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('period');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(
                ['document_sequence_id', 'company_id', 'branch_id', 'period'],
                'document_sequence_counter_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequence_counters');

        Schema::table('document_sequences', function (Blueprint $table) {
            $table->dropUnique('document_sequences_code_scope_unique');

            foreach (['branch_id', 'company_id'] as $column) {
                if (Schema::hasColumn('document_sequences', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['reset_type', 'digits', 'date_format', 'code'] as $column) {
                if (Schema::hasColumn('document_sequences', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
