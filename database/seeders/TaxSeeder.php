<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'VAT11', 'name' => 'PPN 11%', 'tax_type' => 'VAT', 'rate' => 11],
            ['code' => 'VAT12', 'name' => 'PPN 12%', 'tax_type' => 'VAT', 'rate' => 12],
            ['code' => 'WHT23', 'name' => 'PPh 23', 'tax_type' => 'WITHHOLDING', 'rate' => 2],
            ['code' => 'NONE', 'name' => 'No Tax', 'tax_type' => 'OTHER', 'rate' => 0],
        ] as $tax) {
            Tax::updateOrCreate(['code' => $tax['code']], $tax + [
                'is_inclusive' => false,
                'description' => $tax['name'].' tax setup',
                'is_active' => true,
            ]);
        }
    }
}
