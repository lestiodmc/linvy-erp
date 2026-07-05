<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'supplier_group')) {
                $table->string('supplier_group')->nullable()->after('name');
            }

            if (! Schema::hasColumn('suppliers', 'supplier_type')) {
                $table->string('supplier_type')->default('LOCAL')->after('supplier_group');
                $table->index('supplier_type');
            }

            if (! Schema::hasColumn('suppliers', 'tax_number')) {
                $table->string('tax_number')->nullable()->after('supplier_type');
            }

            if (! Schema::hasColumn('suppliers', 'mobile')) {
                $table->string('mobile')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('suppliers', 'website')) {
                $table->string('website')->nullable()->after('email');
            }

            if (! Schema::hasColumn('suppliers', 'city')) {
                $table->string('city')->nullable()->after('address');
                $table->index('city');
            }

            if (! Schema::hasColumn('suppliers', 'province')) {
                $table->string('province')->nullable()->after('city');
            }

            if (! Schema::hasColumn('suppliers', 'country')) {
                $table->string('country')->nullable()->after('province');
            }

            if (! Schema::hasColumn('suppliers', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }

            if (! Schema::hasColumn('suppliers', 'default_currency_id')) {
                $table->unsignedBigInteger('default_currency_id')->nullable()->after('postal_code');
                $table->index('default_currency_id');
            }

            if (! Schema::hasColumn('suppliers', 'payment_term_id')) {
                $table->unsignedBigInteger('payment_term_id')->nullable()->after('default_currency_id');
                $table->index('payment_term_id');
            }

            if (! Schema::hasColumn('suppliers', 'lead_time_days')) {
                $table->unsignedInteger('lead_time_days')->default(0)->after('payment_term_id');
            }

            if (! Schema::hasColumn('suppliers', 'default_tax_id')) {
                $table->unsignedBigInteger('default_tax_id')->nullable()->after('lead_time_days');
                $table->index('default_tax_id');
            }

            if (! Schema::hasColumn('suppliers', 'ap_account_id')) {
                $table->foreignId('ap_account_id')->nullable()->after('default_tax_id')->constrained('accounting_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('suppliers', 'blocked_purchase')) {
                $table->boolean('blocked_purchase')->default(false)->after('ap_account_id');
                $table->index('blocked_purchase');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'ap_account_id')) {
                $table->dropConstrainedForeignId('ap_account_id');
            }

            $indexes = [
                'supplier_type',
                'city',
                'default_currency_id',
                'payment_term_id',
                'default_tax_id',
                'blocked_purchase',
            ];

            foreach ($indexes as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropIndex([$column]);
                }
            }

            $columns = [
                'supplier_group',
                'supplier_type',
                'tax_number',
                'mobile',
                'website',
                'city',
                'province',
                'country',
                'postal_code',
                'default_currency_id',
                'payment_term_id',
                'lead_time_days',
                'default_tax_id',
                'blocked_purchase',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
