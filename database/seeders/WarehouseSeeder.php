<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'RM-WH', 'name' => 'Raw Material Warehouse', 'type' => 'raw_material'],
            ['code' => 'PK-WH', 'name' => 'Packaging Warehouse', 'type' => 'packaging'],
            ['code' => 'PROD-WH', 'name' => 'Production Warehouse', 'type' => 'production'],
            ['code' => 'FG-WH', 'name' => 'Finished Goods Warehouse', 'type' => 'finished_goods'],
            ['code' => 'REJECT-WH', 'name' => 'Reject Warehouse', 'type' => 'reject'],
        ] as $warehouse) {
            Warehouse::updateOrCreate(['code' => $warehouse['code']], $warehouse + [
                'address' => 'PT Linvy Seafood Indonesia Facility',
                'is_active' => true,
            ]);
        }
    }
}
