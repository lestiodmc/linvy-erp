<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\ItemCategory;
use App\Models\WarehouseType;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $accounts = AccountingAccount::pluck('id', 'code');
        $warehouseTypes = WarehouseType::pluck('id', 'code');

        foreach ([
            ['code' => 'RM', 'name' => 'Raw Material', 'item_type' => 'INVENTORY', 'warehouse_type_code' => 'RAW_MATERIAL', 'allow_purchase' => true, 'allow_sales' => false],
            ['code' => 'FG', 'name' => 'Finished Goods', 'item_type' => 'INVENTORY', 'warehouse_type_code' => 'FINISHED_GOODS', 'allow_purchase' => false, 'allow_sales' => true],
            ['code' => 'PK', 'name' => 'Packaging Material', 'item_type' => 'INVENTORY', 'warehouse_type_code' => 'PACKAGING', 'allow_purchase' => true, 'allow_sales' => false],
            ['code' => 'CS', 'name' => 'Consumable', 'item_type' => 'INVENTORY', 'warehouse_type_code' => 'CONSUMABLE', 'allow_purchase' => true, 'allow_sales' => false],
            ['code' => 'NSTK', 'name' => 'Non Stock', 'item_type' => 'NON_INVENTORY', 'warehouse_type_code' => null, 'allow_purchase' => true, 'allow_sales' => false],
            ['code' => 'SV', 'name' => 'Service', 'item_type' => 'SERVICE', 'warehouse_type_code' => null, 'allow_purchase' => true, 'allow_sales' => true],
        ] as $category) {
            ItemCategory::updateOrCreate(['code' => $category['code']], [
                'name' => $category['name'],
                'item_type' => $category['item_type'],
                'default_warehouse_type_id' => $category['warehouse_type_code'] ? ($warehouseTypes[$category['warehouse_type_code']] ?? null) : null,
                'allow_purchase' => $category['allow_purchase'],
                'allow_sales' => $category['allow_sales'],
                'description' => $category['name'].' item category',
                'default_inventory_account_id' => $accounts['1100'] ?? null,
                'default_cogs_account_id' => $accounts['5000'] ?? null,
                'default_sales_account_id' => $accounts['4000'] ?? null,
                'default_purchase_account_id' => $accounts['5100'] ?? null,
                'default_wip_account_id' => $accounts['1110'] ?? null,
                'default_adjustment_account_id' => $accounts['5200'] ?? null,
                'default_waste_account_id' => $accounts['5300'] ?? null,
                'is_active' => true,
            ]);
        }
    }
}
