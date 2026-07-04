<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $accounts = AccountingAccount::pluck('id', 'code');

        foreach ([
            ['code' => 'RM', 'name' => 'Raw Material'],
            ['code' => 'PK', 'name' => 'Packaging Material'],
            ['code' => 'FG', 'name' => 'Finished Goods'],
            ['code' => 'CS', 'name' => 'Consumable'],
            ['code' => 'NSTK', 'name' => 'Non Stock'],
        ] as $category) {
            ItemCategory::updateOrCreate(['code' => $category['code']], $category + [
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
