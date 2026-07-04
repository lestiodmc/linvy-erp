<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['SUP001', 'PT Laut Biru Seafood'],
            ['SUP002', 'PT Mina Bahari Nusantara'],
            ['SUP003', 'PT Samudera Segar Indonesia'],
            ['SUP004', 'CV Anugerah Laut'],
            ['SUP005', 'CV Bahari Makmur'],
            ['SUP006', 'PT Ocean Fresh Indonesia'],
            ['SUP007', 'PT Laut Nusantara'],
            ['SUP008', 'CV Mitra Seafood'],
            ['SUP009', 'PT Sumber Laut Abadi'],
            ['SUP010', 'PT Indo Marine Food'],
        ] as [$code, $name]) {
            Supplier::updateOrCreate(['code' => $code], [
                'name' => $name,
                'contact_person' => 'Purchasing Desk',
                'phone' => '021-55'.substr($code, -3).'88',
                'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
                'address' => 'Jakarta Seafood Industrial Area',
                'is_active' => true,
            ]);
        }
    }
}
