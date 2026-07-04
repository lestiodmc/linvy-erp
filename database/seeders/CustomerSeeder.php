<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['CUS001', 'PT Sumber Pangan Indonesia'],
            ['CUS002', 'PT Seafood Prima'],
            ['CUS003', 'PT Frozen Food Indonesia'],
            ['CUS004', 'CV Nusantara Food'],
            ['CUS005', 'PT Mitra Retail Indonesia'],
            ['CUS006', 'PT Indo Fresh Market'],
            ['CUS007', 'PT Citra Boga'],
            ['CUS008', 'PT Surya Frozen'],
            ['CUS009', 'PT Bahagia Mart'],
            ['CUS010', 'CV Sentosa Abadi'],
        ] as [$code, $name]) {
            Customer::updateOrCreate(['code' => $code], [
                'name' => $name,
                'contact_person' => 'Procurement Team',
                'phone' => '021-77'.substr($code, -3).'66',
                'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
                'billing_address' => 'Jakarta Distribution Center',
                'shipping_address' => 'Jakarta Cold Storage Hub',
                'is_active' => true,
            ]);
        }
    }
}
