<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimal_places' => 0, 'is_base_currency' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_base_currency' => false],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => 'EUR', 'decimal_places' => 2, 'is_base_currency' => false],
        ] as $currency) {
            Currency::updateOrCreate(['code' => $currency['code']], $currency + [
                'is_active' => true,
            ]);
        }
    }
}
