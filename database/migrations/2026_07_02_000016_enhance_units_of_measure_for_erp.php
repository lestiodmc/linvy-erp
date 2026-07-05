<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units_of_measure', function (Blueprint $table) {
            if (! Schema::hasColumn('units_of_measure', 'type')) {
                $table->string('type')->default('BASE')->after('name');
            }

            if (! Schema::hasColumn('units_of_measure', 'base_unit_id')) {
                $table->foreignId('base_unit_id')
                    ->nullable()
                    ->after('type')
                    ->constrained('units_of_measure')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('units_of_measure', 'conversion_factor')) {
                $table->decimal('conversion_factor', 18, 6)->default(1)->after('base_unit_id');
            }

            if (! Schema::hasColumn('units_of_measure', 'allow_decimal')) {
                $table->boolean('allow_decimal')->default(false)->after('precision');
            }

            if (! Schema::hasColumn('units_of_measure', 'description')) {
                $table->text('description')->nullable()->after('allow_decimal');
            }
        });
    }

    public function down(): void
    {
        //
    }
};
