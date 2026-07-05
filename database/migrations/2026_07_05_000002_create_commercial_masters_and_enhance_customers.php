<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('due_days')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_base_currency')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('tax_type');
            $table->decimal('rate', 9, 4)->default(0);
            $table->boolean('is_inclusive')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tax_type');
        });

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'customer_group')) {
                $table->string('customer_group')->nullable()->after('name');
            }

            if (! Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type')->default('LOCAL')->after('customer_group');
                $table->index('customer_type');
            }

            if (! Schema::hasColumn('customers', 'tax_number')) {
                $table->string('tax_number')->nullable()->after('customer_type');
            }

            if (! Schema::hasColumn('customers', 'mobile')) {
                $table->string('mobile')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('customers', 'website')) {
                $table->string('website')->nullable()->after('email');
            }

            if (! Schema::hasColumn('customers', 'billing_city')) {
                $table->string('billing_city')->nullable()->after('billing_address');
                $table->index('billing_city');
            }

            if (! Schema::hasColumn('customers', 'billing_province')) {
                $table->string('billing_province')->nullable()->after('billing_city');
            }

            if (! Schema::hasColumn('customers', 'billing_country')) {
                $table->string('billing_country')->nullable()->after('billing_province');
            }

            if (! Schema::hasColumn('customers', 'billing_postal_code')) {
                $table->string('billing_postal_code')->nullable()->after('billing_country');
            }

            if (! Schema::hasColumn('customers', 'shipping_city')) {
                $table->string('shipping_city')->nullable()->after('shipping_address');
            }

            if (! Schema::hasColumn('customers', 'shipping_province')) {
                $table->string('shipping_province')->nullable()->after('shipping_city');
            }

            if (! Schema::hasColumn('customers', 'shipping_country')) {
                $table->string('shipping_country')->nullable()->after('shipping_province');
            }

            if (! Schema::hasColumn('customers', 'shipping_postal_code')) {
                $table->string('shipping_postal_code')->nullable()->after('shipping_country');
            }

            if (! Schema::hasColumn('customers', 'default_currency_id')) {
                $table->foreignId('default_currency_id')->nullable()->after('shipping_postal_code')->constrained('currencies')->nullOnDelete();
            }

            if (! Schema::hasColumn('customers', 'payment_term_id')) {
                $table->foreignId('payment_term_id')->nullable()->after('default_currency_id')->constrained('payment_terms')->nullOnDelete();
            }

            if (! Schema::hasColumn('customers', 'default_tax_id')) {
                $table->foreignId('default_tax_id')->nullable()->after('payment_term_id')->constrained('taxes')->nullOnDelete();
            }

            if (! Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 18, 2)->default(0)->after('default_tax_id');
            }

            if (! Schema::hasColumn('customers', 'salesman')) {
                $table->string('salesman')->nullable()->after('credit_limit');
            }

            if (! Schema::hasColumn('customers', 'price_level')) {
                $table->string('price_level')->nullable()->after('salesman');
            }

            if (! Schema::hasColumn('customers', 'ar_account_id')) {
                $table->foreignId('ar_account_id')->nullable()->after('price_level')->constrained('accounting_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('customers', 'blocked_sales')) {
                $table->boolean('blocked_sales')->default(false)->after('ar_account_id');
                $table->index('blocked_sales');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach (['default_currency_id', 'payment_term_id', 'default_tax_id', 'ar_account_id'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['customer_type', 'billing_city', 'blocked_sales'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropIndex([$column]);
                }
            }

            $columns = [
                'customer_group',
                'customer_type',
                'tax_number',
                'mobile',
                'website',
                'billing_city',
                'billing_province',
                'billing_country',
                'billing_postal_code',
                'shipping_city',
                'shipping_province',
                'shipping_country',
                'shipping_postal_code',
                'credit_limit',
                'salesman',
                'price_level',
                'blocked_sales',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('taxes');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('payment_terms');
    }
};
