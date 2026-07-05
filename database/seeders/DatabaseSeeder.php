<?php

namespace Database\Seeders;

use App\Models\DocumentSequence;
use App\Models\ModuleSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedAccessData();
        $this->seedDocumentSequences();

        $this->call([
            AccountingAccountSeeder::class,
            CurrencySeeder::class,
            PaymentTermSeeder::class,
            TaxSeeder::class,
            WarehouseSeeder::class,
            UnitSeeder::class,
            ItemCategorySeeder::class,
            BrandSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            ItemSeeder::class,
        ]);
    }

    private function seedAccessData(): void
    {
        foreach ([
            ['code' => 'super-admin', 'name' => 'Super Admin'],
            ['code' => 'inventory-admin', 'name' => 'Inventory Admin'],
            ['code' => 'purchasing', 'name' => 'Purchasing'],
            ['code' => 'sales', 'name' => 'Sales'],
            ['code' => 'production', 'name' => 'Production'],
            ['code' => 'accounting', 'name' => 'Accounting'],
        ] as $role) {
            Role::updateOrCreate(['code' => $role['code']], $role + [
                'permissions' => config('linvy.role_permissions.'.$role['code'], []),
                'is_active' => true,
            ]);
        }

        $superAdmin = Role::where('code', 'super-admin')->first();

        User::updateOrCreate(
            ['email' => 'admin@linvy.local'],
            ['name' => 'Linvy Admin', 'password' => Hash::make('password'), 'role_id' => $superAdmin?->id]
        );

        foreach (config('linvy.optional_modules') as $module) {
            ModuleSetting::updateOrCreate(
                ['module' => $module],
                [
                    'label' => str($module)->replace('_', ' ')->title(),
                    'enabled' => (bool) config("linvy.default_enabled_modules.$module", false),
                ]
            );
        }
    }

    private function seedDocumentSequences(): void
    {
        foreach ([
            ['code' => 'PURCHASE_REQUEST', 'name' => 'Purchase Request', 'prefix' => 'PR'],
            ['code' => 'PURCHASE_ORDER', 'name' => 'Purchase Order', 'prefix' => 'PO'],
            ['code' => 'GOODS_RECEIPT', 'name' => 'Goods Receipt', 'prefix' => 'RCV'],
            ['code' => 'PURCHASE_INVOICE', 'name' => 'Purchase Invoice', 'prefix' => 'PI'],
            ['code' => 'SALES_ORDER', 'name' => 'Sales Order', 'prefix' => 'SO'],
            ['code' => 'DELIVERY_ORDER', 'name' => 'Delivery Order', 'prefix' => 'DO'],
            ['code' => 'SALES_INVOICE', 'name' => 'Sales Invoice', 'prefix' => 'SI'],
            ['code' => 'JOURNAL_VOUCHER', 'name' => 'Journal Voucher', 'prefix' => 'JV'],
            ['code' => 'STOCK_ADJUSTMENT', 'name' => 'Stock Adjustment', 'prefix' => 'ADJ'],
            ['code' => 'STOCK_OPNAME', 'name' => 'Stock Opname', 'prefix' => 'STKOP'],
            ['code' => 'WAREHOUSE_TRANSFER', 'name' => 'Warehouse Transfer', 'prefix' => 'TRF'],
            ['code' => 'PRODUCTION_ORDER', 'name' => 'Production / Repacking', 'prefix' => 'PRD'],
        ] as $sequence) {
            DocumentSequence::updateOrCreate(['code' => $sequence['code']], $sequence + [
                'document_type' => $sequence['code'],
                'date_format' => 'YYYYMM',
                'digits' => 5,
                'reset_type' => 'monthly',
                'company_id' => null,
                'branch_id' => null,
                'period_type' => 'monthly',
                'padding' => 5,
                'separator' => '-',
                'is_active' => true,
            ]);
        }
    }
}
