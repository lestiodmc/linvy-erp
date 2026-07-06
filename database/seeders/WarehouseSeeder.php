<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['code' => 'LINVY'],
            ['name' => 'PT Linvy Seafood Indonesia', 'is_active' => true]
        );

        $warehouseTypes = collect([
            'RAW_MATERIAL' => 'Raw Material',
            'PACKAGING' => 'Packaging',
            'PRODUCTION' => 'Production',
            'FINISHED_GOODS' => 'Finished Goods',
            'QC' => 'QC',
            'TRANSIT' => 'Transit',
            'REJECT' => 'Reject',
            'CONSUMABLE' => 'Consumable',
        ])->mapWithKeys(fn (string $name, string $code): array => [
            $code => WarehouseType::updateOrCreate(['code' => $code], [
                'name' => $name,
                'is_active' => true,
            ]),
        ]);

        $branches = collect([
            'SBY' => ['name' => 'Surabaya', 'address' => 'Surabaya, East Java'],
            'SDA' => ['name' => 'Sidoarjo', 'address' => 'Sidoarjo, East Java'],
        ])->mapWithKeys(fn (array $branch, string $code): array => [
            $code => Branch::updateOrCreate(['code' => $code], [
                'company_id' => $company->id,
                'name' => $branch['name'],
                'address' => $branch['address'],
                'is_active' => true,
            ]),
        ]);

        foreach ($branches as $branchCode => $branch) {
            foreach ([
                ['suffix' => 'RM-WH', 'name' => 'Raw Material Warehouse', 'type' => 'RAW_MATERIAL', 'legacy_type' => 'raw_material'],
                ['suffix' => 'PK-WH', 'name' => 'Packaging Warehouse', 'type' => 'PACKAGING', 'legacy_type' => 'packaging'],
                ['suffix' => 'PROD-WH', 'name' => 'Production Warehouse', 'type' => 'PRODUCTION', 'legacy_type' => 'production'],
                ['suffix' => 'FG-WH', 'name' => 'Finished Goods Warehouse', 'type' => 'FINISHED_GOODS', 'legacy_type' => 'finished_goods'],
                ['suffix' => 'QC-WH', 'name' => 'QC Warehouse', 'type' => 'QC', 'legacy_type' => 'qc'],
                ['suffix' => 'REJECT-WH', 'name' => 'Reject Warehouse', 'type' => 'REJECT', 'legacy_type' => 'reject'],
                ['suffix' => 'TRANSIT-WH', 'name' => 'Transit Warehouse', 'type' => 'TRANSIT', 'legacy_type' => 'transit'],
            ] as $warehouse) {
                Warehouse::updateOrCreate(['code' => $branchCode.'-'.$warehouse['suffix']], [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'warehouse_type_id' => $warehouseTypes[$warehouse['type']]->id,
                    'name' => $warehouse['name'],
                    'type' => $warehouse['legacy_type'],
                    'address' => $branch->name.' facility',
                    'is_active' => true,
                ]);
            }
        }

        Warehouse::whereIn('code', ['RM-WH', 'PK-WH', 'PROD-WH', 'FG-WH', 'QC-WH', 'TRANSIT-WH', 'REJECT-WH'])
            ->update(['is_active' => false]);
    }
}
