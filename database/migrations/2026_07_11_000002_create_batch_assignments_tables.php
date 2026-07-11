<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->date('assignment_date');
            $table->string('status', 20)->default('draft')->index();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_id', 'warehouse_id', 'assignment_date'], 'batch_assignments_scope_date_index');
        });

        Schema::create('batch_assignment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('source_batch_no')->nullable();
            $table->string('destination_batch_no');
            $table->date('destination_expiry_date')->nullable();
            $table->decimal('quantity', 18, 6);
            $table->foreignId('unit_of_measure_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['item_id', 'destination_batch_no'], 'batch_assignment_lines_item_batch_index');
        });

        DB::table('document_sequences')->updateOrInsert(
            ['code' => 'BATCH_ASSIGNMENT'],
            ['document_type' => 'BATCH_ASSIGNMENT', 'name' => 'Batch Assignment', 'prefix' => 'BAS', 'date_format' => 'YYYYMM', 'digits' => 5, 'reset_type' => 'monthly', 'period_type' => 'monthly', 'padding' => 5, 'separator' => '-', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_assignment_lines');
        Schema::dropIfExists('batch_assignments');
        // Sequence configuration is retained because issued BAS numbers may
        // remain referenced by audit exports or restored document data.
    }
};
