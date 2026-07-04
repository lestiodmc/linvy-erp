<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use Illuminate\Database\Seeder;

class AccountingAccountSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => '1100', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '1110', 'name' => 'Work In Process', 'type' => 'asset'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue'],
            ['code' => '5100', 'name' => 'Purchase Expense', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Inventory Adjustment', 'type' => 'expense'],
            ['code' => '5300', 'name' => 'Waste and Reject', 'type' => 'expense'],
        ] as $account) {
            AccountingAccount::updateOrCreate(['code' => $account['code']], $account + ['is_active' => true]);
        }
    }
}
