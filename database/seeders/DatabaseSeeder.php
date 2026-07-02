<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@linvy.local'],
            ['name' => 'Linvy Admin', 'password' => Hash::make('password')]
        );

        $accounts = [
            ['code' => '1100', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '1110', 'name' => 'Work In Process', 'type' => 'asset'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue'],
            ['code' => '5100', 'name' => 'Purchase Expense', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Inventory Adjustment', 'type' => 'expense'],
            ['code' => '5300', 'name' => 'Waste and Reject', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            AccountingAccount::firstOrCreate(['code' => $account['code']], $account);
        }

        $accountIds = AccountingAccount::pluck('id', 'code');

        foreach ([
            ['code' => 'RM', 'name' => 'Raw Material'],
            ['code' => 'PM', 'name' => 'Packaging Material'],
            ['code' => 'FG', 'name' => 'Finished Goods'],
            ['code' => 'CONS', 'name' => 'Consumable'],
            ['code' => 'NSTK', 'name' => 'Non Stock'],
        ] as $category) {
            ItemCategory::firstOrCreate(['code' => $category['code']], $category + [
                'default_inventory_account_id' => $accountIds['1100'],
                'default_cogs_account_id' => $accountIds['5000'],
                'default_sales_account_id' => $accountIds['4000'],
                'default_purchase_account_id' => $accountIds['5100'],
                'default_wip_account_id' => $accountIds['1110'],
                'default_adjustment_account_id' => $accountIds['5200'],
                'default_waste_account_id' => $accountIds['5300'],
            ]);
        }

        foreach ([
            ['code' => 'KG', 'name' => 'Kilogram', 'precision' => 3],
            ['code' => 'PCS', 'name' => 'Pieces', 'precision' => 0],
            ['code' => 'BOX', 'name' => 'Box', 'precision' => 0],
        ] as $uom) {
            UnitOfMeasure::firstOrCreate(['code' => $uom['code']], $uom);
        }

        foreach ([
            ['code' => 'RM-WH', 'name' => 'Raw Material Warehouse', 'type' => 'raw_material'],
            ['code' => 'PROD-WH', 'name' => 'Production Warehouse', 'type' => 'production'],
            ['code' => 'FG-WH', 'name' => 'Finished Goods Warehouse', 'type' => 'finished_goods'],
            ['code' => 'REJECT-WH', 'name' => 'Reject Warehouse', 'type' => 'reject'],
            ['code' => 'TRANSIT-WH', 'name' => 'Transit Warehouse', 'type' => 'transit'],
        ] as $warehouse) {
            Warehouse::firstOrCreate(['code' => $warehouse['code']], $warehouse);
        }
    }
}
