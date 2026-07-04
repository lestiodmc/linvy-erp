<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->restrictOnDelete();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('warehouse_types')) {
            Schema::create('warehouse_types', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        DB::table('companies')->updateOrInsert(
            ['code' => 'LINVY'],
            ['name' => config('linvy.company.name', 'PT Linvy Seafood Indonesia'), 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );

        $companyId = DB::table('companies')->where('code', 'LINVY')->value('id');
        DB::table('branches')->updateOrInsert(
            ['code' => 'SBY'],
            ['company_id' => $companyId, 'name' => 'Surabaya', 'address' => 'Surabaya, East Java', 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
        );

        foreach ($this->warehouseTypes() as $code => $name) {
            DB::table('warehouse_types')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        Schema::table('warehouses', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouses', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('warehouses', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('warehouses', 'warehouse_type_id')) {
                $table->foreignId('warehouse_type_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            }
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('warehouses', 'type')) {
            DB::statement('ALTER TABLE warehouses MODIFY type VARCHAR(255) NULL');
        }

        $branchId = DB::table('branches')->where('code', 'SBY')->value('id');
        foreach (DB::table('warehouses')->get() as $warehouse) {
            $typeCode = $this->legacyTypeCode($warehouse->type ?? null);
            $warehouseTypeId = DB::table('warehouse_types')->where('code', $typeCode)->value('id')
                ?? DB::table('warehouse_types')->where('code', 'RAW_MATERIAL')->value('id');

            DB::table('warehouses')->where('id', $warehouse->id)->update([
                'company_id' => $warehouse->company_id ?: $companyId,
                'branch_id' => $warehouse->branch_id ?: $branchId,
                'warehouse_type_id' => $warehouse->warehouse_type_id ?: $warehouseTypeId,
                'updated_at' => $now,
            ]);
        }

        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'default_warehouse_type_id')) {
                $table->foreignId('default_warehouse_type_id')->nullable()->after('unit_of_measure_id')->constrained('warehouse_types')->nullOnDelete();
            }
        });

        foreach (DB::table('items')->get() as $item) {
            $typeCode = match ($item->type) {
                'raw_material' => 'RAW_MATERIAL',
                'packaging_material' => 'PACKAGING',
                'finished_goods' => 'FINISHED_GOODS',
                'consumable' => 'CONSUMABLE',
                default => null,
            };

            if ($typeCode) {
                DB::table('items')->where('id', $item->id)->whereNull('default_warehouse_type_id')->update([
                    'default_warehouse_type_id' => DB::table('warehouse_types')->where('code', $typeCode)->value('id'),
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::table('receiving_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('receiving_lines', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('remaining_quantity')->constrained()->nullOnDelete();
            }
        });

        DB::table('receiving_lines')
            ->join('receivings', 'receiving_lines.receiving_id', '=', 'receivings.id')
            ->whereNull('receiving_lines.warehouse_id')
            ->whereNotNull('receivings.warehouse_id')
            ->update(['receiving_lines.warehouse_id' => DB::raw('receivings.warehouse_id')]);
    }

    public function down(): void
    {
        //
    }

    private function warehouseTypes(): array
    {
        return [
            'RAW_MATERIAL' => 'Raw Material',
            'PACKAGING' => 'Packaging',
            'PRODUCTION' => 'Production',
            'FINISHED_GOODS' => 'Finished Goods',
            'QC' => 'QC',
            'TRANSIT' => 'Transit',
            'REJECT' => 'Reject',
            'CONSUMABLE' => 'Consumable',
        ];
    }

    private function legacyTypeCode(?string $type): string
    {
        return match ($type) {
            'packaging' => 'PACKAGING',
            'production' => 'PRODUCTION',
            'finished_goods' => 'FINISHED_GOODS',
            'reject' => 'REJECT',
            'transit' => 'TRANSIT',
            default => 'RAW_MATERIAL',
        };
    }
};
