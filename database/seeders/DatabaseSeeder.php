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
            ['document_type' => 'PR', 'name' => 'Purchase Request', 'prefix' => 'PR'],
            ['document_type' => 'PO', 'name' => 'Purchase Order', 'prefix' => 'PO'],
            ['document_type' => 'RCV', 'name' => 'Receiving', 'prefix' => 'RCV'],
            ['document_type' => 'SO', 'name' => 'Sales Order', 'prefix' => 'SO'],
            ['document_type' => 'DO', 'name' => 'Delivery Order', 'prefix' => 'DO'],
            ['document_type' => 'TRF', 'name' => 'Warehouse Transfer', 'prefix' => 'TRF'],
            ['document_type' => 'ADJ', 'name' => 'Stock Adjustment', 'prefix' => 'ADJ'],
            ['document_type' => 'PRD', 'name' => 'Production / Repacking', 'prefix' => 'PRD'],
        ] as $sequence) {
            DocumentSequence::updateOrCreate(['document_type' => $sequence['document_type']], $sequence + [
                'period_type' => 'monthly',
                'padding' => 4,
                'separator' => '/',
                'is_active' => true,
            ]);
        }
    }
}
