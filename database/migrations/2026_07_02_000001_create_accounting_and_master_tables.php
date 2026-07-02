<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('precision')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('default_inventory_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('default_cogs_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('default_sales_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('default_purchase_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('default_wip_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('default_adjustment_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('default_waste_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->enum('type', ['raw_material', 'packaging_material', 'finished_goods', 'consumable', 'non_stock']);
            $table->foreignId('item_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->boolean('is_stock_item')->default(true);
            $table->decimal('standard_cost', 18, 4)->default(0);
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('sales_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('wip_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('adjustment_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('waste_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['raw_material', 'production', 'finished_goods', 'reject', 'transit']);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('accounting_accounts');
    }
};
