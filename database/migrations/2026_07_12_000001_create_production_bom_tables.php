<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_boms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('number'); $table->string('name'); $table->string('production_type', 24);
            $table->foreignId('finished_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('base_output_quantity', 18, 6);
            $table->foreignId('output_uom_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('default_source_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('default_destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->unsignedInteger('version'); $table->date('effective_from')->nullable(); $table->date('effective_to')->nullable();
            $table->string('status', 24)->default('draft'); $table->text('notes')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('activated_at')->nullable();
            $table->foreignId('inactivated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('inactivated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'number']); $table->unique(['company_id', 'finished_item_id', 'version'], 'production_boms_item_version_unique');
            $table->index(['company_id', 'branch_id', 'status']); $table->index(['finished_item_id', 'effective_from', 'effective_to'], 'production_boms_effective_index');
        });
        Schema::create('production_bom_materials', function (Blueprint $table): void {
            $table->id(); $table->foreignId('production_bom_id')->constrained()->cascadeOnDelete(); $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6); $table->foreignId('uom_id')->constrained('units_of_measure')->restrictOnDelete(); $table->string('quantity_type', 24);
            $table->foreignId('source_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete(); $table->unsignedInteger('sequence'); $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['production_bom_id', 'sequence']); $table->index(['production_bom_id', 'item_id']); $table->index('source_warehouse_id');
        });
        Schema::create('production_bom_outputs', function (Blueprint $table): void {
            $table->id(); $table->foreignId('production_bom_id')->constrained()->cascadeOnDelete(); $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('output_type', 24); $table->decimal('quantity', 18, 6); $table->foreignId('uom_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete(); $table->unsignedInteger('sequence'); $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['production_bom_id', 'sequence']); $table->index(['production_bom_id', 'output_type']); $table->index(['item_id', 'destination_warehouse_id']);
        });
        DB::table('document_sequences')->updateOrInsert(['code' => 'PRODUCTION_BOM'], ['document_type'=>'PRODUCTION_BOM','name'=>'Production Formula','prefix'=>'BOM','date_format'=>'YYYYMM','digits'=>4,'reset_type'=>'monthly','period_type'=>'monthly','padding'=>4,'separator'=>'/','is_active'=>true,'updated_at'=>now(),'created_at'=>now()]);
    }
    public function down(): void { Schema::dropIfExists('production_bom_outputs'); Schema::dropIfExists('production_bom_materials'); Schema::dropIfExists('production_boms'); DB::table('document_sequences')->where('code', 'PRODUCTION_BOM')->delete(); }
};
