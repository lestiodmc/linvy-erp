<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('request_date');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('department')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'closed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->date('required_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('converted_quantity', 18, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->constrained()->nullOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'partially_received', 'fully_received', 'closed', 'cancelled'])->default('draft');
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_request_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->decimal('remaining_quantity', 18, 4)->default(0);
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('tax_percent', 8, 4)->default(0);
            $table->decimal('subtotal', 18, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('receivings', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('received_date');
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->string('supplier_delivery_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('receiving_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('ordered_quantity', 18, 4);
            $table->decimal('previously_received_quantity', 18, 4)->default(0);
            $table->decimal('received_quantity', 18, 4);
            $table->decimal('remaining_quantity', 18, 4)->default(0);
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('action');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('receiving_lines');
        Schema::dropIfExists('receivings');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_request_lines');
        Schema::dropIfExists('purchase_requests');
    }
};
