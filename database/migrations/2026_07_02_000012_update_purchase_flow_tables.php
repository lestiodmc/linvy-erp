<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_requests')) {
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
        }

        if (! Schema::hasTable('purchase_request_lines')) {
            Schema::create('purchase_request_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('item_id')->constrained()->restrictOnDelete();
                $table->string('description')->nullable();
                $table->decimal('quantity', 18, 4);
                $table->foreignId('unit_id')->nullable()->constrained('units_of_measure')->restrictOnDelete();
                $table->date('required_date')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('converted_quantity', 18, 4)->default(0);
                $table->timestamps();
            });
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_orders', 'purchase_request_id')) {
                $table->foreignId('purchase_request_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_lines', 'purchase_request_line_id')) {
                $table->foreignId('purchase_request_line_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('purchase_order_lines', 'description')) {
                $table->string('description')->nullable()->after('item_id');
            }
            if (! Schema::hasColumn('purchase_order_lines', 'remaining_quantity')) {
                $table->decimal('remaining_quantity', 18, 4)->default(0)->after('received_quantity');
            }
            if (! Schema::hasColumn('purchase_order_lines', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('remaining_quantity')->constrained('units_of_measure')->restrictOnDelete();
            }
            if (! Schema::hasColumn('purchase_order_lines', 'tax_percent')) {
                $table->decimal('tax_percent', 8, 4)->default(0)->after('unit_price');
            }
            if (! Schema::hasColumn('purchase_order_lines', 'subtotal')) {
                $table->decimal('subtotal', 18, 4)->default(0)->after('tax_percent');
            }
        });

        if (Schema::hasColumn('purchase_order_lines', 'unit_of_measure_id') && Schema::hasColumn('purchase_order_lines', 'unit_id')) {
            DB::table('purchase_order_lines')->whereNull('unit_id')->update(['unit_id' => DB::raw('unit_of_measure_id')]);
        }

        if (Schema::hasColumn('purchase_order_lines', 'line_total') && Schema::hasColumn('purchase_order_lines', 'subtotal')) {
            DB::table('purchase_order_lines')->where('subtotal', 0)->update(['subtotal' => DB::raw('line_total')]);
        }

        DB::table('purchase_order_lines')
            ->where('remaining_quantity', 0)
            ->update(['remaining_quantity' => DB::raw('quantity - received_quantity')]);

        Schema::table('receiving_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_lines', 'description')) {
                $table->string('description')->nullable()->after('item_id');
            }
            if (! Schema::hasColumn('receiving_lines', 'ordered_quantity')) {
                $table->decimal('ordered_quantity', 18, 4)->default(0)->after('description');
            }
            if (! Schema::hasColumn('receiving_lines', 'previously_received_quantity')) {
                $table->decimal('previously_received_quantity', 18, 4)->default(0)->after('ordered_quantity');
            }
            if (! Schema::hasColumn('receiving_lines', 'received_quantity')) {
                $table->decimal('received_quantity', 18, 4)->default(0)->after('previously_received_quantity');
            }
            if (! Schema::hasColumn('receiving_lines', 'remaining_quantity')) {
                $table->decimal('remaining_quantity', 18, 4)->default(0)->after('received_quantity');
            }
            if (! Schema::hasColumn('receiving_lines', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('remaining_quantity')->constrained('units_of_measure')->restrictOnDelete();
            }
        });

        if (Schema::hasColumn('receiving_lines', 'quantity') && Schema::hasColumn('receiving_lines', 'received_quantity')) {
            DB::table('receiving_lines')->where('received_quantity', 0)->update(['received_quantity' => DB::raw('quantity')]);
        }

        if (Schema::hasColumn('receiving_lines', 'unit_of_measure_id') && Schema::hasColumn('receiving_lines', 'unit_id')) {
            DB::table('receiving_lines')->whereNull('unit_id')->update(['unit_id' => DB::raw('unit_of_measure_id')]);
        }

        if (! Schema::hasTable('approval_logs')) {
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

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM('draft','submitted','approved','partially_received','fully_received','closed','cancelled') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE warehouses MODIFY type ENUM('raw_material','packaging','production','finished_goods','reject','transit') NOT NULL");
        }

        if (Schema::hasTable('document_sequences')) {
            DB::table('document_sequences')->updateOrInsert(
                ['document_type' => 'purchase_request'],
                [
                    'name' => 'Purchase Request',
                    'prefix' => 'PR',
                    'period_type' => 'monthly',
                    'padding' => 4,
                    'separator' => '/',
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
