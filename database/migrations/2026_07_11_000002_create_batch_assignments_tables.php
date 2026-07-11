<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('batch_assignments', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->date('assignment_date');
            $table->foreignId('company_id')->constrained()->restrictOnDelete(); $table->foreignId('branch_id')->constrained()->restrictOnDelete(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index(); $table->string('reason')->nullable(); $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable(); $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->index(['branch_id','warehouse_id','assignment_date']);
        });
        Schema::create('batch_assignment_lines', function (Blueprint $table) {
            $table->id(); $table->foreignId('batch_assignment_id')->constrained()->cascadeOnDelete(); $table->foreignId('item_id')->constrained()->restrictOnDelete(); $table->string('source_batch_no')->nullable(); $table->string('destination_batch_no'); $table->date('destination_expiry_date')->nullable(); $table->decimal('quantity',18,6); $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete(); $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['item_id','destination_batch_no']);
        });
    }
    public function down(): void { Schema::dropIfExists('batch_assignment_lines'); Schema::dropIfExists('batch_assignments'); }
};
