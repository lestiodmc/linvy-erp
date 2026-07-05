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
            $table->string('type')->default('BASE');
            $table->foreignId('base_unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->decimal('conversion_factor', 18, 6)->default(1);
            $table->unsignedTinyInteger('precision')->default(2);
            $table->boolean('allow_decimal')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('item_type')->default('INVENTORY');
            $table->foreignId('default_warehouse_type_id')->nullable()->constrained('warehouse_types')->nullOnDelete();
            $table->boolean('allow_purchase')->default(true);
            $table->boolean('allow_sales')->default(false);
            $table->text('description')->nullable();
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

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
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

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->enum('type', ['raw_material', 'packaging_material', 'finished_goods', 'consumable', 'non_stock']);
            $table->foreignId('item_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type')->default('INVENTORY');
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->foreignId('base_unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('purchase_unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('sales_unit_id')->nullable()->constrained('units_of_measure')->nullOnDelete();
            $table->foreignId('default_warehouse_type_id')->nullable()->constrained('warehouse_types')->nullOnDelete();
            $table->boolean('track_inventory')->default(true);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('is_batch_tracked')->default(false);
            $table->boolean('is_serial_tracked')->default(false);
            $table->boolean('has_expiry_date')->default(false);
            $table->foreignId('default_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('purchase_price', 18, 2)->default(0);
            $table->decimal('minimum_order_qty', 18, 6)->default(0);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->boolean('blocked_purchase')->default(false);
            $table->decimal('sales_price', 18, 2)->default(0);
            $table->decimal('minimum_sales_qty', 18, 6)->default(0);
            $table->boolean('blocked_sales')->default(false);
            $table->string('barcode')->nullable();
            $table->text('description')->nullable();
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
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_type_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->nullable();
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
        Schema::dropIfExists('brands');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('warehouse_types');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('accounting_accounts');
    }
};
