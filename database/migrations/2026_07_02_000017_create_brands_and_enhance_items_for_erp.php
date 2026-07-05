<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('item_category_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('items', 'item_type')) {
                $table->string('item_type')->default('INVENTORY')->after('brand_id');
            }

            if (! Schema::hasColumn('items', 'base_unit_id')) {
                $table->foreignId('base_unit_id')->nullable()->after('unit_of_measure_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('items', 'purchase_unit_id')) {
                $table->foreignId('purchase_unit_id')->nullable()->after('base_unit_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('items', 'sales_unit_id')) {
                $table->foreignId('sales_unit_id')->nullable()->after('purchase_unit_id')->constrained('units_of_measure')->nullOnDelete();
            }

            if (! Schema::hasColumn('items', 'track_inventory')) {
                $table->boolean('track_inventory')->default(true)->after('default_warehouse_type_id');
            }

            if (! Schema::hasColumn('items', 'allow_negative_stock')) {
                $table->boolean('allow_negative_stock')->default(false)->after('track_inventory');
            }

            if (! Schema::hasColumn('items', 'is_batch_tracked')) {
                $table->boolean('is_batch_tracked')->default(false)->after('allow_negative_stock');
            }

            if (! Schema::hasColumn('items', 'is_serial_tracked')) {
                $table->boolean('is_serial_tracked')->default(false)->after('is_batch_tracked');
            }

            if (! Schema::hasColumn('items', 'has_expiry_date')) {
                $table->boolean('has_expiry_date')->default(false)->after('is_serial_tracked');
            }

            if (! Schema::hasColumn('items', 'default_supplier_id')) {
                $table->foreignId('default_supplier_id')->nullable()->after('has_expiry_date')->constrained('suppliers')->nullOnDelete();
            }

            if (! Schema::hasColumn('items', 'purchase_price')) {
                $table->decimal('purchase_price', 18, 2)->default(0)->after('default_supplier_id');
            }

            if (! Schema::hasColumn('items', 'minimum_order_qty')) {
                $table->decimal('minimum_order_qty', 18, 6)->default(0)->after('purchase_price');
            }

            if (! Schema::hasColumn('items', 'lead_time_days')) {
                $table->unsignedInteger('lead_time_days')->default(0)->after('minimum_order_qty');
            }

            if (! Schema::hasColumn('items', 'blocked_purchase')) {
                $table->boolean('blocked_purchase')->default(false)->after('lead_time_days');
            }

            if (! Schema::hasColumn('items', 'sales_price')) {
                $table->decimal('sales_price', 18, 2)->default(0)->after('blocked_purchase');
            }

            if (! Schema::hasColumn('items', 'minimum_sales_qty')) {
                $table->decimal('minimum_sales_qty', 18, 6)->default(0)->after('sales_price');
            }

            if (! Schema::hasColumn('items', 'blocked_sales')) {
                $table->boolean('blocked_sales')->default(false)->after('minimum_sales_qty');
            }

            if (! Schema::hasColumn('items', 'barcode')) {
                $table->string('barcode')->nullable()->after('blocked_sales');
            }

            if (! Schema::hasColumn('items', 'description')) {
                $table->text('description')->nullable()->after('barcode');
            }
        });

        if (Schema::hasColumn('items', 'unit_of_measure_id')) {
            DB::table('items')->whereNull('base_unit_id')->update(['base_unit_id' => DB::raw('unit_of_measure_id')]);
            DB::table('items')->whereNull('purchase_unit_id')->update(['purchase_unit_id' => DB::raw('unit_of_measure_id')]);
            DB::table('items')->whereNull('sales_unit_id')->update(['sales_unit_id' => DB::raw('unit_of_measure_id')]);
        }

        if (Schema::hasColumn('items', 'type')) {
            DB::table('items')
                ->whereIn('type', ['raw_material', 'packaging_material', 'finished_goods', 'consumable'])
                ->update(['item_type' => 'INVENTORY']);

            DB::table('items')
                ->where('type', 'non_stock')
                ->update(['item_type' => 'NON_INVENTORY']);
        }

        if (Schema::hasColumn('items', 'is_stock_item')) {
            DB::table('items')->update(['track_inventory' => DB::raw('is_stock_item')]);
        }

        if (Schema::hasColumn('items', 'notes')) {
            DB::table('items')->whereNull('description')->update(['description' => DB::raw('notes')]);
        }
    }

    public function down(): void
    {
        //
    }
};
